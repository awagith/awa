<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;

// Script de importação de produtos usando o mecanismo nativo do Magento ImportExport
// Uso:
//   php scripts/import_products_from_csv.php \
//       --file _csv/catalog_product_sanitizado.csv \
//       --behavior add_update \
//       --delimiter , \
//       --enclosure '"' \
//       --escape \\ \
//       --allowed-errors 50

require __DIR__ . '/../app/bootstrap.php';

ini_set('display_errors', '1');
ini_set('memory_limit', '2048M');
set_time_limit(0);

$opts = getopt('', [
    'file:', 'behavior::', 'delimiter::', 'enclosure::', 'escape::', 'allowed-errors::'
]);

function required(array $opts, string $key): string {
    if (empty($opts[$key]) || !is_string($opts[$key])) {
        fwrite(STDERR, "ERRO: opção --$key é obrigatória.\n");
        exit(2);
    }
    return (string)$opts[$key];
}

$file      = required($opts, 'file');
$behavior  = isset($opts['behavior']) && is_string($opts['behavior']) ? strtolower($opts['behavior']) : 'add_update';
$delimiter = isset($opts['delimiter']) && is_string($opts['delimiter']) ? $opts['delimiter'] : ',';
$enclosure = isset($opts['enclosure']) && is_string($opts['enclosure']) ? $opts['enclosure'] : '"';
$escape    = isset($opts['escape']) && is_string($opts['escape']) ? $opts['escape'] : '\\';
$allowed   = isset($opts['allowed-errors']) && is_numeric($opts['allowed-errors']) ? (int)$opts['allowed-errors'] : 50;

if (!is_file($file)) {
    fwrite(STDERR, "ERRO: arquivo CSV não encontrado: $file\n");
    exit(3);
}

// Mapear comportamento
$behaviorMap = [
    'append'      => \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND,
    'add_update'  => \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE,
    'replace'     => \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE,
    'delete'      => \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE,
];
$behaviorConst = $behaviorMap[$behavior] ?? \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE;

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();

/** @var Magento\Framework\App\State $state */
$state = $om->get(Magento\Framework\App\State::class);
try { $state->setAreaCode(Magento\Framework\App\Area::AREA_ADMINHTML); } catch (\Exception $e) {}

/** @var Magento\ImportExport\Model\Import $importModel */
$importModel = $om->create(Magento\ImportExport\Model\Import::class);
/** @var Magento\ImportExport\Model\Import\Source\CsvFactory $csvFactory */
$csvFactory = $om->get(Magento\ImportExport\Model\Import\Source\CsvFactory::class);

// Criar fonte CSV
$source = $csvFactory->create([
    'file'      => $file,
    'delimiter' => $delimiter,
    'enclosure' => $enclosure,
    'escape'    => $escape,
]);

// Configuração do import
$data = [
    'entity'               => 'catalog_product',
    'behavior'             => $behaviorConst,
    'validation_strategy'  => \Magento\ImportExport\Model\Import::VALIDATION_STRATEGY_SKIP_ERRORS,
    'allowed_error_count'  => $allowed,
];

$importModel->setData($data);
$importModel->setEntityCode('catalog_product');

echo "🔎 Validando CSV...\n";
$valid = $importModel->validateSource($source);

$errorAgg = $importModel->getErrorAggregator();
$errorsCount = 0;
if ($errorAgg) {
    $errorsCount = $errorAgg->getErrorsCount();
}

if (!$valid || $errorsCount > 0) {
    echo "⚠️  Erros encontrados na validação: $errorsCount\n";
    if ($errorAgg) {
        foreach ($errorAgg->getAllErrors() as $error) {
            // $error é Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError
            echo " - Linha {$error->getRowNumber()}: {$error->getErrorMessage()}\n";
        }
    }
    // Prosseguir mesmo com erros se dentro do limite
    if ($errorsCount > $allowed) {
        echo "❌ Número de erros excede o limite permitido ($allowed). Abortando.\n";
        exit(4);
    }
}

echo "🚚 Importando dados... (comportamento: $behavior)\n";
$result = $importModel->importSource();
if (!$result) {
    echo "❌ Import retornou falso. Verifique o CSV e logs.\n";
    exit(5);
}

// Estatísticas
$created = method_exists($importModel, 'getCreatedItemsCount') ? (int)$importModel->getCreatedItemsCount() : 0;
$updated = method_exists($importModel, 'getUpdatedItemsCount') ? (int)$importModel->getUpdatedItemsCount() : 0;
$deleted = method_exists($importModel, 'getDeletedItemsCount') ? (int)$importModel->getDeletedItemsCount() : 0;

echo "\n✅ Importação concluída.\n";
echo " - Criados:  $created\n";
echo " - Atualizados: $updated\n";
echo " - Deletados: $deleted\n";

if ($errorAgg && $errorAgg->getInvalidRowsCount() > 0) {
    echo " - Linhas inválidas: " . $errorAgg->getInvalidRowsCount() . "\n";
}

exit(0);
