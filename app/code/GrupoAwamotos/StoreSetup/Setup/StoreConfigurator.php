<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreConfigurator
{
    private State $appState;
    private BlockFactory $blockFactory;
    private PageFactory $pageFactory;
    private WriterInterface $configWriter;
    private ReinitableConfigInterface $reinitableConfig;
    private ScopeConfigInterface $scopeConfig;
    private CategoryFactory $categoryFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryRepositoryInterface $categoryRepository;
    private StoreManagerInterface $storeManager;
    private DirectoryList $directoryList;
    private \Rokanthemes\SlideBanner\Model\SliderFactory $sliderFactory;
    private \Rokanthemes\SlideBanner\Model\SlideFactory $slideFactory;

    public function __construct(
        State $appState,
        BlockFactory $blockFactory,
        PageFactory $pageFactory,
        WriterInterface $configWriter,
        ReinitableConfigInterface $reinitableConfig,
        ScopeConfigInterface $scopeConfig,
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList,
        \Rokanthemes\SlideBanner\Model\SliderFactory $sliderFactory,
        \Rokanthemes\SlideBanner\Model\SlideFactory $slideFactory
    ) {
        $this->appState = $appState;
        $this->blockFactory = $blockFactory;
        $this->pageFactory = $pageFactory;
        $this->configWriter = $configWriter;
        $this->reinitableConfig = $reinitableConfig;
        $this->scopeConfig = $scopeConfig;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
        $this->sliderFactory = $sliderFactory;
        $this->slideFactory = $slideFactory;
    }

    public function run(OutputInterface $output): void
    {
        $this->ensureAreaCode();

        $this->createBlocks($output);
        $this->createOrUpdateHomepage($output);
        $this->configureHomepage($output);
        $this->createCategories($output);
        $this->applyThemeConfigurations($output);

        $this->ensurePlaceholderBanners($output);
        $this->seedSlider($output);

        $this->reinitableConfig->reinit();
    }

    private function ensureAreaCode(): void
    {
        try {
            $this->appState->getAreaCode();
        } catch (LocalizedException $e) {
            $this->appState->setAreaCode('adminhtml');
        }
    }

    private function createBlocks(OutputInterface $output): void
    {
        foreach ($this->getBlockDefinitions() as $blockData) {
            try {
                $block = $this->blockFactory->create();
                $block->setStoreId(0);
                $block->load($blockData['identifier'], 'identifier');
                $wasExisting = (bool)$block->getId();

                $block->addData([
                    'title' => $blockData['title'],
                    'identifier' => $blockData['identifier'],
                    'content' => $blockData['content'],
                    'is_active' => 1
                ]);
                $block->setStores([0]);
                $block->save();
                $output->writeln(sprintf(' - Bloco %s %s', $blockData['identifier'], $wasExisting ? 'atualizado' : 'criado'));
            } catch (\Throwable $e) {
                $output->writeln(sprintf('<error>   ✗ Erro ao criar/atualizar bloco %s: %s</error>', $blockData['identifier'], $e->getMessage()));
            }
        }
    }

    private function createOrUpdateHomepage(OutputInterface $output): void
    {
        $page = $this->pageFactory->create();
        $page->setStoreId(0);
        $page->load('home', 'identifier');

        $pageContent = $this->getHomepageContent();

        try {
            if ($page->getId()) {
                $page->setTitle('Home Page');
                $page->setIdentifier('home');
                $page->setContent($pageContent);
                $page->setIsActive(true);
                $page->setPageLayout('1column');
                $page->setStores([0]);
            } else {
                $page->setData([
                    'title' => 'Home Page',
                    'identifier' => 'home',
                    'content' => $pageContent,
                    'is_active' => 1,
                    'page_layout' => '1column'
                ]);
                $page->setStores([0]);
            }

            $page->save();
            $output->writeln(' - Página inicial criada/atualizada');
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>   ✗ Erro na página inicial: %s</error>', $exception->getMessage()));
        }
    }

    private function configureHomepage(OutputInterface $output): void
    {
        try {
            $this->configWriter->save('web/default/cms_home_page', 'home');
            $output->writeln(' - Homepage padrão configurada');
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>   ✗ Erro ao configurar homepage: %s</error>', $exception->getMessage()));
        }
    }

    private function createCategories(OutputInterface $output): void
    {
        $rootCategoryId = (int)$this->storeManager->getStore()->getRootCategoryId();

        foreach ($this->getCategoryDefinitions() as $categoryData) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStoreId(0);
            $collection->addAttributeToFilter('url_key', $categoryData['url_key']);
            $existingCategory = $collection->getFirstItem();

            if ($existingCategory && $existingCategory->getId()) {
                $output->writeln(sprintf(' - Categoria já existe: %s', $categoryData['name']));
                continue;
            }

            try {
                $category = $this->categoryFactory->create();
                $category->setStoreId(0);
                $category->setName($categoryData['name']);
                $category->setUrlKey($categoryData['url_key']);
                $category->setIsActive(true);
                $category->setIncludeInMenu(true);
                $category->setParentId($rootCategoryId);
                $category->setAttributeSetId($category->getDefaultAttributeSetId());
                $category->setIsAnchor(true);

                $this->categoryRepository->save($category);
                $output->writeln(sprintf(' - Categoria criada: %s', $categoryData['name']));
            } catch (\Throwable $exception) {
                $output->writeln(sprintf('<error>   ✗ Erro ao criar categoria %s: %s</error>', $categoryData['name'], $exception->getMessage()));

            }
        }
    }

    private function applyThemeConfigurations(OutputInterface $output): void
    {
        foreach ($this->getThemeConfigurations() as $config) {
            try {
                $this->configWriter->save($config['path'], $config['value']);

                $output->writeln(sprintf(' - Configuração aplicada: %s', $config['path']));
            } catch (\Throwable $exception) {
                $output->writeln(sprintf('<error>   ✗ Erro ao salvar %s: %s</error>', $config['path'], $exception->getMessage()));
            }
        }
    }

    private function getBlockDefinitions(): array
    {
        return [
            [
                'identifier' => 'top-left-static',
                'title' => 'Barra superior - Endereço',
                'content' => $this->topLeftStaticContent()
            ],
            [
                'identifier' => 'head_contact',
                'title' => 'Head Contact',
                'content' => $this->headContactContent()
            ],
            [
                'identifier' => 'hotline_header',
                'title' => 'Hotline Header',
                'content' => $this->hotlineHeaderContent()
            ],
            [
                'identifier' => 'top-contact',
                'title' => 'Top Contact',
                'content' => $this->topContactContent()
            ],
            [
                'identifier' => 'footer_info',
                'title' => 'Footer - Informações',
                'content' => $this->footerInfoContent()
            ],
            [
                'identifier' => 'social_block',
                'title' => 'Redes Sociais',
                'content' => $this->socialBlockContent()
            ],
            [
                'identifier' => 'footer_menu',
                'title' => 'Footer - Menu',
                'content' => $this->footerMenuContent()
            ],
            [
                'identifier' => 'footer_static',
                'title' => 'Footer - Conteúdo principal',
                'content' => $this->footerStaticContent()
            ],
            [
                'identifier' => 'footer_payment',
                'title' => 'Footer - Pagamentos',
                'content' => $this->footerPaymentContent()
            ],
            [
                'identifier' => 'fixed_right',
                'title' => 'Atalhos Flutuantes',
                'content' => $this->fixedRightContent()
            ],
            [
                'identifier' => 'home_slider',
                'title' => 'Home - Slider Principal',
                'content' => $this->homeSliderContent()
            ],
            [
                'identifier' => 'home_fitment',
                'title' => 'Home - Busca por Aplicação',
                'content' => $this->homeFitmentContent()
            ],
            [
                'identifier' => 'home_featured',
                'title' => 'Home - Produtos em Destaque',
                'content' => $this->homeFeaturedContent()
            ],
            [
                'identifier' => 'home_new_products',
                'title' => 'Home - Novos Produtos',
                'content' => $this->homeNewProductsContent()
            ],
            [
                'identifier' => 'home_banner_promo',
                'title' => 'Home - Banner Promocional',
                'content' => $this->homeBannerPromoContent()
            ],
            [
                'identifier' => 'top_slideshow_home1',
                'title' => 'Home 1 - Slider + Banners',
                'content' => $this->homeTopSlideshowContent()
            ],
            [
                'identifier' => 'list_ads1',
                'title' => 'Home 1 - Banners Laterais',
                'content' => $this->homeListAdsContent()
            ],
            [
                'identifier' => 'block_top',
                'title' => 'Home - Benefícios superiores',
                'content' => $this->homeBenefitsContent()
            ],
            [
                'identifier' => 'category1_home1',
                'title' => 'Home 1 - Categorias destaque 1',
                'content' => $this->homeCategory1Content()
            ],
            [
                'identifier' => 'category2_home1',
                'title' => 'Home 1 - Categorias destaque 2',
                'content' => $this->homeCategory2Content()
            ],
            [
                'identifier' => 'featured_categories',
                'title' => 'Home 1 - Compre por categoria',
                'content' => $this->homeFeaturedCategoriesContent()
            ],
            [
                'identifier' => 'home1_product_thumb',
                'title' => 'Home 1 - Produtos com imagem',
                'content' => $this->homeProductThumbContent()
            ],
            [
                'identifier' => 'trust_badges_homepage',
                'title' => 'Home - Trust Badges (Selo de Confiança)',
                'content' => $this->trustBadgesHomepageContent()
            ],
            [
                'identifier' => 'home_testimonials',
                'title' => 'Home - Depoimentos de Clientes',
                'content' => $this->homeTestimonialsContent()
            ]
        ];
    }

    private function getCategoryDefinitions(): array
    {
        return [
            ['name' => 'Eletrônicos', 'url_key' => 'eletronicos'],
            ['name' => 'Moda', 'url_key' => 'moda'],
            ['name' => 'Casa e Decoração', 'url_key' => 'casa-decoracao'],
            ['name' => 'Esportes', 'url_key' => 'esportes']
        ];
    }

    private function getThemeConfigurations(): array
    {
        return [
            // Header layout & visibility (added for idempotence of Ayo header preset)
            ['path' => 'themeoption/header/header_type', 'value' => '5'],
            ['path' => 'themeoption/header/show_hotline', 'value' => '1'],
            ['path' => 'themeoption/header/show_search', 'value' => '1'],
            ['path' => 'themeoption/header/search_enable', 'value' => '1'],
            ['path' => 'themeoption/header/show_account', 'value' => '1'],
            ['path' => 'themeoption/header/show_minicart', 'value' => '1'],
            ['path' => 'themeoption/header/show_wishlist', 'value' => '1'],
            ['path' => 'themeoption/header/show_compare', 'value' => '0'],
            ['path' => 'themeoption/general/layout', 'value' => 'full_width'],
            ['path' => 'themeoption/header/sticky_enable', 'value' => '1'],
            ['path' => 'themeoption/header/sticky_select_bg_color', 'value' => 'custom'],
            ['path' => 'themeoption/header/sticky_bg_color_custom', 'value' => '#ffffff'],
            ['path' => 'themeoption/footer/footer_menu_mobile', 'value' => '1'],
            ['path' => 'themeoption/fake_order/enable_f_o', 'value' => '0'],
            ['path' => 'themeoption/newsletter/enable', 'value' => '1'],
            ['path' => 'themeoption/newsletter/content', 'value' => $this->newsletterPopupContent()],
            ['path' => 'themeoption/newsletter/width', 'value' => '580'],
            ['path' => 'themeoption/newsletter/height', 'value' => '520'],
            ['path' => 'themeoption/newsletter/bg_color', 'value' => '#ffffff'],
            ['path' => 'themeoption/newsletter/bg_custom_style', 'value' => 'padding:0;'],
            ['path' => 'rokanthemes_themeoption/newsletter_popup/enable', 'value' => '1'],
            ['path' => 'rokanthemes_themeoption/newsletter_popup/delay', 'value' => '30000'], // 30 segundos
            ['path' => 'rokanthemes_themeoption/newsletter_popup/cookie_lifetime', 'value' => '30'], // 30 dias
            ['path' => 'rokanthemes_themeoption/newsletter_popup/width', 'value' => '580'],
            ['path' => 'rokanthemes_themeoption/newsletter_popup/height', 'value' => '520'],
            ['path' => 'producttab/new_status/enabled', 'value' => '1'],
            ['path' => 'producttab/new_status/items', 'value' => '5'],
            ['path' => 'producttab/new_status/row', 'value' => '1'],
            ['path' => 'producttab/new_status/speed', 'value' => '400'],
            ['path' => 'producttab/new_status/qty', 'value' => '20'],
            ['path' => 'producttab/new_status/addtocart', 'value' => '1'],
            ['path' => 'producttab/new_status/wishlist', 'value' => '1'],
            ['path' => 'producttab/new_status/compare', 'value' => '0'],
            ['path' => 'producttab/new_status/navigation', 'value' => '1'],
            ['path' => 'producttab/new_status/pagination', 'value' => '0'],
            ['path' => 'producttab/new_status/auto', 'value' => '1'],
            ['path' => 'producttab/new_status/shownew', 'value' => '1'],
            ['path' => 'producttab/new_status/newname', 'value' => 'Lançamentos'],
            ['path' => 'producttab/new_status/showbestseller', 'value' => '1'],
            ['path' => 'producttab/new_status/bestsellername', 'value' => 'Mais vendidos'],
            ['path' => 'producttab/new_status/showfeature', 'value' => '1'],
            ['path' => 'producttab/new_status/featurename', 'value' => 'Destaques'],
            ['path' => 'producttab/new_status/showonsale', 'value' => '1'],
            ['path' => 'producttab/new_status/onsalename', 'value' => 'Promoções'],
            ['path' => 'producttab/new_status/showrandom', 'value' => '0'],
            ['path' => 'producttab/new_status/randomname', 'value' => 'Descubra também'],
            ['path' => 'rokanthemes_custommenu/general/enable', 'value' => '1'],
            ['path' => 'rokanthemes_quickview/general/enable', 'value' => '1'],
            ['path' => 'rokanthemes_ajaxsuite/general/ajaxcart_enable', 'value' => '1'],
            ['path' => 'rokanthemes_ajaxsuite/general/ajaxcompare_enable', 'value' => '1'],
            ['path' => 'rokanthemes_ajaxsuite/general/ajaxwishlist_enable', 'value' => '1']
        ];
    }

    private function homeFitmentContent(): string
    {
        $enabled = (string)$this->scopeConfig->getValue('grupoawamotos_fitment/general/enable') === '1';
        $placeholder = (string)($this->scopeConfig->getValue('grupoawamotos_fitment/general/placeholder') ?: 'Ex.: Honda CG 160 2022');
        $hint = (string)($this->scopeConfig->getValue('grupoawamotos_fitment/general/hint') ?: 'Dica: use marca + modelo + ano para resultados mais precisos.');

        if (!$enabled) {
            return '<div class="ayo-home5-fitment" style="display:none"></div>';
        }

        $placeholderEsc = htmlspecialchars($placeholder, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hintEsc = htmlspecialchars($hint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<div class="ayo-home5-fitment">
    <div class="ayo-home5-fitment__box">
        <div class="ayo-home5-fitment__intro">
            <p>Busque por modelo, ano e marca para achar compatibilidades.</p>
        </div>
        <form class="ayo-home5-fitment__form" action="{{store url='catalogsearch/result'}}" method="get">
            <div class="ayo-home5-fitment__fields">
                <input type="text" name="q" placeholder="{$placeholderEsc}" aria-label="Buscar por aplicação" required />
                <button class="action primary" type="submit">Buscar</button>
            </div>
            <small class="ayo-home5-fitment__hint">{$hintEsc}</small>
        </form>
    </div>
    <style>
        .ayo-home5-fitment__box{background:#fff;border-radius:24px;padding:24px;box-shadow:0 12px 38px rgba(15,31,53,.08)}
        .ayo-home5-fitment__fields{display:flex;gap:12px}
        .ayo-home5-fitment__fields input{flex:1 1 auto;height:44px;padding:0 14px;border:1px solid #ddd;border-radius:12px}
        .ayo-home5-fitment__fields button{height:44px;padding:0 18px;border-radius:12px}
        .ayo-home5-fitment__hint{display:block;margin-top:8px;opacity:.75}
    </style>
</div>
HTML;
    }

    private function headContactContent(): string
    {
        return <<<HTML
<div class="head-contact">Atendimento: (11) 4002-8922 • suporte@grupoawamotos.com.br</div>
HTML;
    }

    private function topLeftStaticContent(): string
    {
        return <<<HTML
<div class="top-left-static">
    <span class="address">Av. Paulista, 1000 - Bela Vista, São Paulo/SP</span>
    <span class="separator">•</span>
    <span class="hours">Seg a Sex: 9h às 18h</span>
    <span class="separator">•</span>
    <a class="store-link" href="{{store url='contact'}}">Fale conosco</a>
</div>
HTML;
    }

    private function hotlineHeaderContent(): string
    {
        return <<<HTML
<div class="hoteline_header">
    <div class="site-secure-badge">
        <i class="fa fa-lock" aria-hidden="true"></i>
        <span>Compra Segura</span>
    </div>
    <div class="image_hotline"></div>
    <div class="wrap">
        <label>Central 24h:</label>
        <span>(11) 4002-8922</span>
    </div>
</div>
<style>
.site-secure-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: #28a745;
    color: #ffffff;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    margin-right: 15px;
}
.site-secure-badge i {
    font-size: 14px;
}
@media (max-width: 768px) {
    .site-secure-badge {
        font-size: 11px;
        padding: 4px 8px;
        margin-right: 10px;
    }
    .site-secure-badge span {
        display: none;
    }
}
</style>
HTML;
    }

    private function topContactContent(): string
    {
        return <<<HTML
<div class="top-contact">
    <div class="phone">
        <span class="label">Central telefônica</span>
        <a href="tel:1140028922">(11) 4002-8922</a>
    </div>
    <div class="email">
        <span class="label">Atendimento por e-mail</span>
        <a href="mailto:suporte@grupoawamotos.com.br">suporte@grupoawamotos.com.br</a>
    </div>
</div>
HTML;
    }

    private function footerInfoContent(): string
    {
        return <<<HTML
<div class="footer-info">
    <h4>Sobre Nossa Loja</h4>
    <p>Loja completa com tema Ayo Magento 2</p>
    <p>Endereço: Rua Exemplo, 123 - São Paulo, SP</p>
    <p>Telefone: (11) 1234-5678</p>
</div>
HTML;
    }

    private function socialBlockContent(): string
    {
        return <<<HTML
<div class="social-links">
    <a href="#" class="facebook">Facebook</a>
    <a href="#" class="instagram">Instagram</a>
    <a href="#" class="twitter">Twitter</a>
</div>
HTML;
    }

    private function footerMenuContent(): string
    {
        return <<<HTML
<div class="footer-menu">
    <h4>Links Úteis</h4>
    <ul>
        <li><a href="{{store url="about-us"}}">Sobre Nós</a></li>
        <li><a href="{{store url="customer/account"}}">Minha Conta</a></li>
        <li><a href="{{store url="contact"}}">Contato</a></li>
    </ul>
</div>
HTML;
    }

    private function footerStaticContent(): string
    {
        return <<<HTML
<div class="velaNewsletterFooter">
    <div class="velaNewsletterInner clearfix">
        <h4 class="velaFooterTitle">Assine e receba novidades</h4>
        <div class="velaContent">
            <div class="newsletterDescription">
                Receba lançamentos, ofertas exclusivas e conteúdos técnicos sobre performance para motos e scooters.
            </div>
            {{block class="Magento\Newsletter\Block\Subscribe" template="subscribe.phtml"}}
        </div>
    </div>
</div>
<div class="container">
    <div class="rowFlex rowFlexMargin">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="vela-contactinfo velaBlock">
                <div class="vela-content">
                    <div class="contacinfo-logo clearfix">
                        <div class="velaFooterLogo"><a href="{{store url=''}}" title="Grupo Awamotos">Grupo Awamotos</a></div>
                    </div>
                    <div class="intro-footer d-flex">
                        Especialistas em peças, acessórios e serviços premium para o mercado brasileiro de duas rodas.
                    </div>
                    <div class="contacinfo-phone contactinfo-item clearfix">
                        <div class="d-flex">
                            <div class="image_hotline"></div>
                            <div class="wrap"><label>Central 24/7:</label>(11) 4002-8922</div>
                        </div>
                    </div>
                    <div class="contacinfo-address contactinfo-item d-flex"><label>Endereço:</label>Av. Paulista, 1000 - Bela Vista, São Paulo/SP</div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="rowFlex rowFlexMargin">
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Institucional</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a href="{{store url='about-us'}}">Sobre nós</a></li>
                                <li><a href="{{store url='customer/account'}}">Minha conta</a></li>
                                <li><a href="{{store url='contact'}}">Contato</a></li>
                                <li><a href="{{store url='privacy-policy-cookie-restriction-mode'}}">Privacidade</a></li>
                                <li><a href="{{store url='sales/guest/form'}}">Rastrear pedido</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Ajuda rápida</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a href="{{store url='customer-service'}}">Atendimento</a></li>
                                <li><a href="{{store url='faq'}}">FAQ</a></li>
                                <li><a href="{{store url='returns'}}">Trocas e devoluções</a></li>
                                <li><a href="{{store url='warranty'}}">Garantia</a></li>
                                <li><a href="{{store url='store-locator'}}">Lojas parceiras</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="rowFlex rowFlexMargin">
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Minha conta</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a href="{{store url='wishlist'}}">Lista de desejos</a></li>
                                <li><a href="{{store url='checkout/cart'}}">Carrinho</a></li>
                                <li><a href="{{store url='customer/account/login'}}">Login</a></li>
                                <li><a href="{{store url='customer/account/create'}}">Criar conta</a></li>
                                <li><a href="{{store url='newsletter/manage'}}">Preferências de e-mail</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Redes sociais</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a href="https://www.facebook.com" target="_blank" rel="noopener">Facebook</a></li>
                                <li><a href="https://www.instagram.com" target="_blank" rel="noopener">Instagram</a></li>
                                <li><a href="https://www.youtube.com" target="_blank" rel="noopener">YouTube</a></li>
                                <li><a href="https://www.linkedin.com" target="_blank" rel="noopener">LinkedIn</a></li>
                                <li><a href="https://www.tiktok.com" target="_blank" rel="noopener">TikTok</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    private function footerPaymentContent(): string
    {
        return <<<HTML
<div class="payment-method">
    <img alt="Pagamentos" src="{{media url='wysiwyg/payment/pagamentos.svg'}}" style="max-width: 100%; height:auto;" />
</div>
HTML;
    }

    private function newsletterPopupContent(): string
    {
        return <<<HTML
<div class="ayo-newsletter-popup">
    <div class="newsletter-popup-badge">
        <i class="fa fa-gift"></i>
    </div>
    <h3 class="newsletter-popup-title">GANHE 10% OFF</h3>
    <p class="newsletter-popup-subtitle">Na sua primeira compra!</p>
    <p class="newsletter-popup-description">Cadastre seu e-mail e receba um cupom exclusivo + novidades sobre equipamentos e acessórios para motos.</p>
    {{block class="Magento\\Newsletter\\Block\\Subscribe" template="subscribe.phtml"}}
    <p class="newsletter-popup-privacy">
        <small>Seus dados estão seguros. Não compartilhamos com terceiros.</small>
    </p>
</div>
<style>
.ayo-newsletter-popup {
    position: relative;
    text-align: center;
    padding: 40px 30px;
}
.newsletter-popup-badge {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: linear-gradient(135deg, #b73337 0%, #8b2629 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(183, 51, 55, 0.3);
}
.newsletter-popup-badge i {
    font-size: 40px;
    color: #ffffff;
}
.newsletter-popup-title {
    font-size: 32px;
    font-weight: 700;
    color: #b73337;
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.newsletter-popup-subtitle {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 15px;
}
.newsletter-popup-description {
    font-size: 14px;
    color: #666;
    line-height: 1.6;
    margin: 0 0 25px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}
.newsletter-popup-privacy {
    margin-top: 15px;
    color: #999;
}
.newsletter-popup-privacy small {
    font-size: 11px;
}
.ayo-newsletter-popup .block.newsletter .field.newsletter {
    margin: 0 auto;
    max-width: 400px;
}
.ayo-newsletter-popup .block.newsletter .actions {
    margin-top: 15px;
}
.ayo-newsletter-popup .block.newsletter button {
    background: #b73337;
    border-color: #b73337;
    padding: 12px 40px;
    font-size: 16px;
    font-weight: 600;
    text-transform: uppercase;
    border-radius: 6px;
    transition: all 0.3s ease;
}
.ayo-newsletter-popup .block.newsletter button:hover {
    background: #8b2629;
    border-color: #8b2629;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(183, 51, 55, 0.3);
}
</style>
HTML;
    }

    private function homeSliderContent(): string
    {
        return <<<HTML
<div class="banner-slider banner-slider--home5">
    <a href="{{store url='eletronicos'}}" class="banner-hero-link" title="Eletrônicos em Destaque">
        <picture>
            <source srcset="{{media url='import/home/hero.webp'}}" type="image/webp" />
            <img
                src="{{media url='import/home/banner-hero.svg'}}"
                alt="Eletrônicos em Destaque"
                loading="lazy"
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 100vw, 1600px"
                srcset="{{media url='import/home/hero.webp'}} 1600w"
                style="width:100%;height:auto;border-radius:24px;box-shadow:0 24px 50px rgba(15,31,53,.22)"
            />
        </picture>
    </a>
</div>
HTML;
    }

    private function homeFeaturedContent(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid ayo-home5-product-grid--carousel" aria-label="Produtos em Destaque">
    {{widget type="Rokanthemes\\Featuredpro\\Block\\Widget\\Featuredpro"
        template="widget/featuredpro_list.phtml"
        limit="12"
        row="1"
        navigation="1"
        pagination="0"}}
</div>
HTML;
    }

    private function homeNewProductsContent(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid ayo-home5-product-grid--carousel" aria-label="Novos Produtos">
    {{widget type="Rokanthemes\\Newproduct\\Block\\Widget\\Newproduct"
        template="widget/newproduct_list.phtml"
        limit="12"
        row="1"
        navigation="1"
        pagination="0"}}
</div>
HTML;
    }

    private function homeBannerPromoContent(): string
    {
        return <<<HTML
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
HTML;
    }

    private function homeTopSlideshowContent(): string
    {
        return <<<HTML
<div class="ayo-home5-hero-layout">
    <div class="ayo-home5-hero-layout__main">
        {{block class="Magento\\Cms\\Block\\Block" block_id="home_slider"}}
    </div>
    <div class="ayo-home5-hero-layout__side">
        <a class="ayo-home5-hero-card ayo-home5-hero-card--primary" href="{{store url='moda'}}" title="Moda">
            <picture>
                <source srcset="{{media url='import/home/side1.webp'}}" type="image/webp" />
                <img src="{{media url='import/home/banner-side-1.svg'}}" alt="Moda" loading="lazy" sizes="(max-width: 768px) 100vw, 800px" srcset="{{media url='import/home/side1.webp'}} 800w" />
            </picture>
            <span class="ayo-home5-hero-card__content">
                <span class="ayo-home5-hero-card__eyebrow">Coleção exclusiva</span>
                <strong class="ayo-home5-hero-card__title">Moda</strong>
                <span class="ayo-home5-hero-card__cta">Ver novidades</span>
            </span>
        </a>
        <a class="ayo-home5-hero-card ayo-home5-hero-card--secondary" href="{{store url='esportes'}}" title="Esportes">
            <picture>
                <source srcset="{{media url='import/home/side2.webp'}}" type="image/webp" />
                <img src="{{media url='import/home/banner-side-2.svg'}}" alt="Esportes" loading="lazy" sizes="(max-width: 768px) 100vw, 800px" srcset="{{media url='import/home/side2.webp'}} 800w" />
            </picture>
            <span class="ayo-home5-hero-card__content">
                <span class="ayo-home5-hero-card__eyebrow">Ofertas especiais</span>
                <strong class="ayo-home5-hero-card__title">Esportes</strong>
                <span class="ayo-home5-hero-card__cta">Explorar agora</span>
            </span>
        </a>
    </div>
</div>
HTML;
    }

    private function homeListAdsContent(): string
    {
        return <<<HTML
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
HTML;
    }

    private function homeBenefitsContent(): string
    {
        return <<<HTML
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
HTML;
    }

    private function homeCategory1Content(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\\Categorytab\\Block\\CateWidget"
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
HTML;
    }

    private function homeCategory2Content(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\\Categorytab\\Block\\CateWidget"
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
HTML;
    }

    private function homeFeaturedCategoriesContent(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\\Categorytab\\Block\\CateWidget"
        title=""
        color_box="red-box"
        identify="featured_categories"
        category_id="44,45,67,71,72,73,74,75,76,86,88,109"
        slide_row="2"
        slide_limit="6"
        template="categorytab/grid-original.phtml"}}
</div>
HTML;
    }

    private function homeProductThumbContent(): string
    {
        return <<<HTML
<div class="ayo-home5-product-grid">
    {{widget type="Rokanthemes\\Categorytab\\Block\\CateWidget"
        title=""
        color_box="red-box"
        identify="categorytab_thumb"
        category_id="45,67,71,72,73"
        limit_qty="13"
        show_pager="0"
        slide_row="1"
        slide_limit="6"
        template="categorytab/grid-original.phtml"}}
</div>
HTML;
    }

    private function getHomepageContent(): string
    {
        return <<<'HTML'
<div class="ayo-home5-wrapper">
    <section class="ayo-home5-section container ayo-home5-section--hero">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Experiência premium</span>
            <h2>Novidades em duas rodas</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="top_slideshow_home1"}}
    </section>

    <section class="ayo-home5-section ayo-home5-section--trust-badges">
        {{block class="Magento\Cms\Block\Block" block_id="trust_badges_homepage"}}
    </section>

    <section class="ayo-home5-section container ayo-home5-section--fitment">
        <header class="ayo-home5-heading">
            <span class="ayo-home5-label">Encontre a peça certa</span>
            <h2>Busca por aplicação</h2>
            <span class="ayo-home5-divider"></span>
        </header>
        {{block class="Magento\Cms\Block\Block" block_id="home_fitment"}}
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

    <section class="ayo-home5-section ayo-home5-section--testimonials">
        {{block class="Magento\Cms\Block\Block" block_id="home_testimonials"}}
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
    }

    

    private function ensurePlaceholderBanners(OutputInterface $output): void
    {
        try {
            $mediaDir = rtrim($this->directoryList->getPath(DirectoryList::MEDIA), '/');
            $sliderDir = $mediaDir . '/slidebanner';
            if (!is_dir($sliderDir)) {
                @mkdir($sliderDir, 0755, true);
            }

            for ($i = 1; $i <= 3; $i++) {
                $file = $sliderDir . "/banner{$i}.svg";
                if (!file_exists($file)) {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="520">'
                        . '<rect width="100%" height="100%" fill="#f2f2f2"/>'
                        . '<text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-size="42" fill="#333">'
                        . "Banner {$i} Placeholder"
                        . '</text></svg>';
                    @file_put_contents($file, $svg);
                }
            }
            // pagamentos sprite simples
            $paymentDir = $mediaDir . '/wysiwyg/payment';
            if (!is_dir($paymentDir)) {
                @mkdir($paymentDir, 0755, true);
            }
            $paymentFile = $paymentDir . '/pagamentos.svg';
            if (!file_exists($paymentFile)) {
                $svgPay = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="60">'
                    . '<rect width="100%" height="100%" fill="#ffffff"/>'
                    . '<g font-family="sans-serif" font-size="20" font-weight="700" fill="#333">'
                    . '<text x="20" y="38">PIX</text>'
                    . '<text x="90" y="38">BOLETO</text>'
                    . '<text x="210" y="38">VISA</text>'
                    . '<text x="290" y="38">MASTERCARD</text>'
                    . '<text x="440" y="38">AMEX</text>'
                    . '</g></svg>';
                @file_put_contents($paymentFile, $svgPay);
            }

            $output->writeln(' - Placeholders de banners e pagamentos verificados/criados');
        } catch (\Throwable $e) {
            $output->writeln('<error>   ✗ Falha ao criar placeholders de banners: ' . $e->getMessage() . '</error>');
        }
    }

    private function fixedRightContent(): string
    {
        return <<<HTML
<div class="fixed-right-links">
    <ul class="list-unstyled">
        <li><a class="fixed-call" href="tel:1140028922" title="Ligar"><span>Ligação</span></a></li>
        <li><a class="fixed-whatsapp" href="https://wa.me/551140028922" target="_blank" rel="noopener" title="WhatsApp"><span>WhatsApp</span></a></li>
        <li><a class="fixed-email" href="mailto:suporte@grupoawamotos.com.br" title="E-mail"><span>E-mail</span></a></li>
        <li><a class="fixed-top" href="#top" title="Topo"><span>Topo</span></a></li>
    </ul>
</div>
HTML;
    }

    private function homeTestimonialsContent(): string
    {
        return <<<HTML
<div class="testimonials-wrapper">
    <div class="testimonials-header">
        <h2>O Que Nossos Clientes Dizem</h2>
        <p>Mais de 10.000 motociclistas confiam em nossos produtos</p>
    </div>
    {{widget type="Rokanthemes\Testimonials\Block\Testimonials" 
        template="rokanthemes/testimonials/slider.phtml"
        identify="home_testimonials"
        title="O Que Nossos Clientes Dizem"
        qty="10"}}
</div>
<style>
.testimonials-wrapper {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 60px 0;
    margin: 40px 0;
}
.testimonials-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 15px;
}
.testimonials-header h2 {
    font-size: 32px;
    font-weight: 700;
    color: #333;
    margin-bottom: 10px;
}
.testimonials-header p {
    font-size: 16px;
    color: #666;
}
.testimonials-wrapper .item-testimonial {
    background: #ffffff;
    border-radius: 12px;
    padding: 30px;
    margin: 0 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.testimonials-wrapper .item-testimonial:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}
.testimonials-wrapper .testimonial-rating {
    color: #ffc107;
    font-size: 18px;
    margin-bottom: 15px;
}
.testimonials-wrapper .testimonial-content {
    font-size: 15px;
    line-height: 1.6;
    color: #555;
    margin-bottom: 20px;
    font-style: italic;
}
.testimonials-wrapper .testimonial-author {
    display: flex;
    align-items: center;
    gap: 15px;
}
.testimonials-wrapper .testimonial-author-image {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #b73337;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 600;
}
.testimonials-wrapper .testimonial-author-info strong {
    display: block;
    font-size: 16px;
    color: #333;
    margin-bottom: 2px;
}
.testimonials-wrapper .testimonial-author-info span {
    font-size: 13px;
    color: #888;
}
@media (max-width: 768px) {
    .testimonials-header h2 {
        font-size: 24px;
    }
    .testimonials-wrapper .item-testimonial {
        margin: 0 10px;
        padding: 20px;
    }
}
</style>
HTML;
    }

    private function trustBadgesHomepageContent(): string
    {
        return <<<HTML
<div class="trust-badges-wrapper">
    <div class="trust-badges-container">
        <div class="trust-badge">
            <div class="trust-badge__icon">
                <i class="fa fa-shield" aria-hidden="true"></i>
            </div>
            <div class="trust-badge__content">
                <strong>Compra Segura SSL</strong>
                <span>Seus dados protegidos</span>
            </div>
        </div>
        <div class="trust-badge">
            <div class="trust-badge__icon">
                <i class="fa fa-credit-card" aria-hidden="true"></i>
            </div>
            <div class="trust-badge__content">
                <strong>Pagamento Protegido</strong>
                <span>PIX, Boleto e Cartão</span>
            </div>
        </div>
        <div class="trust-badge">
            <div class="trust-badge__icon">
                <i class="fa fa-truck" aria-hidden="true"></i>
            </div>
            <div class="trust-badge__content">
                <strong>Frete Grátis</strong>
                <span>Acima de R\$ 199</span>
            </div>
        </div>
        <div class="trust-badge">
            <div class="trust-badge__icon">
                <i class="fa fa-exchange" aria-hidden="true"></i>
            </div>
            <div class="trust-badge__content">
                <strong>Troca Facilitada</strong>
                <span>Em até 7 dias</span>
            </div>
        </div>
    </div>
</div>
<style>
.trust-badges-wrapper {
    background: #f8f8f8;
    padding: 30px 0;
    margin: 40px 0;
}
.trust-badges-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}
.trust-badge {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.trust-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.trust-badge__icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #b73337;
    border-radius: 50%;
    color: #ffffff;
}
.trust-badge__icon i {
    font-size: 24px;
}
.trust-badge__content {
    display: flex;
    flex-direction: column;
}
.trust-badge__content strong {
    font-size: 16px;
    color: #333;
    margin-bottom: 4px;
}
.trust-badge__content span {
    font-size: 13px;
    color: #666;
}
@media (max-width: 768px) {
    .trust-badges-container {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    .trust-badge {
        padding: 15px;
    }
}
</style>
HTML;
    }

    private function seedSlider(OutputInterface $output): void
    {
        try {
            $identifier = 'homepageslider';
            $slider = $this->sliderFactory->create();
            // load by identifier (resource model supports non-numeric via slider_identifier)
            $slider->load($identifier);

            $wasExisting = (bool)$slider->getId();
            if (!$wasExisting) {
                $slider->setData([
                    'slider_identifier' => $identifier,
                    'slider_title' => 'Homepage Slider',
                    'slider_status' => 1,
                    'store_ids' => json_encode([0]),
                    'slider_setting' => null,
                    'slider_styles' => null,
                    'slider_script' => null,
                    'slider_template' => null,
                ]);
                $slider->save();
            }

            // Ensure we have three basic slides
            $sliderId = (int)$slider->getId();
            if ($sliderId <= 0) {
                // If still no ID, nothing to do
                return;
            }

            $existingSlides = $this->slideFactory->create()->getCollection();
            $existingSlides->addFieldToFilter('slider_id', $sliderId);
            if ($existingSlides->getSize() >= 3) {
                $output->writeln(' - Slider já possui slides suficientes');
                return;
            }

            $links = [
                '{{store url="colecoes/performance"}}',
                '{{store url="colecoes/urbanas"}}',
                '{{store url="promocoes"}}',
            ];
            for ($i = 1; $i <= 3; $i++) {
                $slide = $this->slideFactory->create();
                $slide->setData([
                    'slider_id' => $sliderId,
                    'slide_type' => 1,
                    'slide_text' => 'Banner ' . $i,
                    'slide_image' => 'slidebanner/banner' . $i . '.svg',
                    'slide_image_mobile' => 'slidebanner/banner' . $i . '.svg',
                    'slide_link' => $links[$i-1] ?? '{{store url=""}}',
                    'slide_status' => 1,
                    'slide_position' => $i,
                ]);
                $slide->save();
            }
            $output->writeln(sprintf(' - Slider %s %s com 3 slides', $identifier, $wasExisting ? 'atualizado' : 'criado'));
        } catch (\Throwable $e) {
            $output->writeln('<error>   ✗ Falha ao semear slider: ' . $e->getMessage() . '</error>');
        }
    }
}
