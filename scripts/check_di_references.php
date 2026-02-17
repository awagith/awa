<?php
$base = realpath(__DIR__ . '/..');
require_once $base . '/app/autoload.php';

// Por padrão, validamos apenas módulos em app/code (principalmente customizações).
// O vendor pode conter referências de compatibilidade/desuso que não quebram a loja, mas geram falso-positivo.
$paths = [$base . '/app/code'];
if (!empty(getenv('DI_CHECK_INCLUDE_VENDOR'))) {
    $paths[] = $base . '/vendor';
}
$ignoreSubstrings = [
    // Artefatos de teste do magento2-base têm di.xml com classes não autoloadáveis em produção
    '/vendor/magento/magento2-base/dev/',
    '/vendor/magento/magento2-base/setup/src/Magento/Setup/Test/',
];

$shouldIgnore = static function (string $path) use ($ignoreSubstrings): bool {
    $p = str_replace('\\', '/', $path);
    foreach ($ignoreSubstrings as $needle) {
        if (strpos($p, $needle) !== false) {
            return true;
        }
    }
    return false;
};
$files = [];
foreach ($paths as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && preg_match('/\/etc\/di\.xml$/', $file->getPathname())) {
            $path = $file->getPathname();
            if ($shouldIgnore($path)) {
                continue;
            }
            $files[] = $path;
        }
    }
}

$bad = [];
function norm($class) {
    $class = trim($class);
    if ($class === '') return $class;
    return ltrim($class, '\\');
}

// Magento usa virtual types extensivamente (ex.: interceptionConfigScope). Eles não são classes PHP.
// Para evitar falsos positivos, coletamos todos os virtualTypes definidos nos di.xml escaneados.
$virtualTypes = [];
foreach ($files as $file) {
    $xml = @simplexml_load_file($file);
    if (!$xml) continue;
    $xml->registerXPathNamespace('x', 'urn:magento:framework:ObjectManager/etc/config.xsd');
    foreach ($xml->xpath('//virtualType') as $vt) {
        $vtName = norm((string)$vt['name']);
        if ($vtName !== '') {
            $virtualTypes[$vtName] = true;
        }
    }
}

foreach ($files as $file) {
    $xml = @simplexml_load_file($file);
    if (!$xml) continue;
    $xml->registerXPathNamespace('x', 'urn:magento:framework:ObjectManager/etc/config.xsd');
    // preferences
    foreach ($xml->xpath('//preference') as $pref) {
        $for = norm((string)$pref['for']);
        $type = norm((string)$pref['type']);
        if ($for && !interface_exists($for) && !class_exists($for)) {
            $bad[] = ["file"=>$file, "kind"=>"preference-for", "name"=>$for];
        }
        if ($type && empty($virtualTypes[$type]) && !class_exists($type)) {
            $bad[] = ["file"=>$file, "kind"=>"preference-type", "name"=>$type];
        }
    }
    // types with plugins
    foreach ($xml->xpath('//type') as $typeNode) {
        $name = norm((string)$typeNode['name']);
        if ($name && empty($virtualTypes[$name]) && !interface_exists($name) && !class_exists($name)) {
            $bad[] = ["file"=>$file, "kind"=>"type-name", "name"=>$name];
        }
        foreach ($typeNode->plugin as $plugin) {
            $disabled = strtolower(trim((string)($plugin['disabled'] ?? '')));
            if ($disabled === 'true' || $disabled === '1') {
                continue;
            }
            $ptype = norm((string)$plugin['type']);
            if ($ptype && !class_exists($ptype)) {
                $bad[] = ["file"=>$file, "kind"=>"plugin-type", "name"=>$ptype, "on"=>$name];
            }
        }
    }
}

if (!$bad) {
    echo "All di.xml references look valid\n";
    exit(0);
}
foreach ($bad as $b) {
    echo $b['kind'], " | ", $b['name'], " | ", $b['file'];
    if (!empty($b['on'])) echo " | on: ", $b['on'];
    echo "\n";
}
exit(1);
