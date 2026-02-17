<?php
/**
 * Script de sanitização e validação de CSV de produtos Magento 2.
 * Uso:
 *   php scripts/sanitize_catalog_csv.php \
 *       --input _csv/catalog_product.csv \
 *       --output _csv/catalog_product_sanitizado.csv \
 *       --report _csv/catalog_product_relatorio.json \
 *       --image-dir pub/media/import \
 *       --generate-url-key \
 *       --fix-sku \
 *       --check-images \
 *       --dry-run
 *
 * Flags:
 *   --dry-run          Não grava CSV de saída; apenas relatório.
 *   --fix-sku          Substitui espaços e caracteres inválidos por hífens.
 *   --generate-url-key Cria url_key quando vazio baseado no name.
 *   --check-images     Verifica existência dos arquivos de imagem.
 */

declare(strict_types=1);

$options = getopt('', [
    'input:', 'output:', 'report:', 'image-dir:',
    'generate-url-key', 'fix-sku', 'check-images', 'dry-run'
]);

function requiredOption(array $opts, string $key): string {
    if (empty($opts[$key])) {
        fwrite(STDERR, "ERRO: opção --$key obrigatória.\n");
        exit(2);
    }
    return (string)$opts[$key];
}

$input     = requiredOption($options, 'input');
$output    = $options['output'] ?? '_csv/catalog_product_sanitizado.csv';
$report    = $options['report'] ?? '_csv/catalog_product_relatorio.json';
$imageDir  = rtrim($options['image-dir'] ?? 'pub/media/import', '/');
$dryRun    = array_key_exists('dry-run', $options);
$doFixSku  = array_key_exists('fix-sku', $options);
$doUrlKey  = array_key_exists('generate-url-key', $options);
$doCheckImages = array_key_exists('check-images', $options);

if (!is_file($input)) {
    fwrite(STDERR, "ERRO: arquivo de entrada não encontrado: $input\n");
    exit(3);
}

if ($doCheckImages && !is_dir($imageDir)) {
    fwrite(STDERR, "ERRO: diretório de imagens não encontrado: $imageDir\n");
    exit(4);
}

function slugify(string $value): string {
    $value = trim($value);
    $value = mb_strtolower($value, 'UTF-8');
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    if ($trans !== false) {
        $value = $trans;
    }
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = preg_replace('/-+/', '-', $value) ?? '';
    return trim($value, '-');
}

function sanitizeSku(string $sku): string {
    $sku = preg_replace('/\s+/', '-', $sku) ?? $sku;
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $sku);
    if ($trans !== false) {
        $sku = $trans;
    }
    $sku = preg_replace('/[^A-Za-z0-9_-]/', '-', $sku) ?? $sku;
    $sku = preg_replace('/-+/', '-', $sku) ?? $sku;
    return $sku;
}

$fh = fopen($input, 'r');
if (!$fh) {
    fwrite(STDERR, "ERRO: não foi possível abrir $input\n");
    exit(5);
}

$delimiter = ',';
$header = fgetcsv($fh, 0, $delimiter);
if (!$header) {
    fwrite(STDERR, "ERRO: cabeçalho vazio ou inválido.\n");
    exit(6);
}

$columns = array_flip($header);

$idxSku        = $columns['sku']             ?? null;
$idxName       = $columns['name']            ?? null;
$idxUrlKey     = $columns['url_key']         ?? null;
$idxImage      = $columns['image']           ?? null;
$idxSmallImage = $columns['small_image']     ?? null;
$idxThumbnail  = $columns['thumbnail']       ?? null;
$idxAdditional = $columns['additional_images'] ?? null;

$reportData = [
    'timestamp' => date('c'),
    'input' => $input,
    'image_dir' => $imageDir,
    'dry_run' => $dryRun,
    'changes' => [
        'sku' => [],
        'url_key' => [],
    ],
    'missing_images' => [],
    'duplicate_additional_images' => [],
    'stats' => [
        'rows' => 0,
        'skus_changed' => 0,
        'url_keys_generated' => 0,
        'missing_images' => 0,
        'duplicates_in_additional' => 0,
    ],
    'warnings' => [],
];

$outHandle = null;
if (!$dryRun) {
    $outHandle = fopen($output, 'w');
    if (!$outHandle) {
        fwrite(STDERR, "ERRO: não foi possível criar $output\n");
        exit(7);
    }
    fputcsv($outHandle, $header, $delimiter);
}

while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
    $reportData['stats']['rows']++;
    if ($idxSku !== null && isset($row[$idxSku]) && $doFixSku) {
        $originalSku = $row[$idxSku];
        $newSku = sanitizeSku($originalSku);
        if ($newSku !== $originalSku) {
            $row[$idxSku] = $newSku;
            $reportData['changes']['sku'][] = ['from' => $originalSku, 'to' => $newSku];
            $reportData['stats']['skus_changed']++;
        }
    }
    if ($doUrlKey && $idxUrlKey !== null && isset($row[$idxUrlKey]) && trim((string)$row[$idxUrlKey]) === '' && $idxName !== null) {
        $generated = slugify((string)$row[$idxName]);
        if ($generated === '') {
            $reportData['warnings'][] = 'Falha em gerar url_key para SKU ' . ($row[$idxSku] ?? '(sem sku)');
        } else {
            $row[$idxUrlKey] = $generated;
            $reportData['changes']['url_key'][] = ['sku' => $row[$idxSku] ?? null, 'url_key' => $generated];
            $reportData['stats']['url_keys_generated']++;
        }
    }
    if ($doCheckImages) {
        $imageFields = [
            'image' => $idxImage,
            'small_image' => $idxSmallImage,
            'thumbnail' => $idxThumbnail,
        ];
        foreach ($imageFields as $label => $idx) {
            if ($idx !== null && isset($row[$idx]) && trim((string)$row[$idx]) !== '') {
                $path = $imageDir . '/' . ltrim((string)$row[$idx], '/');
                if (!is_file($path)) {
                    $reportData['missing_images'][] = [
                        'sku' => $row[$idxSku] ?? null,
                        'field' => $label,
                        'file' => $row[$idx],
                        'expected_path' => $path,
                    ];
                    $reportData['stats']['missing_images']++;
                }
            }
        }
        if ($idxAdditional !== null && isset($row[$idxAdditional]) && trim((string)$row[$idxAdditional]) !== '') {
            $listRaw = (string)$row[$idxAdditional];
            $parts = array_map('trim', explode(',', $listRaw));
            $unique = [];
            $dups = [];
            foreach ($parts as $img) {
                if ($img === '') { continue; }
                if (isset($unique[$img])) {
                    $dups[] = $img;
                } else {
                    $unique[$img] = true;
                }
                $path = $imageDir . '/' . ltrim($img, '/');
                if (!is_file($path)) {
                    $reportData['missing_images'][] = [
                        'sku' => $row[$idxSku] ?? null,
                        'field' => 'additional_images',
                        'file' => $img,
                        'expected_path' => $path,
                    ];
                    $reportData['stats']['missing_images']++;
                }
            }
            if ($dups) {
                $reportData['duplicate_additional_images'][] = [
                    'sku' => $row[$idxSku] ?? null,
                    'duplicates' => array_values(array_unique($dups)),
                ];
                $reportData['stats']['duplicates_in_additional']++;
            }
        }
    }
    if (!$dryRun && $outHandle) {
        fputcsv($outHandle, $row, $delimiter);
    }
}
fclose($fh);
if ($outHandle) { fclose($outHandle); }

$json = json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "ERRO: falha ao gerar JSON do relatório.\n");
    exit(8);
}
if (file_put_contents($report, $json) === false) {
    fwrite(STDERR, "ERRO: falha ao gravar relatório em $report\n");
    exit(9);
}

echo "Linhas processadas: {$reportData['stats']['rows']}\n";
echo "SKUs ajustados: {$reportData['stats']['skus_changed']}\n";
echo "url_keys gerados: {$reportData['stats']['url_keys_generated']}\n";
echo "Imagens faltantes: {$reportData['stats']['missing_images']}\n";
echo "Registros c/ duplicatas em additional_images: {$reportData['stats']['duplicates_in_additional']}\n";
echo ($dryRun ? "(dry-run) CSV não gravado em $output\n" : "CSV sanitizado gravado em $output\n");
echo "Relatório: $report\n";

if ($reportData['stats']['missing_images'] > 0) {
    exit(10);
}
exit(0);
