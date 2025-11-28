#!/usr/bin/env php
<?php
declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;

require __DIR__ . '/../app/bootstrap.php';

$options = getopt('', ['dir::', 'blocks::']);
$defaultBlocks = [
    'footer_info',
    'social_block',
    'footer_menu',
    'home_slider',
    'home_featured',
    'home_new_products',
    'home_banner_promo',
    'top-left-static',
    'head_contact',
    'hotline_header',
    'fixed_right'
];
$targetDir = $options['dir'] ?? (__DIR__ . '/../biblioteca/traduzir');
$blocksInput = $options['blocks'] ?? null;
$identifiers = $blocksInput ? array_filter(array_map('trim', explode(',', $blocksInput))) : $defaultBlocks;
if (!$identifiers) {
    fwrite(STDERR, "Nenhum bloco informado. Use --blocks id1,id2 ou deixe os padrões.\n");
    exit(1);
}
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    fwrite(STDERR, "Não foi possível criar diretório alvo: {$targetDir}\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var State $state */
$state = $om->get(State::class);
try { $state->setAreaCode('adminhtml'); } catch (\Exception $e) {}
/** @var BlockRepositoryInterface $blockRepository */
$blockRepository = $om->get(BlockRepositoryInterface::class);

$total = 0;
foreach ($identifiers as $identifier) {
    /** @var SearchCriteriaBuilder $builder */
    $builder = $om->create(SearchCriteriaBuilder::class);
    $builder->addFilter('identifier', $identifier, 'eq');
    $searchCriteria = $builder->create();
    $items = $blockRepository->getList($searchCriteria)->getItems();
    if (!$items) {
        fwrite(STDERR, "Aviso: bloco {$identifier} não encontrado.\n");
        continue;
    }
    foreach ($items as $block) {
        $filename = $targetDir . '/' . $identifier . '.html';
        $metadata = sprintf("<!-- title: %s | identifier: %s -->\n", $block->getTitle(), $identifier);
        file_put_contents($filename, $metadata . $block->getContent());
        echo "Exportado {$identifier} -> {$filename}\n";
        $total++;
        break;
    }
}

echo "Total exportado: {$total}\n";
