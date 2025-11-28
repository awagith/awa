<?php
$base = realpath(__DIR__ . '/..');
require_once $base . '/app/autoload.php';
$paths = [$base . '/app/code', $base . '/vendor'];
$files = [];
foreach ($paths as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && preg_match('/\/etc\/di\.xml$/', $file->getPathname())) {
            $files[] = $file->getPathname();
        }
    }
}

$bad = [];
function norm($class) {
    $class = trim($class);
    if ($class === '') return $class;
    return ltrim($class, '\\');
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
        if ($type && !class_exists($type)) {
            $bad[] = ["file"=>$file, "kind"=>"preference-type", "name"=>$type];
        }
    }
    // types with plugins
    foreach ($xml->xpath('//type') as $typeNode) {
        $name = norm((string)$typeNode['name']);
        if ($name && !interface_exists($name) && !class_exists($name)) {
            $bad[] = ["file"=>$file, "kind"=>"type-name", "name"=>$name];
        }
        foreach ($typeNode->plugin as $plugin) {
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
