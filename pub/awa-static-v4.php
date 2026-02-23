<?php
/**
 * AWA Deploy Static v4 - Static content deploy + cache clean
 * Skips di:compile (generated/ already has 4856 files)
 * Self-deletes on completion
 */

$token = $_GET['token'] ?? '';
if ($token !== 'awa2026static') {
    http_response_code(403);
    die('Forbidden');
}

$step = (int)($_GET['step'] ?? 0);
$root = dirname(__DIR__);

header('Content-Type: text/plain; charset=utf-8');
ini_set('max_execution_time', 600);
ini_set('memory_limit', '4G');

echo "AWA Static Deploy v4 - Step $step\n";
echo str_repeat('=', 50) . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "User: " . get_current_user() . " | PID: " . getmypid() . "\n\n";

switch ($step) {
    case 1:
        // Step 1: Clean view_preprocessed + run static-content:deploy
        echo "=== Phase 1: Clean view_preprocessed ===\n";
        $vpDir = $root . '/var/view_preprocessed';
        if (is_dir($vpDir)) {
            shell_exec("rm -rf $vpDir/* 2>&1");
            echo "var/view_preprocessed: CLEANED\n";
        }

        // Also remove deployed_version.txt to force redeploy
        $dvFile = $root . '/pub/static/deployed_version.txt';
        if (file_exists($dvFile)) {
            @unlink($dvFile);
            echo "deployed_version.txt: REMOVED\n";
        }

        echo "\n=== Phase 2: Static Content Deploy ===\n";
        $cmd = "cd $root && bin/magento-www setup:static-content:deploy pt_BR -f --no-interaction 2>&1";
        echo "CMD: $cmd\n\n";

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        $outputStr = implode("\n", $output);
        echo $outputStr . "\n";
        echo "\nExit code: $returnCode\n";

        if ($returnCode === 0) {
            echo "\n*** STATIC DEPLOY SUCCESS ***\n";
            echo "Next: ?token=awa2026static&step=2 (cache clean)\n";
        } else {
            echo "\n*** STATIC DEPLOY FAILED ***\n";
            echo "Trying alternative: deploy with --jobs 1...\n\n";

            // Try with --jobs 1 (single thread, less memory)
            $cmd2 = "cd $root && bin/magento-www setup:static-content:deploy pt_BR -f --no-interaction --jobs 1 2>&1";
            $output2 = [];
            $returnCode2 = 0;
            exec($cmd2, $output2, $returnCode2);
            echo implode("\n", $output2) . "\n";
            echo "\nExit code (retry): $returnCode2\n";

            if ($returnCode2 === 0) {
                echo "\n*** STATIC DEPLOY SUCCESS (retry) ***\n";
                echo "Next: ?token=awa2026static&step=2\n";
            } else {
                echo "\n*** BOTH ATTEMPTS FAILED ***\n";
                echo "Manual deploy needed via Hostinger SSH.\n";
            }
        }
        break;

    case 2:
        // Step 2: Cache clean + flush
        echo "=== Cache Clean ===\n";
        $cmd = "cd $root && bin/magento-www cache:flush 2>&1";
        echo shell_exec($cmd) . "\n";

        // Also flush Redis
        echo "=== Redis Flush ===\n";
        echo shell_exec("redis-cli FLUSHALL 2>&1") . "\n";

        // Check deployed_version.txt
        $dvFile = $root . '/pub/static/deployed_version.txt';
        echo "deployed_version.txt: " . (file_exists($dvFile) ? file_get_contents($dvFile) : 'MISSING') . "\n";

        // Check if LESS was compiled
        $cssDir = $root . '/pub/static/frontend/ayo/ayo_home5/pt_BR/css';
        if (is_dir($cssDir)) {
            $files = glob($cssDir . '/*.css');
            echo "\nCompiled CSS files: " . count($files) . "\n";
            foreach ($files as $f) {
                echo "  " . basename($f) . " (" . filesize($f) . " bytes)\n";
            }
        }

        echo "\nNext: ?token=awa2026static&step=3 (self-delete)\n";
        break;

    case 3:
        // Self-delete
        @unlink(__FILE__);
        echo "Self-deleted.\n";
        break;

    default:
        echo "Usage:\n";
        echo "  ?step=1 - Clean + static deploy\n";
        echo "  ?step=2 - Cache clean + verify\n";
        echo "  ?step=3 - Self-delete\n";
        break;
}
