#!/usr/bin/env php
<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;

require __DIR__ . '/../app/bootstrap.php';

$options = getopt('', [
    'dir::',
    'dry-run::'
]);
$dir = $options['dir'] ?? (__DIR__ . '/../biblioteca/traduzir');
$dryRun = isset($options['dry-run']) && in_array($options['dry-run'], ['1', 'true', 'yes'], true);

if (!is_dir($dir)) {
    fwrite(STDERR, "Diretório não encontrado: {$dir}\n");
    exit(1);
}

$files = glob(rtrim($dir, '/'). '/*.html');
if (!$files) {
    fwrite(STDERR, "Nenhum arquivo .html encontrado em {$dir}\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('adminhtml'); } catch (Exception $e) {}
/** @var BlockRepositoryInterface $blockRepository */
$blockRepository = $om->get(BlockRepositoryInterface::class);
/** @var SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $om->create(SearchCriteriaBuilder::class);
/** @var BlockInterfaceFactory $blockFactory */
$blockFactory = $om->get(BlockInterfaceFactory::class);

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
        fwrite(STDERR, "{$file}: cabeçalho não contém identifier.\n");
        continue;
    }
    $body = preg_replace('/^<!--.*?-->\s*/s', '', $content, 1);
    if ($body === null) { $body = $content; }

    $builder = $om->create(SearchCriteriaBuilder::class);
    $builder->addFilter('identifier', $identifier, 'eq');
    $searchCriteria = $builder->create();
    $items = $blockRepository->getList($searchCriteria)->getItems();
    if (!$items) {
        fwrite(STDOUT, "{$identifier}: bloco não encontrado, criando novo.\n");
        $block = $blockFactory->create();
        $block->setIdentifier($identifier);
    } else {
        $block = reset($items);
    }
    if ($title) {
        $block->setTitle($title);
    }
    $block->setContent($body);
    $block->setIsActive(true);
    $block->setStores([0]);

    $total++;
    if ($dryRun) {
        echo "[DRY-RUN] Atualizaria {$identifier} com conteúdo de {$file}\n";
        continue;
    }
    $blockRepository->save($block);
    echo "Atualizado {$identifier} a partir de {$file}\n";
    $updated++;
}

echo $dryRun ? "Dry-run processou {$total} arquivos.\n" : "Importação concluída. Blocos atualizados: {$updated}/{$total}.\n";
