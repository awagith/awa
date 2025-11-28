<?php
echo "User: " . exec('whoami') . "\n";
echo "Group: " . exec('id -gn') . "\n";
echo "Groups: " . exec('id -Gn') . "\n";
