<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewrite as UrlRewriteResource;
use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory;
use Magento\UrlRewrite\Model\UrlRewrite;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Rokanthemes\SlideBanner\Model\SlideFactory;

class EnforcePublicCatalogSeo implements DataPatchInterface
{
    private const DEFAULT_ROBOTS = 'INDEX,FOLLOW';
    private const TERMS_IDENTIFIER = 'termos-e-condicoes';
    private const PRIVACY_CANONICAL_IDENTIFIER = 'privacy-policy-cookie-restriction-mode';
    private const PRIVACY_ALIAS_REQUEST_PATHS = [
        'politica-de-privacidade',
        'politica-de-privacidade/',
    ];
    private const BRANDS_ALIAS_REQUEST_PATHS = [
        'brands',
        'brands/',
    ];
    private const BRANDS_CANONICAL_PATH = 'marcas.html';
    private const LEGACY_HOSTS = [
        'srv1113343.hstgr.cloud',
        'www.srv1113343.hstgr.cloud',
    ];
    private const FOOTER_BLOCK_IDENTIFIERS = [
        'footer_static',
        'footer_static4',
        'footer_static5',
        'footer_static6',
        'footer_static7',
        'footer_static9',
        'footer_static10',
        'footer_static11',
        'footer_static13',
        'footer_static14',
        'footer_static15',
        'footer_static16',
        'footer_menu',
    ];
    private const BANNER_MID_IDENTIFIER = 'banner_mid_home5';
    private const BANNER_MID_TITLE = 'Homepage — Banners Mid-Page (SEO)';
    private const SLIDER_HOME_ALT = '<p>Portal B2B AWA Motos</p>';
    private const DEFAULT_PAGE_LAYOUT = '1column';
    private const DEFAULT_PAGE_STORE_ID = 0;
    private const DEFAULT_SCOPE_ID = 0;
    private const ROBOTS_TEMPLATE = <<<'TEXT'
# Grupo Awamotos - Magento 2.4.8
User-agent: *
Disallow: /admin/
Disallow: /checkout/
Disallow: /customer/
Disallow: /catalogsearch/
Disallow: /wishlist/
Disallow: /pub/static/
Disallow: /var/

# Sitemaps
Sitemap: %1$s/sitemap.xml
Sitemap: %1$s/media_sitemap.xml
TEXT;
    private const BANNER_MID_CONTENT = <<<'HTML'
<div class="awa-banners-mosaic">
    <div class="awa-banner-item awa-banner-tall">
        <a class="banner-hover" href="{{store url="shipping"}}">
            <img loading="lazy" src="{{media url=wysiwyg/home-banners/banner-envio.jpg}}" alt="Envio Imediato para todo o Brasil">
        </a>
    </div>
    <div class="awa-banner-item awa-banner-wide">
        <a class="banner-hover" href="{{store url="formas-pagamento"}}">
            <img loading="lazy" src="{{media url=wysiwyg/home-banners/banner-pagamento.jpg}}" alt="Pagamento Seguro - Cartões, Pix e Boleto">
        </a>
    </div>
    <div class="awa-banner-item awa-banner-square">
        <a class="banner-hover" href="{{store url="ofertas.html"}}">
            <img loading="lazy" src="{{media url=wysiwyg/home-banners/banner-ofertas.jpg}}" alt="Ofertas e Promoções AWA Motos">
        </a>
    </div>
</div>
HTML;

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter,
        private readonly BlockFactory $blockFactory,
        private readonly PageFactory $pageFactory,
        private readonly UrlRewriteFactory $urlRewriteFactory,
        private readonly UrlRewriteResource $urlRewriteResource,
        private readonly UrlRewriteCollectionFactory $urlRewriteCollectionFactory,
        private readonly SlideFactory $slideFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $this->saveRobotsConfiguration();
            $this->upsertTermsPage();
            $this->upsertBannerMidBlock();
            $this->normalizeFooterBlocks();
            $this->upsertRedirects();
            $this->normalizeHomepageSlides();
        } finally {
            $this->moduleDataSetup->endSetup();
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AyoContentSetup::class,
            AyoHomepageCmsBlocks::class,
            UpdateInstitutionalPages::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function saveRobotsConfiguration(): void
    {
        $this->configWriter->save(
            'design/search_engine_robots/default_robots',
            self::DEFAULT_ROBOTS,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            self::DEFAULT_SCOPE_ID
        );

        $this->configWriter->save(
            'design/search_engine_robots/custom_instructions',
            sprintf(self::ROBOTS_TEMPLATE, $this->getDefaultBaseUrl()),
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            self::DEFAULT_SCOPE_ID
        );

        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();
            $baseUrl = rtrim((string) $store->getBaseUrl(), '/');

            $this->configWriter->save(
                'design/search_engine_robots/default_robots',
                self::DEFAULT_ROBOTS,
                ScopeInterface::SCOPE_STORES,
                $storeId
            );

            $this->configWriter->save(
                'design/search_engine_robots/custom_instructions',
                sprintf(self::ROBOTS_TEMPLATE, $baseUrl !== '' ? $baseUrl : $this->getDefaultBaseUrl()),
                ScopeInterface::SCOPE_STORES,
                $storeId
            );
        }
    }

    private function upsertTermsPage(): void
    {
        $page = $this->loadOrCreatePage(self::TERMS_IDENTIFIER);
        $page->setTitle('Termos e Condições');
        $page->setContentHeading('');
        $page->setPageLayout(self::DEFAULT_PAGE_LAYOUT);
        $page->setMetaTitle('Termos e Condições | AWA Motos');
        $page->setMetaDescription(
            'Termos e condições de uso, compra, pagamento, entrega e garantia da AWA Motos para clientes B2C e B2B.'
        );
        $page->setContent($this->getTermsPageContent());
        $page->setStores([self::DEFAULT_PAGE_STORE_ID]);
        $page->setIsActive(true);
        $page->save();
    }

    private function upsertBannerMidBlock(): void
    {
        $block = $this->loadOrCreateBlock(self::BANNER_MID_IDENTIFIER);
        $block->setTitle(self::BANNER_MID_TITLE);
        $block->setContent(self::BANNER_MID_CONTENT);
        $block->setStores([self::DEFAULT_PAGE_STORE_ID]);
        $block->setIsActive(true);
        $block->save();
    }

    private function normalizeFooterBlocks(): void
    {
        foreach (self::FOOTER_BLOCK_IDENTIFIERS as $identifier) {
            $block = $this->blockFactory->create();
            $block->setStoreId(self::DEFAULT_PAGE_STORE_ID);
            $block->load($identifier, 'identifier');

            if (!$block->getId()) {
                continue;
            }

            $currentContent = (string) $block->getContent();
            $updatedContent = $this->replaceBrokenFooterLinks($currentContent);

            if ($updatedContent === $currentContent) {
                continue;
            }

            $block->setContent($updatedContent);
            $block->setStores([self::DEFAULT_PAGE_STORE_ID]);
            $block->setIsActive(true);
            $block->save();
        }
    }

    private function upsertRedirects(): void
    {
        foreach ($this->storeManager->getStores() as $store) {
            $storeId = (int) $store->getId();

            foreach (self::PRIVACY_ALIAS_REQUEST_PATHS as $requestPath) {
                $this->saveCustomRedirect($requestPath, self::PRIVACY_CANONICAL_IDENTIFIER, $storeId);
            }

            foreach (self::BRANDS_ALIAS_REQUEST_PATHS as $requestPath) {
                $this->saveCustomRedirect($requestPath, self::BRANDS_CANONICAL_PATH, $storeId);
            }
        }
    }

    private function normalizeHomepageSlides(): void
    {
        $baseUrl = $this->getDefaultBaseUrl();
        $slides = $this->slideFactory->create()->getCollection();

        foreach ($slides as $slide) {
            $hasChanges = false;
            $slideLink = trim((string) $slide->getData('slide_link'));
            $normalizedLink = $this->replaceLegacyHost($slideLink, $baseUrl);

            if ($normalizedLink !== $slideLink) {
                $slide->setData('slide_link', $normalizedLink);
                $hasChanges = true;
            }

            $plainText = trim(strip_tags((string) $slide->getData('slide_text')));
            $normalizedBaseUrl = rtrim($baseUrl, '/') . '/';

            if ($plainText === '.' && ($normalizedLink === $normalizedBaseUrl || $slide->getData('slide_position') == 0)) {
                $slide->setData('slide_text', self::SLIDER_HOME_ALT);
                $hasChanges = true;
            }

            if ($hasChanges) {
                $slide->save();
            }
        }
    }

    private function loadOrCreateBlock(string $identifier): Block
    {
        $block = $this->blockFactory->create();
        $block->setStoreId(self::DEFAULT_PAGE_STORE_ID);
        $block->load($identifier, 'identifier');

        if (!$block->getId()) {
            $block->setData([
                'identifier' => $identifier,
                'stores' => [self::DEFAULT_PAGE_STORE_ID],
                'is_active' => 1,
            ]);
        }

        return $block;
    }

    private function loadOrCreatePage(string $identifier): Page
    {
        $page = $this->pageFactory->create();
        $page->setStoreId(self::DEFAULT_PAGE_STORE_ID);
        $page->load($identifier, 'identifier');

        if (!$page->getId()) {
            $page->setData([
                'identifier' => $identifier,
                'stores' => [self::DEFAULT_PAGE_STORE_ID],
                'is_active' => 1,
            ]);
        }

        return $page;
    }

    private function replaceBrokenFooterLinks(string $content): string
    {
        return strtr(
            $content,
            [
                'href="/brands"' => 'href="{{store url=\'marcas.html\'}}"',
                "href='/brands'" => 'href="{{store url=\'marcas.html\'}}"',
                'href="/termos-e-condicoes"' => 'href="{{store url=\'termos-e-condicoes\'}}"',
                "href='/termos-e-condicoes'" => 'href="{{store url=\'termos-e-condicoes\'}}"',
                'href="/politica-de-privacidade"' => 'href="{{store url=\'privacy-policy-cookie-restriction-mode\'}}"',
                "href='/politica-de-privacidade'" => 'href="{{store url=\'privacy-policy-cookie-restriction-mode\'}}"',
            ]
        );
    }

    private function saveCustomRedirect(string $requestPath, string $targetPath, int $storeId): void
    {
        $rewrite = $this->urlRewriteCollectionFactory->create()
            ->addFieldToFilter('request_path', $requestPath)
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem();

        if (!$rewrite->getId()) {
            $rewrite = $this->urlRewriteFactory->create();
        }

        $rewrite->setStoreId($storeId);
        $rewrite->setEntityType('custom');
        $rewrite->setEntityId(0);
        $rewrite->setRequestPath($requestPath);
        $rewrite->setTargetPath($targetPath);
        $rewrite->setRedirectType(301);
        $rewrite->setIsAutogenerated(0);

        $this->urlRewriteResource->save($rewrite);
    }

    private function replaceLegacyHost(string $link, string $baseUrl): string
    {
        if ($link === '') {
            return '';
        }

        $parts = parse_url($link);
        $baseParts = parse_url($baseUrl);

        if (!is_array($parts) || !is_array($baseParts)) {
            return $link;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '' || !in_array($host, self::LEGACY_HOSTS, true)) {
            return $link;
        }

        $scheme = (string) ($baseParts['scheme'] ?? 'https');
        $baseHost = (string) ($baseParts['host'] ?? '');
        if ($baseHost === '') {
            return $link;
        }

        $path = (string) ($parts['path'] ?? '/');
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        return $scheme . '://' . $baseHost . ($path !== '' ? $path : '/') . $query . $fragment;
    }

    private function getDefaultBaseUrl(): string
    {
        $baseUrl = (string) $this->scopeConfig->getValue('web/secure/base_url');
        if ($baseUrl === '') {
            $baseUrl = (string) $this->scopeConfig->getValue('web/unsecure/base_url');
        }

        return rtrim($baseUrl, '/');
    }

    private function getTermsPageContent(): string
    {
        return <<<'HTML'
<div class="awa-inst" role="main" aria-label="Termos e Condições">
  <h1>Termos e Condições</h1>

  <div class="awa-summary">
    <p><strong>Última atualização:</strong> 10 de março de 2026.</p>
    <p>Estes termos regulam o uso do site da <strong>AWA Motos</strong>, bem como as compras realizadas por clientes pessoa física e jurídica. Ao navegar, se cadastrar ou concluir um pedido, você concorda com as regras abaixo.</p>
  </div>

  <h2>1. Identificação da empresa</h2>
  <p>O site é operado pelo <strong>Grupo Awamotos</strong>, inscrito no CNPJ {{config path="general/store_information/merchant_vat_number"}}, com sede em {{config path="general/store_information/street_line1"}}, {{config path="general/store_information/street_line2"}} — {{config path="general/store_information/city"}}/SP, CEP {{config path="general/store_information/postcode"}}.</p>

  <h2>2. Uso do site</h2>
  <ul>
    <li>Você deve fornecer informações verdadeiras e atualizadas em cadastro, orçamento e compra.</li>
    <li>É proibido usar o site para fraudes, coleta automatizada de dados ou qualquer atividade que prejudique a operação da loja.</li>
    <li>A AWA Motos pode bloquear cadastros ou pedidos com indícios de uso indevido, inconsistência cadastral ou risco de fraude.</li>
  </ul>

  <h2>3. Cadastro e conta</h2>
  <p>O acesso a áreas restritas depende de conta válida. No canal B2B, o cadastro está sujeito à análise de CNPJ e aprovação comercial. Cada cliente é responsável pela confidencialidade de seu login e pelas ações realizadas em sua conta.</p>

  <h2>4. Preços, estoque e condições comerciais</h2>
  <ul>
    <li>Os preços são exibidos em Reais (BRL) e podem variar sem aviso prévio até a confirmação do pedido.</li>
    <li>Ofertas promocionais e condições especiais podem ter prazo, estoque ou elegibilidade limitados.</li>
    <li>Pedidos B2B podem seguir políticas comerciais próprias, incluindo desconto por volume, crédito e aprovação interna.</li>
    <li>A conclusão do pedido depende de disponibilidade real de estoque e validação de pagamento.</li>
  </ul>

  <h2>5. Pagamento e faturamento</h2>
  <p>As formas de pagamento aceitas estão descritas na página de <a href="{{store url='formas-pagamento'}}">Formas de Pagamento</a>. Em pedidos faturados para CNPJ, a liberação pode depender de análise cadastral, limite de crédito e conformidade fiscal.</p>

  <h2>6. Entrega e retirada</h2>
  <p>Os prazos e modalidades de entrega seguem a política de <a href="{{store url='shipping'}}">Frete e Entrega</a>. Caso haja retirada em loja, o pedido só poderá ser liberado após confirmação do pagamento e validação do titular ou representante autorizado.</p>

  <h2>7. Trocas, devoluções e garantia</h2>
  <p>Trocas, devoluções e garantias seguem as regras publicadas em <a href="{{store url='returns'}}">Trocas e Devoluções</a> e <a href="{{store url='warranty'}}">Garantia</a>. Produtos com sinais de mau uso, instalação inadequada ou violação de embalagem podem ser recusados após análise técnica.</p>

  <h2>8. Conteúdo e propriedade intelectual</h2>
  <p>Marcas, imagens, descrições, layout, textos, logos e demais conteúdos do site pertencem à AWA Motos ou a seus parceiros e não podem ser copiados, reproduzidos ou utilizados sem autorização prévia.</p>

  <h2>9. Privacidade e proteção de dados</h2>
  <p>O tratamento de dados pessoais segue a nossa <a href="{{store url='privacy-policy-cookie-restriction-mode'}}">Política de Privacidade e Cookies</a>, em conformidade com a LGPD.</p>

  <h2>10. Limitação de responsabilidade</h2>
  <ul>
    <li>A AWA Motos não responde por indisponibilidades temporárias do site, falhas de terceiros ou uso incorreto do produto.</li>
    <li>Compatibilidade de peça deve ser confirmada pelo cliente com base na aplicação correta, ficha técnica e suporte comercial quando necessário.</li>
    <li>Imagens e cores podem sofrer variação conforme lote, tela e atualização do fabricante.</li>
  </ul>

  <h2>11. Canal de atendimento</h2>
  <p>Em caso de dúvidas, fale com nossa equipe pela <a href="{{store url='customer-service'}}">Central de Atendimento</a>, pelo telefone {{config path="general/store_information/phone"}} ou pelo WhatsApp <a href="https://wa.me/5516997367588" target="_blank" rel="noopener">(16) 99736-7588</a>.</p>
</div>
HTML;
    }
}
