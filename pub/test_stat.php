<?php
$file = '/home/user/htdocs/srv1113343.hstgr.cloud/pub/static/frontend/ayo/ayo_default/pt_BR/mage/requirejs/mixins.js';
echo "Testing stat for: $file\n";
$result = @stat($file);
if ($result) {
    echo "Stat OK\n";
    print_r($result);
} else {
    echo "Stat Failed\n";
    print_r(error_get_last());
}

$file2 = '/home/jessessh/htdocs/srv1113343.hstgr.cloud/pub/static/frontend/ayo/ayo_default/pt_BR/mage/requirejs/mixins.js';
echo "\nTesting stat for: $file2\n";
$result2 = @stat($file2);
if ($result2) {
    echo "Stat OK\n";
    print_r($result2);
} else {
    echo "Stat Failed\n";
    print_r(error_get_last());
}
