#!/usr/bin/env php
<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

require __DIR__ . '/../app/bootstrap.php';

$options = getopt('', ['dir::', 'pages::']);
$defaultPages = ['home', 'about-us', 'privacy-policy-cookie-restriction-mode'];
$targetDir = $options['dir'] ?? (__DIR__ . '/../biblioteca/traduzir/pages');
$pagesInput = $options['pages'] ?? null;
$identifiers = $pagesInput ? array_filter(array_map('trim', explode(',', $pagesInput))) : $defaultPages;
if (!$identifiers) {
    fwrite(STDERR, "Nenhuma página informada.\n");
    exit(1);
}
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Não foi possível criar diretório: {$targetDir}\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('adminhtml'); } catch (Exception $e) {}
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $om->get(PageRepositoryInterface::class);

$total = 0;
foreach ($identifiers as $identifier) {
    try {
        $page = $pageRepository->getById($identifier);
    } catch (Exception $e) {
        fwrite(STDERR, "Página {$identifier} não encontrada: {$e->getMessage()}\n");
        continue;
    }
    $filename = sprintf('%s/%s.html', $targetDir, $identifier);
    $meta = sprintf("<!-- title: %s | identifier: %s -->\n", $page->getTitle(), $page->getIdentifier());
    $content = $meta . $page->getContent();
    file_put_contents($filename, $content);
    echo "Exportado {$identifier} -> {$filename}\n";
    $total++;
}

echo "Total de páginas exportadas: {$total}\n";
