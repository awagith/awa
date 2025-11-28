<?php
$file = '/home/jessessh/htdocs/srv1113343.hstgr.cloud/pub/static/frontend/ayo/ayo_default/pt_BR/mage/requirejs/mixins.js';
echo "Checking file: $file\n";
if (file_exists($file)) {
    echo "File exists.\n";
    $stat = stat($file);
    if ($stat) {
        echo "Stat successful.\n";
        print_r($stat);
    } else {
        echo "Stat failed.\n";
        print_r(error_get_last());
    }
} else {
    echo "File does not exist.\n";
}
