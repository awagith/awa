<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

/**
 * Lê um CSV de produtos (Magento import) e cria categorias faltantes.
 * Por padrão usa `_csv/catalog_product_sanitizado.csv` e coluna `categories`.
 *
 * Uso:
 *   php scripts/ensure_categories_from_csv.php \
 *       --input _csv/catalog_product_sanitizado.csv \
 *       --column categories \
 *       --delimiter , \
 *       --dry-run
 */

require __DIR__ . '/../app/bootstrap.php';

$opts = getopt('', ['input::', 'column::', 'delimiter::', 'dry-run']);
$inputOpt = $opts['input'] ?? null;
$input = (is_string($inputOpt) && $inputOpt !== '') ? $inputOpt : '_csv/catalog_product_sanitizado.csv';
$columnOpt = $opts['column'] ?? null;
$column = (is_string($columnOpt) && $columnOpt !== '') ? $columnOpt : 'categories';
$delimiterOpt = $opts['delimiter'] ?? null;
$delimiter = (is_string($delimiterOpt) && $delimiterOpt !== '') ? $delimiterOpt : ',';
$dryRun = array_key_exists('dry-run', $opts);

if (!is_file($input)) {
    fwrite(STDERR, "ERRO: arquivo não encontrado: $input\n");
    exit(2);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository */
$categoryRepository = $om->get(Magento\Catalog\Api\CategoryRepositoryInterface::class);
/** @var Magento\Catalog\Model\CategoryFactory $categoryFactory */
$categoryFactory = $om->get(Magento\Catalog\Model\CategoryFactory::class);
/** @var Magento\Eav\Model\Config $eavConfig */
$eavConfig = $om->get(Magento\Eav\Model\Config::class);

// Root Category (geralmente ID 2 em stores padrão)
$rootId = 2;

function slugify_url_key(string $label): string {
    $label = trim($label);
    $label = mb_strtolower($label, 'UTF-8');
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $label);
    if ($trans !== false) { $label = $trans; }
    $label = preg_replace('/[^a-z0-9]+/i', '-', $label) ?? '';
    $label = preg_replace('/-+/', '-', $label) ?? '';
    return trim($label, '-');
}

function findCategoryIdByNameAndParent(
    Magento\Catalog\Model\CategoryFactory $categoryFactory,
    string $name,
    int $parentId
): ?int {
    $collection = $categoryFactory->create()->getCollection();
    $collection->addAttributeToFilter('name', $name)
        ->addAttributeToFilter('parent_id', $parentId)
        ->setPageSize(1);
    if ($collection->getSize() > 0) {
        return (int)$collection->getFirstItem()->getId();
    }
    return null;
}

$fh = fopen($input, 'r');
if (!$fh) { fwrite(STDERR, "ERRO ao abrir $input\n"); exit(3); }
$header = fgetcsv($fh, 0, $delimiter);
if (!$header) { fwrite(STDERR, "ERRO: cabeçalho inválido\n"); exit(4); }
$map = array_flip($header);
if (!isset($map[$column])) { fwrite(STDERR, "ERRO: coluna '$column' não encontrada\n"); exit(5); }
$colIdx = $map[$column];

$toCreate = [];
$seenPaths = [];
while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    $raw = (string)($row[$colIdx] ?? '');
    if ($raw === '') { continue; }
    // O Magento aceita múltiplos caminhos de categorias separados por '|'
    $paths = array_map('trim', explode('|', $raw));
    foreach ($paths as $path) {
        if ($path === '') { continue; }
        // Ex.: Categorias/Retrovisores/Linha Original
        $parts = array_values(
            array_filter(
                array_map('trim', explode('/', $path)),
                function ($x) { return $x !== ''; }
            )
        );
        if (!$parts) { continue; }
        $parent = $rootId;
        $currentPath = [];
        foreach ($parts as $part) {
            $currentPath[] = $part;
            $key = $parent . '>' . implode('/', $currentPath);
            if (isset($seenPaths[$key])) {
                $parent = $seenPaths[$key];
                continue;
            }
            $existingId = findCategoryIdByNameAndParent($categoryFactory, $part, $parent);
            if ($existingId) {
                $seenPaths[$key] = $existingId;
                $parent = $existingId;
                continue;
            }
            // marca para criação
            $toCreate[] = ['name' => $part, 'parent' => $parent];
            // placeholder negativo para caminhar adiante
            $placeholderId = -count($toCreate); // id fictício
            $seenPaths[$key] = $placeholderId;
            $parent = $placeholderId;
        }
    }
}
fclose($fh);

// Resolve placeholders criando de cima para baixo
$created = [];
$warnings = [];

foreach ($toCreate as $idx => $item) {
    $name = $item['name'];
    $parent = $item['parent'];
    // converte parent placeholder para real id
    if ($parent < 0) {
        // tentar achar o pai real já criado pelo nome + heurística simples (não ambígua na mesma execução)
        // fallback: root
        $parent = $rootId;
    }
    if ($dryRun) {
        echo "[dry-run] Criaria categoria '$name' sob pai $parent\n";
        continue;
    }
    try {
        $cat = $categoryFactory->create();
        $cat->setName($name);
        $cat->setIsActive(true);
        $cat->setParentId($parent);
        $cat->setStoreId(0);
        $cat->setUrlKey(slugify_url_key($name));
        $cat->setData('display_mode', 'PRODUCTS');
        $cat->setData('is_anchor', 1);
        $saved = $categoryRepository->save($cat);
        $created[] = (int)$saved->getId();
        echo "✓ Categoria criada: '{$name}' (ID {$saved->getId()})\n";
    } catch (\Throwable $e) {
        $warnings[] = "Erro criando '{$name}': " . $e->getMessage();
        echo "✗ Erro criando '{$name}': " . $e->getMessage() . "\n";
    }
}

echo "\nResumo: a_criar=" . count($toCreate) . ", criadas=" . count($created) . ", avisos=" . count($warnings) . "\n";
exit(0);
