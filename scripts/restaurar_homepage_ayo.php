#!/usr/bin/env php
<?php
/**
 * Reconstrói a homepage "home5" com layout limpo, ajusta blocos, define a página padrão
 * e executa reindexações para refletir o conteúdo atualizado.
 */

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Indexer\Model\Indexer\CollectionFactory as IndexerCollectionFactory;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $appState */
$appState = $objectManager->get(State::class);
try {
    $appState->setAreaCode(Area::AREA_ADMINHTML);
} catch (\Exception $exception) {
    // Área já definida durante execuções subsequentes.
}

/** @var BlockRepositoryInterface $blockRepository */
$blockRepository = $objectManager->get(BlockRepositoryInterface::class);
/** @var BlockFactory $blockFactory */
$blockFactory = $objectManager->get(BlockFactory::class);
/** @var PageRepositoryInterface $pageRepository */
$pageRepository = $objectManager->get(PageRepositoryInterface::class);
/** @var PageFactory $pageFactory */
$pageFactory = $objectManager->get(PageFactory::class);
/** @var WriterInterface $configWriter */
$configWriter = $objectManager->get(WriterInterface::class);
/** @var TypeListInterface $cacheTypeList */
$cacheTypeList = $objectManager->get(TypeListInterface::class);
/** @var IndexerCollectionFactory $indexerCollectionFactory */
$indexerCollectionFactory = $objectManager->get(IndexerCollectionFactory::class);
/** @var IndexerRegistry $indexerRegistry */
$indexerRegistry = $objectManager->get(IndexerRegistry::class);

$blocksToUpdate = [
    'home_slider' => [
        'title' => 'Home Slider Principal',
        'content' => <<<'HTML'
<div class="banner-slider banner-slider--home5">
    {{block class="Rokanthemes\SlideBanner\Block\Slider" slider_id="homepageslider" template="slider.phtml"}}
</div>
HTML
    ],
    'list_ads1' => [
        'title' => 'Home Hero Banners Laterais',
        'content' => <<<'HTML'
<div class="ayo-home5-hero-card-stack">
    <a class="ayo-home5-hero-card ayo-home5-hero-card--primary" href="{{store url='colecoes/performance'}}" title="Coleção Performance">
        <img src="{{view url='images/side-banner-promo.svg'}}" alt="Coleção Performance" />
        <span class="ayo-home5-hero-card__content">
            <span class="ayo-home5-hero-card__eyebrow">{{trans "Coleção exclusiva"}}</span>
            <strong class="ayo-home5-hero-card__title">{{trans "Performance"}}</strong>
            <span class="ayo-home5-hero-card__cta">{{trans "Ver coleção"}}</span>
        </span>
    </a>
    <a class="ayo-home5-hero-card ayo-home5-hero-card--secondary" href="{{store url='colecoes/combos'}}" title="Combos com Desconto">
        <img src="{{view url='images/side-banner-combos.svg'}}" alt="Combos com Desconto" />
        <span class="ayo-home5-hero-card__content">
            <span class="ayo-home5-hero-card__eyebrow">{{trans "Ofertas especiais"}}</span>
            <strong class="ayo-home5-hero-card__title">{{trans "Combos"}}</strong>
            <span class="ayo-home5-hero-card__cta">{{trans "Economize agora"}}</span>
        </span>
    </a>
</div>
HTML
    ],
    'top_slideshow_home1' => [
        'title' => 'Home Hero Grid',
        'content' => <<<'HTML'
<div class="ayo-home5-hero-layout">
    <div class="ayo-home5-hero-layout__main">
        {{block class="Magento\Cms\Block\Block" block_id="home_slider"}}
    </div>
    <div class="ayo-home5-hero-layout__side">
        {{block class="Magento\Cms\Block\Block" block_id="list_ads1"}}
    </div>
</div>
HTML
    ],
    'block_top' => [
        'title' => 'Home Benefícios de Serviço',
        'content' => <<<'HTML'
<div class="velaServicesInner velaServicesInner--home5">
    <div class="velaContent">
        <div class="rowFlex rowFlexMargin flexJustifyCenter">
            <div class="col-xs-6 col-sm-3 col-2">
                <div class="boxService d-flex flexJustifyCenter">
                    <div class="boxServiceImage boxServiceImage1"></div>
                    <div class="boxServiceContent">
                        <h4 class="boxServiceTitle">Entrega expressa</h4>
                        <div class="boxServiceDesc">Envio imediato para capitais</div>
                    </div>
                </div>
            </div>
            <div class="col-xs-6 col-sm-3 col-2">
                <div class="boxService d-flex flexJustifyCenter">
                    <div class="boxServiceImage boxServiceImage2"></div>
                    <div class="boxServiceContent">
                        <h4 class="boxServiceTitle">Pagamento seguro</h4>
                        <div class="boxServiceDesc">Cartões, Pix e boleto</div>
                    </div>
                </div>
            </div>
            <div class="col-xs-6 col-sm-3 col-2">
                <div class="boxService d-flex flexJustifyCenter">
                    <div class="boxServiceImage boxServiceImage3"></div>
                    <div class="boxServiceContent">
                        <h4 class="boxServiceTitle">Compra garantida</h4>
                        <div class="boxServiceDesc">Suporte técnico especializado</div>
                    </div>
                </div>
            </div>
            <div class="col-xs-6 col-sm-3 col-2">
                <div class="boxService d-flex flexJustifyCenter">
                    <div class="boxServiceImage boxServiceImage4"></div>
                    <div class="boxServiceContent">
                        <h4 class="boxServiceTitle">Atendimento 24/7</h4>
                        <div class="boxServiceDesc">Equipe pronta para ajudar</div>
                    </div>
                </div>
            </div>
            <div class="col-xs-6 col-sm-3 col-2">
                <div class="boxService d-flex flexJustifyCenter">
                    <div class="boxServiceImage boxServiceImage5"></div>
                    <div class="boxServiceContent">
                        <h4 class="boxServiceTitle">Serviços Pró-Ação</h4>
                        <div class="boxServiceDesc">Troca e devolução sem burocracia</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML
    ],
    'category1_home1' => [
        'title' => 'Home Categoria Destaque 1',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\Categorytab\Block\CateWidget"
        title="Mais vendidos"
        color_box="red-box"
        identify="categorytab"
        category_id="71,72,73,74,75"
        limit_qty="11"
        show_pager="0"
        slide_row="1"
        slide_limit="6"
        template="categorytab/grid.phtml"}}
</div>
HTML
    ],
    'category2_home1' => [
        'title' => 'Home Categoria Destaque 2',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\Categorytab\Block\CateWidget"
        title="Categorias populares"
        color_box="red-box"
        identify="categorytab2"
        category_id="40,44,45,67,68,86"
        limit_qty="10"
        show_pager="0"
        slide_row="1"
        slide_limit="6"
        default="6"
        desktop="5"
        desktop_small="4"
        tablet="3"
        mobile="1"
        template="categorytab/grid-original.phtml"}}
</div>
HTML
    ],
    'featured_categories' => [
        'title' => 'Home Categorias em Destaque',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\Categorytab\Block\CateWidget"
        title=""
        color_box="red-box"
        identify="featured_categories"
        category_id="44,45,67,71,72,73,74,75,76,86,88,109"
        slide_row="2"
        slide_limit="6"
        template="categorytab/catthumbnail.phtml"}}
</div>
HTML
    ],
    'home1_product_thumb' => [
        'title' => 'Home Produtos Mais Buscados',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\Categorytab\Block\CateWidget"
        title=""
        color_box="red-box"
        identify="categorytab_thumb"
        category_id="45,67,71,72,73"
        limit_qty="13"
        show_pager="0"
        slide_row="1"
        slide_limit="6"
        template="categorytab/catthumbnail.phtml"}}
</div>
HTML
    ],
    'home_featured' => [
        'title' => 'Home Produtos em Destaque',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid ayo-home5-product-grid--carousel">
    {{block class="Rokanthemes\Featuredpro\Block\Widget\Featuredpro" template="widget/featuredpro_list.phtml"}}
</div>
HTML
    ],
    'home_new_products' => [
        'title' => 'Home Novos Produtos',
        'content' => <<<'HTML'
<div class="ayo-home5-product-grid ayo-home5-product-grid--carousel">
    {{block class="Rokanthemes\Newproduct\Block\Widget\Newproduct" template="widget/newproduct_list.phtml"}}
</div>
HTML
    ],
    'home_banner_promo' => [
        'title' => 'Home Banner Promocional',
        'content' => <<<'HTML'
<div class="ayo-home5-promo">
    <div class="ayo-home5-promo__inner">
        <span class="ayo-home5-promo__badge">{{trans "Linha exclusiva"}}</span>
        <h2>{{trans "Equipe-se para qualquer pista"}}</h2>
        <p>{{trans "Capacetes, jaquetas e acessórios selecionados com condições especiais para quem vive a estrada."}}</p>
        <a class="action primary ayo-home5-promo__cta" href="{{store url='ofertas'}}">{{trans "Ver ofertas"}}</a>
    </div>
    <div class="ayo-home5-promo__image">
        <img src="{{view url='images/home5/support_icon.png'}}" alt="{{trans "Acessórios de moto"}}" />
    </div>
</div>
HTML
    ],
];

$pageContent = <<<'HTML'
<div class="ayo-home5-wrapper">
    <section class="ayo-home5-section container ayo-home5-section--hero">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Experiência premium</span>
            <h2>Novidades em duas rodas</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="top_slideshow_home1"}}
    </section>

    <section class="ayo-home5-section container">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Benefícios exclusivos</span>
            <h2>Por que comprar com a Awamoto's</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="block_top"}}
    </section>

    <section class="ayo-home5-section container ayo-home5-section--categories">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Categorias em alta</span>
            <h2>Sua próxima aventura começa aqui</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        <div class="ayo-home5-category-rows">
            <div class="ayo-home5-category-column">
                {{block class="Magento\Cms\Block\Block" block_id="category1_home1"}}
            </div>
            <div class="ayo-home5-category-column">
                {{block class="Magento\Cms\Block\Block" block_id="category2_home1"}}
            </div>
        </div>
    </section>

    <section class="ayo-home5-section container">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Coleções</span>
            <h2>Compre por categoria</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="featured_categories"}}
    </section>

    <section class="ayo-home5-section container">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Destaques</span>
            <h2>Escolhas do time</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="home_featured"}}
    </section>

    <section class="ayo-home5-section container ayo-home5-section--promo">
        {{block class="Magento\Cms\Block\Block" block_id="home_banner_promo"}}
    </section>

    <section class="ayo-home5-section container">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Lançamentos</span>
            <h2>Chegou na loja</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="home_new_products"}}
    </section>

    <section class="ayo-home5-section container">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Mais buscados</span>
            <h2>Seleções da comunidade</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="home1_product_thumb"}}
    </section>
</div>

<style type="text/css">
    .ayo-home5-wrapper {
        background-color: #f7f7f7;
        padding: 32px 0 72px;
    }
    .ayo-home5-section {
        margin-bottom: 72px;
    }
    .ayo-home5-section:last-of-type {
        margin-bottom: 0;
    }
    .ayo-home5-heading {
        text-align: center;
        margin-bottom: 32px;
    }
    .ayo-home5-label {
        display: inline-block;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.3em;
        text-transform: uppercase;
        color: #ff6f00;
    }
    .ayo-home5-heading h2 {
        font-size: 34px;
        font-weight: 700;
        margin: 12px 0 0;
        color: #1f1f1f;
    }
    .ayo-home5-divider {
        display: block;
        width: 92px;
        height: 5px;
        background: linear-gradient(90deg, #ff6f00 0%, #ffb300 50%, #ffd54f 100%);
        margin: 20px auto 0;
        border-radius: 999px;
    }
    .ayo-home5-hero-layout {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
        align-items: stretch;
    }
    .ayo-home5-hero-layout__main {
        flex: 1 1 62%;
        min-width: 0;
    }
    .ayo-home5-hero-layout__side {
        flex: 1 1 300px;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .ayo-home5-hero-card-stack {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    .ayo-home5-hero-card {
        position: relative;
        display: block;
        border-radius: 24px;
        overflow: hidden;
        min-height: 260px;
        color: #ffffff;
        text-decoration: none;
        box-shadow: 0 24px 50px rgba(15, 31, 53, 0.22);
        transition: transform 0.35s ease, box-shadow 0.35s ease;
    }
    .ayo-home5-hero-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .ayo-home5-hero-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, 0.72) 100%);
        opacity: 0.9;
        transition: opacity 0.3s ease;
    }
    .ayo-home5-hero-card__content {
        position: absolute;
        inset: auto 26px 28px 26px;
        z-index: 1;
    }
    .ayo-home5-hero-card__eyebrow {
        display: block;
        font-size: 12px;
        letter-spacing: 0.28em;
        text-transform: uppercase;
        opacity: 0.85;
        margin-bottom: 10px;
    }
    .ayo-home5-hero-card__title {
        display: block;
        font-size: 34px;
        line-height: 1.05;
        font-weight: 700;
    }
    .ayo-home5-hero-card__cta {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        font-weight: 600;
        font-size: 15px;
    }
    .ayo-home5-hero-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 28px 60px rgba(15, 31, 53, 0.32);
    }
    .ayo-home5-hero-card:hover::after {
        opacity: 1;
    }
    .velaServicesInner--home5 {
        background: #ffffff;
        border-radius: 24px;
        padding: 36px 24px;
        box-shadow: 0 18px 60px rgba(17, 29, 54, 0.08);
    }
    .ayo-home5-category-rows {
        display: grid;
        gap: 24px;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        align-items: stretch;
    }
    .ayo-home5-category-column > div {
        background: #ffffff;
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 12px 38px rgba(15, 31, 53, 0.08);
    }
    .ayo-home5-product-grid {
        background: #ffffff;
        border-radius: 24px;
        padding: 24px;
        box-shadow: 0 12px 38px rgba(15, 31, 53, 0.08);
    }
    .ayo-home5-product-grid--carousel {
        background: transparent;
        box-shadow: none;
        padding: 0;
    }
    .ayo-home5-section--promo .ayo-home5-promo {
        background: linear-gradient(135deg, #ff6f00 0%, #ff9800 45%, #ffc107 100%);
        border-radius: 28px;
        padding: 48px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        color: #ffffff;
        box-shadow: 0 24px 60px rgba(255, 111, 0, 0.35);
    }
    .ayo-home5-promo__inner {
        max-width: 520px;
    }
    .ayo-home5-promo__badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        font-weight: 600;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-size: 12px;
        margin-bottom: 18px;
    }
    .ayo-home5-promo__inner h2 {
        font-size: 36px;
        line-height: 1.15;
        margin: 0 0 16px;
    }
    .ayo-home5-promo__inner p {
        font-size: 18px;
        margin: 0 0 28px;
        opacity: 0.92;
    }
    .ayo-home5-promo__cta {
        font-size: 16px;
        padding: 14px 28px;
        border-radius: 999px;
        background: #ffffff;
        color: #ff6f00;
    }
    .ayo-home5-promo__image {
        flex: 1 1 200px;
        text-align: center;
    }
    .ayo-home5-promo__image img {
        max-width: 260px;
        width: 100%;
        filter: drop-shadow(0 18px 32px rgba(0, 0, 0, 0.22));
    }
    @media (max-width: 1199px) {
        .ayo-home5-hero-card {
            min-height: 220px;
        }
    }
    @media (max-width: 991px) {
        .ayo-home5-section {
            margin-bottom: 60px;
        }
        .ayo-home5-hero-layout {
            flex-direction: column;
        }
        .ayo-home5-hero-layout__side {
            flex-direction: row;
        }
        .ayo-home5-hero-card-stack {
            flex-direction: row;
        }
        .ayo-home5-section--promo .ayo-home5-promo {
            padding: 40px;
        }
    }
    @media (max-width: 639px) {
        .ayo-home5-wrapper {
            padding: 24px 0 48px;
        }
        .ayo-home5-heading h2 {
            font-size: 26px;
        }
        .ayo-home5-hero-layout__side,
        .ayo-home5-hero-card-stack {
            flex-direction: column;
        }
        .ayo-home5-hero-card {
            min-height: 210px;
        }
        .ayo-home5-product-grid {
            padding: 20px 16px;
            border-radius: 18px;
        }
        .ayo-home5-category-rows {
            grid-template-columns: 1fr;
        }
        .ayo-home5-section--promo .ayo-home5-promo {
            padding: 36px 24px;
            text-align: center;
        }
        .ayo-home5-promo__image {
            order: -1;
            margin-bottom: 24px;
        }
        .ayo-home5-promo__inner h2 {
            font-size: 30px;
        }
    }
</style>
HTML;

$cacheTypesToClean = ['config', 'layout', 'block_html', 'full_page'];
$reindexed = [];
$reindexErrors = [];

try {
    foreach ($blocksToUpdate as $identifier => $data) {
        try {
            $block = $blockRepository->getById($identifier);
        } catch (NoSuchEntityException $exception) {
            $block = $blockFactory->create();
            $block->setIdentifier($identifier);
        }

        $block->setTitle($data['title']);
        $block->setContent($data['content']);
        $block->setIsActive(true);
        $block->setStores([0]);
        $blockRepository->save($block);
    }

    try {
        $page = $pageRepository->getById('homepage_ayo_home5');
    } catch (NoSuchEntityException $exception) {
        $page = $pageFactory->create();
        $page->setIdentifier('homepage_ayo_home5');
    }

    $page->setTitle('Home 5 - Experiência AYO');
    $page->setPageLayout('1column');
    $page->setContentHeading('');
    $page->setContent($pageContent);
    $page->setIsActive(true);
    $page->setStores([0]);
    $pageRepository->save($page);

    $configWriter->save('web/default/cms_home_page', 'homepage_ayo_home5');

    foreach ($cacheTypesToClean as $type) {
        $cacheTypeList->cleanType($type);
    }

    $indexerCollection = $indexerCollectionFactory->create();
    foreach ($indexerCollection as $indexer) {
        $indexerId = $indexer->getId();
        try {
            $indexerRegistry->get($indexerId)->reindexAll();
            $reindexed[] = $indexerId;
        } catch (\Throwable $throwable) {
            $reindexErrors[$indexerId] = $throwable->getMessage();
        }
    }
} catch (\Throwable $throwable) {
    fwrite(STDERR, "[ERRO] Falha ao restaurar homepage: " . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

echo "[OK] Homepage 'home5' atualizada e definida como principal." . PHP_EOL;
echo "[INFO] Blocos atualizados: " . implode(', ', array_keys($blocksToUpdate)) . PHP_EOL;
echo "[INFO] Reindex executado para: " . implode(', ', $reindexed) . PHP_EOL;

if (!empty($reindexErrors)) {
    foreach ($reindexErrors as $indexerId => $message) {
        echo "[WARN] Indexer '{$indexerId}' não concluiu: {$message}" . PHP_EOL;
    }
}
