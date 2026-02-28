<?php
declare(strict_types=1);

// Uso: php scripts/export_skus_from_csv.php --input _csv/catalog_product_sanitizado.csv --output _csv/imported_skus.txt

$opts = getopt('', ['input:', 'output:']);
if (!isset($opts['input'], $opts['output'])) {
    fwrite(STDERR, "Uso: php scripts/export_skus_from_csv.php --input <csv> --output <txt>\n");
    exit(2);
}
$in = (string)$opts['input'];
$out = (string)$opts['output'];
if (!is_file($in)) {
    fwrite(STDERR, "ERRO: arquivo não encontrado: $in\n");
    exit(3);
}

$fh = fopen($in, 'r');
if (!$fh) { fwrite(STDERR, "ERRO ao abrir $in\n"); exit(4); }
$header = fgetcsv($fh, 0, ',','"','\\');
if (!$header) { fwrite(STDERR, "ERRO: cabeçalho inválido\n"); exit(5); }
$cols = array_flip($header);
if (!isset($cols['sku'])) { fwrite(STDERR, "ERRO: coluna 'sku' não encontrada\n"); exit(6); }
$iSku = (int)$cols['sku'];

$tmp = fopen($out, 'w');
if (!$tmp) { fwrite(STDERR, "ERRO ao abrir saída: $out\n"); exit(7); }
$seen = [];
while (($row = fgetcsv($fh, 0, ',','"','\\')) !== false) {
    $sku = isset($row[$iSku]) ? trim((string)$row[$iSku]) : '';
    if ($sku === '' || isset($seen[$sku])) continue;
    $seen[$sku] = true;
    fwrite($tmp, $sku . "\n");
}
fclose($fh);
fclose($tmp);
echo "Gerado: $out (" . count($seen) . " SKUs)\n";
exit(0);
