<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Aplica TODAS as configurações do tema Ayo (Rokanthemes) conforme
 * documentação oficial: https://ayo.nextsky.co/documentation/
 *
 * Escopo:
 * - Theme Options (General, Font, Colors, Sticky Header, Newsletter Popup)
 * - Custom Menu
 * - Vertical Menu
 * - ProductTab (New, OnSale, Bestseller, Featured, MostViewed, TopRated)
 * - CategoryTab
 * - LayeredAjax
 * - OnePageCheckout (Terms & Conditions)
 * - QuickView
 * - AjaxSuite
 * - SearchSuiteAutocomplete
 * - Blog
 * - Testimonials
 * - SuperDeals
 * - PriceCountdown
 * - Brand
 * - StoreLocator
 * - FAQ
 * - SlideBanner
 *
 * @see docs/AUDITORIA_TEMA_AYO.md
 */
class AyoThemeFullConfiguration implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        $configs = $this->getAllConfigurations();
        $count = 0;

        foreach ($configs as $path => $value) {
            try {
                $this->configWriter->save($path, $value, 'default', 0);
                $count++;
            } catch (\Throwable $e) {
                $this->logger->warning(
                    sprintf('[AyoThemeFullConfiguration] Falha ao salvar %s: %s', $path, $e->getMessage())
                );
            }
        }

        $this->logger->info(
            sprintf('[AyoThemeFullConfiguration] %d configurações aplicadas com sucesso.', $count)
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            ApplyAwaColorPalette::class,
            ConfigureAyoHome5Parity::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * Retorna TODAS as configurações Rokanthemes recomendadas.
     *
     * @return array<string, string>
     */
    private function getAllConfigurations(): array
    {
        return array_merge(
            $this->getThemeOptionGeneral(),
            $this->getThemeOptionFont(),
            $this->getThemeOptionColors(),
            $this->getThemeOptionStickyHeader(),
            $this->getThemeOptionNewsletterPopup(),
            $this->getCustomMenuConfig(),
            $this->getVerticalMenuConfig(),
            $this->getProductTabConfig(),
            $this->getCategoryTabConfig(),
            $this->getLayeredAjaxConfig(),
            $this->getOnePageCheckoutConfig(),
            $this->getQuickViewConfig(),
            $this->getAjaxSuiteConfig(),
            $this->getSearchAutocompleteConfig(),
            $this->getBlogConfig(),
            $this->getTestimonialsConfig(),
            $this->getSuperDealsConfig(),
            $this->getPriceCountdownConfig(),
            $this->getBrandConfig(),
            $this->getStoreLocatorConfig(),
            $this->getFaqConfig(),
            $this->getSliderConfig(),
        );
    }

    // ========================================================================
    // THEME OPTIONS — General
    // ========================================================================

    private function getThemeOptionGeneral(): array
    {
        return [
            // Habilita renderização dinâmica LESS (desabilitar em produção)
            'rokanthemes_themeoption/general/auto_render_less' => '0',
            // Largura padrão da página
            'rokanthemes_themeoption/general/page_width' => '1200',
            // Copyright
            'rokanthemes_themeoption/general/copyright' => '© 2026 AWA Motos — Grupo Awamotos. Todos os direitos reservados. CNPJ: XX.XXX.XXX/0001-XX',
            // Layout padrão
            'rokanthemes_themeoption/general/layout' => 'wide',
        ];
    }

    // ========================================================================
    // THEME OPTIONS — Font
    // ========================================================================

    private function getThemeOptionFont(): array
    {
        return [
            // Habilitar fonte customizada
            'rokanthemes_themeoption/font/custom' => '1',
            // Fonte Google
            'rokanthemes_themeoption/font/google_font' => 'Roboto:300,400,500,700',
            // Font Family geral
            'rokanthemes_themeoption/font/basic_font_family' => "'Roboto', 'Helvetica Neue', Arial, sans-serif",
            // Tamanho base
            'rokanthemes_themeoption/font/basic_font_size' => '14',
            // Fonte para headings
            'rokanthemes_themeoption/font/heading_font_family' => "'Roboto', 'Helvetica Neue', Arial, sans-serif",
            'rokanthemes_themeoption/font/heading_font_weight' => '700',
        ];
    }

    // ========================================================================
    // THEME OPTIONS — Colors (complementa ApplyAwaColorPalette)
    // ========================================================================

    private function getThemeOptionColors(): array
    {
        return [
            // Enable custom color scheme
            'rokanthemes_themeoption/colors/custom' => '1',
            // Cores primárias AWA
            'rokanthemes_themeoption/colors/primary_color' => 'b73337',
            'rokanthemes_themeoption/colors/secondary_color' => '333333',
            // Text
            'rokanthemes_themeoption/colors/text_color' => '333333',
            // Links
            'rokanthemes_themeoption/colors/link_color' => 'b73337',
            'rokanthemes_themeoption/colors/link_hover_color' => '8e2629',
            // Botões
            'rokanthemes_themeoption/colors/button_text_color' => 'FFFFFF',
            'rokanthemes_themeoption/colors/button_bg_color' => 'b73337',
            'rokanthemes_themeoption/colors/button_hover_text_color' => 'FFFFFF',
            'rokanthemes_themeoption/colors/button_hover_bg_color' => '8e2629',
            // Header
            'rokanthemes_themeoption/colors/header_bg_color' => 'FFFFFF',
            'rokanthemes_themeoption/colors/header_text_color' => '333333',
            // Footer
            'rokanthemes_themeoption/colors/footer_bg_color' => '222222',
            'rokanthemes_themeoption/colors/footer_text_color' => 'cccccc',
        ];
    }

    // ========================================================================
    // THEME OPTIONS — Sticky Header
    // ========================================================================

    private function getThemeOptionStickyHeader(): array
    {
        return [
            'rokanthemes_themeoption/sticky_header/enable' => '1',
            'rokanthemes_themeoption/sticky_header/bg_color' => 'FFFFFF',
            'rokanthemes_themeoption/sticky_header/text_color' => '333333',
        ];
    }

    // ========================================================================
    // THEME OPTIONS — Newsletter Popup
    // ========================================================================

    private function getThemeOptionNewsletterPopup(): array
    {
        return [
            'rokanthemes_themeoption/newsletter_popup/enable' => '1',
            'rokanthemes_themeoption/newsletter_popup/width' => '600',
            'rokanthemes_themeoption/newsletter_popup/height' => '400',
            'rokanthemes_themeoption/newsletter_popup/delay' => '5000',
            'rokanthemes_themeoption/newsletter_popup/title' => 'Receba Ofertas Exclusivas!',
            'rokanthemes_themeoption/newsletter_popup/description' => 'Cadastre-se e ganhe 10% de desconto na primeira compra. Fique por dentro de lançamentos e promoções de peças para motos.',
            'rokanthemes_themeoption/newsletter_popup/button_text' => 'Quero Meu Desconto',
        ];
    }

    // ========================================================================
    // CUSTOM MENU
    // ========================================================================

    private function getCustomMenuConfig(): array
    {
        return [
            'rokanthemes_custommenu/general/enable' => '1',
            'rokanthemes_custommenu/general/default_menu_type' => 'fullwidth',
            'rokanthemes_custommenu/general/visible_menu_depth' => '3',
        ];
    }

    // ========================================================================
    // VERTICAL MENU
    // ========================================================================

    private function getVerticalMenuConfig(): array
    {
        return [
            'rokanthemes_verticalmenu/general/enable' => '1',
            'rokanthemes_verticalmenu/general/default_menu_type' => 'fullwidth',
            'rokanthemes_verticalmenu/general/visible_menu_depth' => '3',
            'rokanthemes_verticalmenu/general/limit_show_more_cat' => '10',
        ];
    }

    // ========================================================================
    // PRODUCTTAB — Tabs de Produtos na Homepage
    // ========================================================================

    private function getProductTabConfig(): array
    {
        return [
            // ---- New Products ----
            'rokanthemes_newproduct/general/enable' => '1',
            'rokanthemes_newproduct/general/title' => 'Novidades',
            'rokanthemes_newproduct/general/description' => 'Confira os últimos lançamentos em peças e acessórios para motos',
            'rokanthemes_newproduct/general/qty_products' => '12',
            'rokanthemes_newproduct/general/autoplay' => '1',
            'rokanthemes_newproduct/general/items_default' => '5',
            'rokanthemes_newproduct/general/items_desktop' => '4',
            'rokanthemes_newproduct/general/items_tablet' => '3',
            'rokanthemes_newproduct/general/items_mobile' => '2',
            'rokanthemes_newproduct/general/show_price' => '1',
            'rokanthemes_newproduct/general/show_addtocart' => '1',
            'rokanthemes_newproduct/general/show_wishlist' => '1',
            'rokanthemes_newproduct/general/show_rating' => '1',

            // ---- On Sale Products ----
            'rokanthemes_onsaleproduct/general/enable' => '1',
            'rokanthemes_onsaleproduct/general/title' => 'Ofertas',
            'rokanthemes_onsaleproduct/general/description' => 'Aproveite os melhores preços em peças para motos',
            'rokanthemes_onsaleproduct/general/qty_products' => '12',
            'rokanthemes_onsaleproduct/general/autoplay' => '1',
            'rokanthemes_onsaleproduct/general/items_default' => '5',
            'rokanthemes_onsaleproduct/general/items_desktop' => '4',
            'rokanthemes_onsaleproduct/general/items_tablet' => '3',
            'rokanthemes_onsaleproduct/general/items_mobile' => '2',
            'rokanthemes_onsaleproduct/general/show_price' => '1',
            'rokanthemes_onsaleproduct/general/show_addtocart' => '1',
            'rokanthemes_onsaleproduct/general/show_wishlist' => '1',
            'rokanthemes_onsaleproduct/general/show_rating' => '1',

            // ---- Bestseller Products ----
            'rokanthemes_bestsellerproduct/general/enable' => '1',
            'rokanthemes_bestsellerproduct/general/title' => 'Mais Vendidos',
            'rokanthemes_bestsellerproduct/general/description' => 'Os produtos preferidos dos nossos clientes',
            'rokanthemes_bestsellerproduct/general/qty_products' => '12',
            'rokanthemes_bestsellerproduct/general/autoplay' => '1',
            'rokanthemes_bestsellerproduct/general/items_default' => '5',
            'rokanthemes_bestsellerproduct/general/items_desktop' => '4',
            'rokanthemes_bestsellerproduct/general/items_tablet' => '3',
            'rokanthemes_bestsellerproduct/general/items_mobile' => '2',
            'rokanthemes_bestsellerproduct/general/show_price' => '1',
            'rokanthemes_bestsellerproduct/general/show_addtocart' => '1',
            'rokanthemes_bestsellerproduct/general/show_wishlist' => '1',
            'rokanthemes_bestsellerproduct/general/show_rating' => '1',

            // ---- Most Viewed Products ----
            'rokanthemes_mostviewedproduct/general/enable' => '1',
            'rokanthemes_mostviewedproduct/general/title' => 'Mais Visualizados',
            'rokanthemes_mostviewedproduct/general/description' => 'Os produtos mais procurados',
            'rokanthemes_mostviewedproduct/general/qty_products' => '12',
            'rokanthemes_mostviewedproduct/general/autoplay' => '1',
            'rokanthemes_mostviewedproduct/general/items_default' => '5',
            'rokanthemes_mostviewedproduct/general/items_desktop' => '4',
            'rokanthemes_mostviewedproduct/general/items_tablet' => '3',
            'rokanthemes_mostviewedproduct/general/items_mobile' => '2',

            // ---- Featured Products ----
            'rokanthemes_featuredpro/general/enable' => '1',
            'rokanthemes_featuredpro/general/title' => 'Destaques',
            'rokanthemes_featuredpro/general/description' => 'Seleção especial de peças e acessórios',
            'rokanthemes_featuredpro/general/qty_products' => '12',
            'rokanthemes_featuredpro/general/autoplay' => '1',
            'rokanthemes_featuredpro/general/items_default' => '5',
            'rokanthemes_featuredpro/general/items_desktop' => '4',
            'rokanthemes_featuredpro/general/items_tablet' => '3',
            'rokanthemes_featuredpro/general/items_mobile' => '2',
            'rokanthemes_featuredpro/general/show_price' => '1',
            'rokanthemes_featuredpro/general/show_addtocart' => '1',
            'rokanthemes_featuredpro/general/show_wishlist' => '1',
            'rokanthemes_featuredpro/general/show_rating' => '1',

            // ---- Top Rated Products ----
            'rokanthemes_toprate/general/enable' => '1',
            'rokanthemes_toprate/general/title' => 'Melhor Avaliados',
            'rokanthemes_toprate/general/description' => 'Produtos com as melhores avaliações',
            'rokanthemes_toprate/general/qty_products' => '12',
            'rokanthemes_toprate/general/autoplay' => '1',
            'rokanthemes_toprate/general/items_default' => '5',
            'rokanthemes_toprate/general/items_desktop' => '4',
            'rokanthemes_toprate/general/items_tablet' => '3',
            'rokanthemes_toprate/general/items_mobile' => '2',
        ];
    }

    // ========================================================================
    // CATEGORY TAB
    // ========================================================================

    private function getCategoryTabConfig(): array
    {
        return [
            'rokanthemes_categorytab/general/enable' => '1',
            'rokanthemes_categorytab/general/title' => 'Compre por Categoria',
            'rokanthemes_categorytab/general/description' => 'Encontre peças e acessórios por tipo de produto',
            'rokanthemes_categorytab/general/items_default' => '4',
            'rokanthemes_categorytab/general/items_desktop' => '4',
            'rokanthemes_categorytab/general/items_tablet' => '3',
            'rokanthemes_categorytab/general/items_mobile' => '2',
        ];
    }

    // ========================================================================
    // LAYERED AJAX — Filtro de Navegação com AJAX
    // ========================================================================

    private function getLayeredAjaxConfig(): array
    {
        return [
            'rokanthemes_layeredajax/general/enable' => '1',
            'rokanthemes_layeredajax/general/open_all_tab' => '0',
            'rokanthemes_layeredajax/general/use_price_range_slider' => '1',
            'rokanthemes_layeredajax/general/scroll_to_top' => '1',
        ];
    }

    // ========================================================================
    // ONE PAGE CHECKOUT — Terms & Conditions
    // ========================================================================

    private function getOnePageCheckoutConfig(): array
    {
        return [
            'rokanthemes_onepagecheckout/general/enable' => '1',
            // Terms and Conditions
            'rokanthemes_onepagecheckout/terms/enable' => '1',
            'rokanthemes_onepagecheckout/terms/checkbox_text' => 'Li e aceito os <a href="/termos-e-condicoes" target="_blank">Termos e Condições</a> e a <a href="/politica-de-privacidade" target="_blank">Política de Privacidade</a>.',
            'rokanthemes_onepagecheckout/terms/title_warning' => 'Atenção!',
            'rokanthemes_onepagecheckout/terms/content_warning' => 'Você precisa aceitar os Termos e Condições para finalizar a compra.',
            // Layout
            'rokanthemes_onepagecheckout/general/show_order_comment' => '1',
            'rokanthemes_onepagecheckout/general/show_discount' => '1',
        ];
    }

    // ========================================================================
    // QUICK VIEW
    // ========================================================================

    private function getQuickViewConfig(): array
    {
        return [
            'rokanthemes_quickview/general/enable' => '1',
            'rokanthemes_quickview/general/enabled' => '1',
        ];
    }

    // ========================================================================
    // AJAX SUITE
    // ========================================================================

    private function getAjaxSuiteConfig(): array
    {
        return [
            'rokanthemes_ajaxsuite/general/ajaxcart_enable' => '1',
            'rokanthemes_ajaxsuite/general/ajaxcompare_enable' => '1',
            'rokanthemes_ajaxsuite/general/ajaxwishlist_enable' => '1',
        ];
    }

    // ========================================================================
    // SEARCH AUTOCOMPLETE
    // ========================================================================

    private function getSearchAutocompleteConfig(): array
    {
        return [
            'rokanthemes_searchsuiteautocomplete/general/enable' => '1',
            'rokanthemes_searchsuiteautocomplete/general/min_chars' => '2',
            'rokanthemes_searchsuiteautocomplete/general/max_results' => '10',
            'rokanthemes_searchsuiteautocomplete/general/show_image' => '1',
            'rokanthemes_searchsuiteautocomplete/general/show_price' => '1',
            'rokanthemes_searchsuiteautocomplete/general/show_description' => '1',
        ];
    }

    // ========================================================================
    // BLOG
    // ========================================================================

    private function getBlogConfig(): array
    {
        return [
            'rokanthemes_blog/general/enabled' => '1',
            'rokanthemes_blog/general/title' => 'Blog AWA Motos',
            'rokanthemes_blog/general/route' => 'blog',
            'rokanthemes_blog/general/meta_title' => 'Blog — Dicas, Novidades e Guias | AWA Motos',
            'rokanthemes_blog/general/meta_description' => 'Acompanhe dicas de uso, manutenção de motos, novidades em peças e acessórios no blog da AWA Motos.',
            // Sidebar
            'rokanthemes_blog/sidebar/recent_posts_count' => '5',
            'rokanthemes_blog/sidebar/most_viewed_count' => '5',
            // Slider
            'rokanthemes_blog/slider/enable' => '1',
            'rokanthemes_blog/slider/title' => 'Do Nosso Blog',
            'rokanthemes_blog/slider/description' => 'Dicas, guias e novidades sobre motos e acessórios',
            'rokanthemes_blog/slider/items_desktop' => '3',
            'rokanthemes_blog/slider/items_tablet' => '2',
            'rokanthemes_blog/slider/items_mobile' => '1',
        ];
    }

    // ========================================================================
    // TESTIMONIALS
    // ========================================================================

    private function getTestimonialsConfig(): array
    {
        return [
            'rokanthemes_testimonials/general/enable' => '1',
            'rokanthemes_testimonials/general/title' => 'O Que Nossos Clientes Dizem',
            'rokanthemes_testimonials/general/auto_slider' => '1',
            'rokanthemes_testimonials/general/items_desktop' => '3',
            'rokanthemes_testimonials/general/items_tablet' => '2',
            'rokanthemes_testimonials/general/items_mobile' => '1',
        ];
    }

    // ========================================================================
    // SUPERDEALS
    // ========================================================================

    private function getSuperDealsConfig(): array
    {
        return [
            'rokanthemes_superdeals/general/enable' => '1',
            'rokanthemes_superdeals/general/title' => 'Super Ofertas',
            'rokanthemes_superdeals/general/description' => 'Ofertas por tempo limitado — aproveite!',
            'rokanthemes_superdeals/general/items_desktop' => '4',
            'rokanthemes_superdeals/general/items_tablet' => '3',
            'rokanthemes_superdeals/general/items_mobile' => '2',
        ];
    }

    // ========================================================================
    // PRICE COUNTDOWN
    // ========================================================================

    private function getPriceCountdownConfig(): array
    {
        return [
            'rokanthemes_pricecountdown/general/enable' => '1',
        ];
    }

    // ========================================================================
    // BRAND
    // ========================================================================

    private function getBrandConfig(): array
    {
        return [
            'rokanthemes_brand/general/enabled' => '1',
            'rokanthemes_brand/general/route' => 'brands',
            'rokanthemes_brand/general/title' => 'Nossas Marcas',
            'rokanthemes_brand/general/meta_title' => 'Marcas de Peças e Acessórios para Motos | AWA Motos',
            'rokanthemes_brand/general/meta_description' => 'Conheça as marcas parceiras da AWA Motos: fabricantes de bagageiros, baús, retrovisores e acessórios.',
            'rokanthemes_brand/general/items_desktop' => '6',
            'rokanthemes_brand/general/items_tablet' => '4',
            'rokanthemes_brand/general/items_mobile' => '2',
        ];
    }

    // ========================================================================
    // STORE LOCATOR
    // ========================================================================

    private function getStoreLocatorConfig(): array
    {
        return [
            'rokanthemes_storelocator/general/enable' => '1',
            'rokanthemes_storelocator/general/title' => 'Nossa Loja',
            'rokanthemes_storelocator/general/meta_title' => 'Nossa Loja — AWA Motos em Araraquara-SP',
            'rokanthemes_storelocator/general/meta_description' => 'Visite a AWA Motos em Araraquara-SP. Peças e acessórios para motos no atacado e varejo.',
        ];
    }

    // ========================================================================
    // FAQ
    // ========================================================================

    private function getFaqConfig(): array
    {
        return [
            'rokanthemes_faq/general/enable' => '1',
            'rokanthemes_faq/general/title' => 'Perguntas Frequentes',
            'rokanthemes_faq/general/meta_title' => 'Perguntas Frequentes — FAQ | AWA Motos',
            'rokanthemes_faq/general/meta_description' => 'Encontre respostas sobre envio, pagamento, trocas, devoluções e produtos na AWA Motos.',
        ];
    }

    // ========================================================================
    // SLIDE BANNER
    // ========================================================================

    private function getSliderConfig(): array
    {
        return [
            'rokanthemes_slidebanner/general/enabled' => '1',
        ];
    }
}
