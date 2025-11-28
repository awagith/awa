#!/usr/bin/env php
<?php
/**
 * Rebuild do índice simples de busca fallback (MySQL FULLTEXT) sem depender de OpenSearch.
 * Uso:
 *   php scripts/fallback_search_rebuild.php [--batch 500] [--truncate 1]
 */
declare(strict_types=1);
error_reporting(E_ALL);

$args = $argv; array_shift($args);
$opts = [ 'batch' => 500, 'truncate' => 0 ];
foreach ($args as $i => $arg) {
    if (strpos($arg,'--')===0) {
        $k = substr($arg,2); $n = $args[$i+1] ?? null;
        if ($n !== null && strpos($n,'--')!==0) { $opts[$k] = is_numeric($n)? (int)$n : $n; }
        else { $opts[$k] = 1; }
    }
}
$batchSize = (int)$opts['batch']; if ($batchSize < 50) { $batchSize = 50; }
$doTruncate = (int)$opts['truncate'] === 1;

require __DIR__ . '/../app/bootstrap.php';
use Magento\Framework\App\Bootstrap;
use GrupoAwamotos\Fitment\Helper\Config as FitmentConfig;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ResourceConnection;

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

$createSql = "CREATE TABLE IF NOT EXISTS `$table` (
    `product_id` INT UNSIGNED NOT NULL PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `tokens` TEXT NOT NULL,
    `keywords` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY `ft_all` (`name`,`keywords`,`tokens`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($createSql);

// Garantir colunas/índices quando a tabela já existia de versões anteriores
$columns = $conn->describeTable($table);
if (!isset($columns['keywords'])) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN `keywords` TEXT NULL AFTER `tokens`");
}
try { $conn->query("ALTER TABLE `$table` ADD FULLTEXT KEY `ft_all` (`name`,`keywords`,`tokens`)"); } catch (\Throwable $e) { /* já existe */ }

if ($doTruncate) {
    $conn->truncateTable($table);
    echo "Tabela truncada.\n";
}

// Contar produtos
$count = $conn->fetchOne('SELECT COUNT(*) FROM ' . $resource->getTableName('catalog_product_entity'));
echo "Total produtos: $count\n";

function normalizeText(string $txt): string {
    $t = strtolower($txt);
    $t = preg_replace('/[^a-z0-9áàâãéêíóôõúç\s]/u',' ', $t);
    $t = preg_replace('/\s+/',' ', $t);
    $parts = array_filter(array_unique(explode(' ', trim($t))), fn($p)=>strlen($p)>1);
    return implode(' ', $parts);
}

$insertRows = [];
$processed = 0; $page = 1; $pages = (int)ceil($count / $batchSize);
while ($processed < $count) {
    $collection = $collectionFactory->create();
    $collection->addAttributeToSelect(['name','meta_keyword'])
        ->setPageSize($batchSize)
        ->setCurPage($page);
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
        // Aplicar sinônimos: se qualquer termo do grupo existir no nome normalizado, adiciona todos
        foreach ($synGroups as $grp) {
            foreach ($grp as $term) {
                if (strpos($tokens, $term) !== false) { // termo presente
                    $kwParts[] = implode(' ', $grp) . ' ';
                    break;
                }
            }
        }
        $kw = trim(implode(' ', array_filter($kwParts)));
        $insertRows[] = [ 'product_id' => $id, 'name' => $name, 'tokens' => $tokens, 'keywords' => $kw ];
    }
    if (count($insertRows) >= $batchSize) {
        // Upsert chunk
        $values = [];
        foreach ($insertRows as $r) {
            $values[] = '(' . $conn->quote($r['product_id']) . ',' . $conn->quote($r['name']) . ',' . $conn->quote($r['tokens']) . ',' . $conn->quote($r['keywords']) . ')';
        }
        $sql = 'INSERT INTO ' . $table . ' (product_id,name,tokens,keywords) VALUES ' . implode(',', $values) . ' ON DUPLICATE KEY UPDATE name=VALUES(name), tokens=VALUES(tokens), keywords=VALUES(keywords)';
        $conn->query($sql);
        $processed += count($insertRows);
        echo "Processado $processed / $count\n";
        $insertRows = [];
    }
    $page++;
    if ($page > $pages) { break; }
}
// Insert restante
if ($insertRows) {
    $values = [];
    foreach ($insertRows as $r) {
        $values[] = '(' . $conn->quote($r['product_id']) . ',' . $conn->quote($r['name']) . ',' . $conn->quote($r['tokens']) . ',' . $conn->quote($r['keywords']) . ')';
    }
    $sql = 'INSERT INTO ' . $table . ' (product_id,name,tokens,keywords) VALUES ' . implode(',', $values) . ' ON DUPLICATE KEY UPDATE name=VALUES(name), tokens=VALUES(tokens), keywords=VALUES(keywords)';
    $conn->query($sql);
    $processed += count($insertRows);
    echo "Processado $processed / $count\n";
}

echo "Rebuild concluído.\n";
// Estatísticas finais
$idxCount = $conn->fetchOne('SELECT COUNT(*) FROM ' . $table);
echo "Linhas indexadas: $idxCount\n";
exit(0);
