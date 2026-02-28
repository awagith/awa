#!/usr/bin/env php
<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Api\Data\PageInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

require __DIR__ . '/../app/bootstrap.php';

$options = getopt('', ['dir::', 'dry-run::']);
$dir = $options['dir'] ?? (__DIR__ . '/../biblioteca/traduzir/pages');
$dryRun = isset($options['dry-run']) && in_array($options['dry-run'], ['1', 'true', 'yes'], true);

if (!is_dir($dir)) {
    fwrite(STDERR, "Diretório não encontrado: {$dir}\n");
    exit(1);
}
$files = glob(rtrim($dir, '/').'/*.html');
if (!$files) {
    fwrite(STDERR, "Nenhum arquivo .html encontrado em {$dir}\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('adminhtml'); } catch (Exception $e) {}
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $om->get(PageRepositoryInterface::class);
/** @var PageInterfaceFactory $pageFactory */
$pageFactory = $om->get(PageInterfaceFactory::class);

$total = 0; $updated = 0;
foreach ($files as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        fwrite(STDERR, "Falha ao ler {$file}\n");
        continue;
    }
    $firstLine = strtok($content, "\n");
    $matches = [];
    $identifier = null; $title = null;
    if (preg_match('/identifier:\s*([^|>]+)\s*/i', $firstLine, $matches)) {
        $identifier = rtrim(trim($matches[1]), "- ");
    }
    if (preg_match('/title:\s*([^|>]+)\s*/i', $firstLine, $matches)) {
        $title = rtrim(trim($matches[1]), "- ");
    }
    if (!$identifier) {
        fwrite(STDERR, "{$file}: cabeçalho sem identifier.\n");
        continue;
    }
    $body = preg_replace('/^<!--.*?-->\s*/s', '', $content, 1);
    if ($body === null) { $body = $content; }

    try {
        $page = $pageRepository->getById($identifier);
    } catch (Exception $e) {
        $page = $pageFactory->create();
        $page->setIdentifier($identifier);
        $page->setStores([0]);
    }
    if ($title) {
        $page->setTitle($title);
    }
    $page->setContent($body);
    $page->setIsActive(true);
    if (!$page->getPageLayout()) {
        $page->setPageLayout('1column');
    }

    $total++;
    if ($dryRun) {
        echo "[DRY-RUN] Atualizaria página {$identifier} com conteúdo de {$file}\n";
        continue;
    }
    $pageRepository->save($page);
    echo "Atualizada página {$identifier} a partir de {$file}\n";
    $updated++;
}

echo $dryRun ? "Dry-run processou {$total} arquivos.\n" : "Importação de páginas concluída ({$updated}/{$total}).\n";
