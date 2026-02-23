<?php
declare(strict_types=1);

$token = $_GET['token'] ?? '';
if ($token !== 'awa2026fix') {
    http_response_code(403);
    die('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

set_time_limit(600);
ignore_user_abort(true);
ini_set('max_execution_time', '600');

$action = $_GET['action'] ?? 'fix';
$basePath = dirname(__DIR__);
$lessFile = $basePath . '/app/design/frontend/ayo/ayo_home5/web/css/source/_home5_themes.less';

echo "AWA LESS Fix\n";
echo str_repeat('=', 50) . "\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "Action: {$action}\n\n";

if ($action === 'fix') {
    if (!file_exists($lessFile)) {
        die("ERROR: LESS file not found at: {$lessFile}\n");
    }

    $content = file_get_contents($lessFile);
    $lines = count(explode("\n", $content));
    echo "File: {$lessFile}\n";
    echo "Lines: {$lines}\n";
    echo "Size: " . strlen($content) . " bytes\n\n";

    // The broken code (missing &:before { block)
    $broken = <<<'LESS'
				color: @inputtext;
					width: 50px;
					transform: translateX(-50%);
					-webkit-transform: translateX(-50%);
					-o-transform: translateX(-50%);
					-ms-transform: translateX(-50%);
					height: 2px;
					background: transparent;
				}
LESS;

    // The fixed code (with &:before { restored)
    $fixed = <<<'LESS'
				color: @inputtext;

				&:before {
					content: "";
					position: absolute;
					bottom: 0;
					left: 50%;
					width: 50px;
					transform: translateX(-50%);
					-webkit-transform: translateX(-50%);
					-o-transform: translateX(-50%);
					-ms-transform: translateX(-50%);
					height: 2px;
					background: transparent;
				}
LESS;

    // Check if the broken pattern exists
    if (strpos($content, $broken) !== false) {
        $newContent = str_replace($broken, $fixed, $content);
        $written = file_put_contents($lessFile, $newContent);
        if ($written === false) {
            echo "ERROR: Failed to write!\n";
        } else {
            echo "FIX APPLIED SUCCESSFULLY!\n";
            echo "Written: {$written} bytes\n";

            // Verify
            $verify = file_get_contents($lessFile);
            $newLines = count(explode("\n", $verify));
            echo "New lines: {$newLines}\n";

            $openBraces = substr_count($verify, '{');
            $closeBraces = substr_count($verify, '}');
            echo "Braces: open={$openBraces}, close={$closeBraces}, balance=" . ($openBraces - $closeBraces) . "\n";

            if (strpos($verify, '&:before {') !== false) {
                echo "Verification: &:before { FOUND - OK!\n";
            } else {
                echo "Verification: &:before { NOT FOUND - ERROR!\n";
            }
        }
    } elseif (strpos($content, $fixed) !== false) {
        echo "Already fixed! The &:before block is already present.\n";

        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        echo "Braces: open={$openBraces}, close={$closeBraces}, balance=" . ($openBraces - $closeBraces) . "\n";
    } else {
        echo "WARNING: Neither broken nor fixed pattern found!\n";
        echo "Searching for context...\n";

        // Search for the area
        if (strpos($content, 'color: @inputtext;') !== false) {
            echo "Found: 'color: @inputtext;'\n";
            $pos = strpos($content, 'color: @inputtext;');
            $excerpt = substr($content, $pos - 100, 400);
            echo "Context:\n---\n{$excerpt}\n---\n";
        } elseif (strpos($content, 'color: #888;') !== false) {
            echo "Found: 'color: #888;' (ORIGINAL, color not yet replaced)\n";
        } else {
            echo "Cannot find target area.\n";
        }

        $openBraces = substr_count($content, '{');
        $closeBraces = substr_count($content, '}');
        echo "Braces: open={$openBraces}, close={$closeBraces}, balance=" . ($openBraces - $closeBraces) . "\n";
    }

} elseif ($action === 'deploy') {
    echo "=== Starting Background Deploy ===\n";

    $logFile = $basePath . '/var/log/awa-deploy.log';
    $pidFile = $basePath . '/var/log/awa-deploy.pid';

    // Check if already running
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if (file_exists("/proc/{$pid}")) {
            echo "Deploy already running! PID: {$pid}\n";
            if (file_exists($logFile)) {
                echo "\n=== Current Log (last 30 lines) ===\n";
                $lines = file($logFile);
                echo implode('', array_slice($lines, -30));
            }
            return;
        }
        @unlink($pidFile);
    }

    // Clean view_preprocessed
    $viewDir = $basePath . '/var/view_preprocessed';
    if (is_dir($viewDir)) {
        exec("rm -rf " . escapeshellarg($viewDir) . "/*");
        echo "Cleaned: var/view_preprocessed\n";
    }

    $dvFile = $basePath . '/pub/static/deployed_version.txt';
    if (file_exists($dvFile)) {
        @unlink($dvFile);
        echo "Removed: deployed_version.txt\n";
    }

    // Launch deploy in background
    $cmd = "cd " . escapeshellarg($basePath)
        . " && echo 'DEPLOY STARTED: " . date('Y-m-d H:i:s') . "' > " . escapeshellarg($logFile)
        . " && bin/magento-www setup:static-content:deploy pt_BR -f --no-interaction --jobs 1 >> " . escapeshellarg($logFile) . " 2>&1"
        . " && echo '' >> " . escapeshellarg($logFile)
        . " && echo 'DEPLOY COMPLETED SUCCESSFULLY: '$(date '+%Y-%m-%d %H:%M:%S') >> " . escapeshellarg($logFile)
        . " || echo 'DEPLOY FAILED: '$(date '+%Y-%m-%d %H:%M:%S') >> " . escapeshellarg($logFile)
        . " ; rm -f " . escapeshellarg($pidFile);

    // Use nohup + & to run in background
    $bgCmd = "nohup bash -c " . escapeshellarg($cmd) . " > /dev/null 2>&1 & echo $!";
    $pid = trim(shell_exec($bgCmd));

    file_put_contents($pidFile, $pid);
    echo "Deploy launched in background! PID: {$pid}\n";
    echo "Log file: {$logFile}\n";
    echo "\nCheck progress with: ?action=status\n";

} elseif ($action === 'status') {
    $logFile = $basePath . '/var/log/awa-deploy.log';
    $pidFile = $basePath . '/var/log/awa-deploy.pid';

    echo "=== Deploy Status ===\n";

    $running = false;
    if (file_exists($pidFile)) {
        $pid = trim(file_get_contents($pidFile));
        if (file_exists("/proc/{$pid}")) {
            $running = true;
            echo "Status: RUNNING (PID: {$pid})\n";
        } else {
            echo "Status: FINISHED (PID {$pid} no longer running)\n";
            @unlink($pidFile);
        }
    } else {
        echo "Status: NOT RUNNING\n";
    }

    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $total = count($lines);

        echo "\nLog: {$total} lines\n";
        echo str_repeat('-', 50) . "\n";

        if ($running && $total > 40) {
            echo "... (showing last 40 lines) ...\n";
            echo implode("\n", array_slice($lines, -40));
        } else {
            echo $content;
        }

        // Check for success/failure
        if (strpos($content, 'DEPLOY COMPLETED SUCCESSFULLY') !== false) {
            echo "\n\n*** DEPLOY SUCCESS! ***\n";
        } elseif (strpos($content, 'DEPLOY FAILED') !== false) {
            echo "\n\n*** DEPLOY FAILED ***\n";
        }
    } else {
        echo "No log file found.\n";
    }

} elseif ($action === 'cache') {
    echo "=== Cache Clean ===\n";
    $cmd = "cd " . escapeshellarg($basePath) . " && bin/magento-www cache:flush 2>&1";
    exec($cmd, $output, $exitCode);
    echo implode("\n", $output) . "\n";
    echo "Exit code: {$exitCode}\n\n";

    // Verify CSS
    $cssFile = $basePath . '/pub/media/rokanthemes/theme_option/custom_default.css';
    if (file_exists($cssFile)) {
        $css = file_get_contents($cssFile);
        echo "=== CSS Verification ===\n";
        echo "custom_default.css size: " . strlen($css) . " bytes\n";
        echo "Contains #b73337: " . (strpos($css, '#b73337') !== false ? 'YES' : 'NO') . "\n";
        echo "Contains #8e2629: " . (strpos($css, '#8e2629') !== false ? 'YES' : 'NO') . "\n";
        echo "Contains #333333: " . (strpos($css, '#333333') !== false ? 'YES' : 'NO') . "\n";
        echo "Contains #ff9300: " . (strpos($css, '#ff9300') !== false ? 'YES (OLD!)' : 'NO (GOOD)') . "\n";
        echo "Contains #ff7800: " . (strpos($css, '#ff7800') !== false ? 'YES (OLD!)' : 'NO (GOOD)') . "\n";
    }

} elseif ($action === 'delete') {
    echo "Self-deleting...\n";

    // Also delete awa-static-v4.php
    $v4 = dirname(__FILE__) . '/awa-static-v4.php';
    if (file_exists($v4)) {
        @unlink($v4);
        echo "Deleted: awa-static-v4.php\n";
    }

    @unlink(__FILE__);
    echo "Deleted: " . basename(__FILE__) . "\n";
    echo "DONE\n";
}
