<?php
declare(strict_types=1);

$token = $_GET['token'] ?? '';
if ($token !== 'awa2026bg') {
    http_response_code(403);
    die('Forbidden');
}

$action = $_GET['action'] ?? 'start';
$basePath = dirname(__DIR__);
$logFile = $basePath . '/var/log/awa-deploy.log';
$pidFile = $basePath . '/var/log/awa-deploy.pid';

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo '<!DOCTYPE html><html><head><title>AWA Deploy</title></head><body><pre>';

if ($action === 'start') {
    // Check if already running
    if (file_exists($pidFile)) {
        $pid = (int) trim(file_get_contents($pidFile));
        if ($pid > 0 && file_exists("/proc/{$pid}")) {
            echo "Deploy already running! PID: {$pid}\n";
            echo "Use ?action=status to check progress.\n";
            echo '</pre></body></html>';
            exit;
        }
        @unlink($pidFile);
    }

    // Clean preprocessed
    $viewDir = $basePath . '/var/view_preprocessed';
    if (is_dir($viewDir)) {
        exec("rm -rf " . escapeshellarg($viewDir) . "/*");
    }

    $dvFile = $basePath . '/pub/static/deployed_version.txt';
    if (file_exists($dvFile)) {
        @unlink($dvFile);
    }

    // Write initial log
    file_put_contents($logFile, "DEPLOY STARTED: " . date('Y-m-d H:i:s') . "\n");

    // Build the deploy command
    $deployCmd = "cd " . escapeshellarg($basePath)
        . " && bin/magento-www setup:static-content:deploy pt_BR -f --no-interaction --jobs 1"
        . " >> " . escapeshellarg($logFile) . " 2>&1"
        . " && echo 'DEPLOY_STATUS: SUCCESS' >> " . escapeshellarg($logFile)
        . " || echo 'DEPLOY_STATUS: FAILED' >> " . escapeshellarg($logFile)
        . "; rm -f " . escapeshellarg($pidFile);

    // Execute in background
    $descriptorspec = [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', '/dev/null', 'w'],
        2 => ['file', '/dev/null', 'w'],
    ];

    $process = proc_open("nohup bash -c " . escapeshellarg($deployCmd) . " &", $descriptorspec, $pipes);

    if (is_resource($process)) {
        $status = proc_get_status($process);
        $pid = $status['pid'];
        // The actual child PID is pid+1 due to nohup wrapper
        $childPid = $pid + 1;
        file_put_contents($pidFile, (string) $childPid);
        proc_close($process);

        echo "Deploy started in background!\n";
        echo "PID: ~{$childPid}\n";
        echo "Log: var/log/awa-deploy.log\n";
        echo "Time: " . date('Y-m-d H:i:s') . "\n\n";
        echo "Use ?action=status to check progress.\n";
    } else {
        echo "ERROR: Failed to start background process!\n";
    }

} elseif ($action === 'status') {
    echo "=== Deploy Status === " . date('Y-m-d H:i:s') . "\n\n";

    // Check if running
    $running = false;
    if (file_exists($pidFile)) {
        $pid = (int) trim(file_get_contents($pidFile));
        // Check broader - any static-content:deploy running
        exec("pgrep -f 'static-content:deploy' 2>/dev/null", $pids);
        if (!empty($pids)) {
            $running = true;
            echo "Status: RUNNING (PIDs: " . implode(', ', $pids) . ")\n\n";
        } else {
            echo "Status: FINISHED\n\n";
            @unlink($pidFile);
        }
    } else {
        // Also check if process is running without PID file
        exec("pgrep -f 'static-content:deploy' 2>/dev/null", $pids);
        if (!empty($pids)) {
            $running = true;
            echo "Status: RUNNING (PIDs: " . implode(', ', $pids) . ")\n\n";
        } else {
            echo "Status: NOT RUNNING\n\n";
        }
    }

    // Show log
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $total = count($lines);
        echo "Log lines: {$total}\n";
        echo str_repeat('-', 50) . "\n";

        if ($total > 50) {
            // Show first 5 and last 45 lines
            echo implode("\n", array_slice($lines, 0, 5));
            echo "\n... (" . ($total - 50) . " lines omitted) ...\n";
            echo implode("\n", array_slice($lines, -45));
        } else {
            echo $content;
        }

        if (strpos($content, 'DEPLOY_STATUS: SUCCESS') !== false) {
            echo "\n\n*** DEPLOY COMPLETED SUCCESSFULLY ***\n";
        } elseif (strpos($content, 'DEPLOY_STATUS: FAILED') !== false) {
            echo "\n\n*** DEPLOY FAILED ***\n";
        }
    } else {
        echo "No log file found. Deploy may not have started.\n";
    }

} elseif ($action === 'cache') {
    echo "=== Cache Flush ===\n";
    exec("cd " . escapeshellarg($basePath) . " && bin/magento-www cache:flush 2>&1", $output, $exitCode);
    echo implode("\n", $output) . "\n";
    echo "Exit: {$exitCode}\n\n";

    // Verify CSS
    $cssFile = $basePath . '/pub/media/rokanthemes/theme_option/custom_default.css';
    if (file_exists($cssFile)) {
        $css = file_get_contents($cssFile);
        echo "=== CSS Verification ===\n";
        echo "Size: " . strlen($css) . " bytes\n";
        echo "#b73337: " . (strpos($css, '#b73337') !== false ? 'YES' : 'NO') . "\n";
        echo "#8e2629: " . (strpos($css, '#8e2629') !== false ? 'YES' : 'NO') . "\n";
        echo "#333333: " . (strpos($css, '#333333') !== false ? 'YES' : 'NO') . "\n";
        echo "#ff9300: " . (strpos($css, '#ff9300') !== false ? 'YES (OLD!)' : 'NO (good)') . "\n";
        echo "#ff7800: " . (strpos($css, '#ff7800') !== false ? 'YES (OLD!)' : 'NO (good)') . "\n";
    }

} elseif ($action === 'delete') {
    // Delete all temp scripts
    $scripts = ['awa-fix-less.php', 'awa-static-v4.php', 'awa-deploy-bg.php'];
    foreach ($scripts as $s) {
        $f = dirname(__FILE__) . '/' . $s;
        if (file_exists($f)) {
            @unlink($f);
            echo "Deleted: {$s}\n";
        }
    }
    // Delete log files
    @unlink($basePath . '/var/log/awa-deploy.log');
    @unlink($basePath . '/var/log/awa-deploy.pid');
    echo "Cleaned up log files.\n";
    echo "DONE\n";
}

echo '</pre></body></html>';
