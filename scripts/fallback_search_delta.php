#!/usr/bin/env php
<?php
/**
 * Atualização incremental do índice FULLTEXT fallback.
 * Uso:
 *   php scripts/fallback_search_delta.php [--since "2025-11-01 00:00:00"] [--limit 1000]
 */
declare(strict_types=1);
error_reporting(E_ALL);

$argv = isset($argv) && is_array($argv) ? $argv : [];
$args = $argv; array_shift($args);
$opts = [ 'since' => null, 'limit' => 1000 ];
foreach ($args as $i => $arg) {
    if (strpos($arg,'--')===0) {
        $k = substr($arg,2); $n = $args[$i+1] ?? null;
        if ($n !== null && strpos($n,'--')!==0) { $opts[$k] = $n; } else { $opts[$k] = '1'; }
    }
}
$limit = (int)$opts['limit']; if ($limit < 50) { $limit = 50; }
$since = $opts['since'];

require __DIR__ . '/../app/bootstrap.php';
use Magento\Framework\App\Bootstrap;
use GrupoAwamotos\Fitment\Helper\Config as FitmentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var FitmentConfig $cfg */
$cfg = $om->get(FitmentConfig::class);
/** @var ResourceConnection $resource */
$resource = $om->get(ResourceConnection::class);
/** @var CollectionFactory $collectionFactory */
$collectionFactory = $om->get(CollectionFactory::class);
$conn = $resource->getConnection();
$table = $resource->getTableName('grupoawamotos_fallback_search');

if (!$conn->isTableExists($table)) {
    fwrite(STDERR, "Tabela de índice inexistente. Execute rebuild primeiro.\n");
    exit(2);
}

// Adicionar colunas e índices se não existirem
try {
    $columns = $conn->describeTable($table);
    if (!isset($columns['source_updated_at'])) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `source_updated_at` DATETIME NULL AFTER `updated_at`");
        echo "Coluna source_updated_at adicionada.\n";
    }
    if (!isset($columns['keywords'])) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `keywords` TEXT NULL AFTER `tokens`");
        echo "Coluna keywords adicionada.\n";
    }
    // Garantir índice FULLTEXT combinado
    try { $conn->query("ALTER TABLE `$table` ADD FULLTEXT KEY `ft_all` (`name`,`keywords`,`tokens`)"); } catch (\Throwable $e) { /* pode já existir */ }
} catch (\Throwable $e) {
    fwrite(STDERR, "Erro alterando tabela: {$e->getMessage()}\n");
}

function normalizeText(string $txt): string {
    $t = strtolower($txt);
    $t = preg_replace('/[^a-z0-9áàâãéêíóôõúç\s]/u',' ', $t);
    $t = preg_replace('/\s+/',' ', $t);
    $parts = array_filter(array_unique(explode(' ', trim($t))), fn($p)=>strlen($p)>1);
    return implode(' ', $parts);
}

$prodTable = $resource->getTableName('catalog_product_entity');
$whereSince = $since ? 'WHERE updated_at >= ' . $conn->quote($since) : '';
$sql = "SELECT entity_id, updated_at FROM $prodTable $whereSince ORDER BY updated_at DESC LIMIT $limit";
$rows = $conn->fetchAll($sql);
if (!$rows) {
    echo "Nenhum produto para processar.\n";
    exit(0);
}

// Carregar nomes via coleção (mais seguro para atributos EAV)
$ids = array_map(fn($r)=> (int)$r['entity_id'], $rows);
$collection = $collectionFactory->create();
$collection->addAttributeToSelect(['name','meta_keyword'])
    ->addFieldToFilter('entity_id', ['in' => $ids]);

$values = [];
foreach ($collection as $product) {
    $id = (int)$product->getId();
    $name = (string)$product->getName();
    $sku = (string)$product->getSku();
    $metaKw = (string)($product->getData('meta_keyword') ?? '');
    $tokens = normalizeText($name);
    $skuWeight = $cfg->getSkuWeight();
    $metaWeight = $cfg->getMetaKeywordWeight();
    $synGroups = $cfg->getSynonymGroups();
    $kwParts = [];
    if ($sku !== '') { $kwParts[] = str_repeat(strtolower($sku).' ', $skuWeight); }
    $normMeta = normalizeText($metaKw);
    if ($normMeta !== '') { $kwParts[] = str_repeat($normMeta.' ', $metaWeight); }
    foreach ($synGroups as $grp) {
        foreach ($grp as $term) {
            if (strpos($tokens, $term) !== false) {
                $kwParts[] = implode(' ', $grp) . ' ';
                break;
            }
        }
    }
    $kw = trim(implode(' ', array_filter($kwParts)));
    $updated = null;
    foreach ($rows as $r) { if ((int)$r['entity_id'] === $id) { $updated = $r['updated_at']; break; } }
    $values[] = '(' . $conn->quote($id) . ',' . $conn->quote($name) . ',' . $conn->quote($tokens) . ',' . $conn->quote($kw) . ',' . ($updated ? $conn->quote($updated) : 'NULL') . ')';
}

if ($values) {
    $sqlInsert = 'INSERT INTO ' . $table . ' (product_id,name,tokens,keywords,source_updated_at) VALUES ' . implode(',', $values) . ' ON DUPLICATE KEY UPDATE name=VALUES(name), tokens=VALUES(tokens), keywords=VALUES(keywords), source_updated_at=VALUES(source_updated_at)';
    $conn->query($sqlInsert);
    echo "Produtos atualizados: " . count($values) . "\n";
}

exit(0);
