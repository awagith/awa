<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Remove paths legados de configuracao Ayo/Rokanthemes que nao sao
 * reconhecidos pelos system.xml atuais dos modulos instalados.
 */
class CleanupLegacyAyoConfigPaths implements DataPatchInterface
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

        $deleted = 0;
        $failed = 0;

        foreach ($this->getLegacyPaths() as $path) {
            try {
                $this->configWriter->delete($path, 'default', 0);
                $deleted++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->logger->warning(
                    sprintf(
                        '[CleanupLegacyAyoConfigPaths] Falha ao remover "%s": %s',
                        $path,
                        $exception->getMessage()
                    )
                );
            }
        }

        $this->logger->info(
            sprintf(
                '[CleanupLegacyAyoConfigPaths] Paths removidos: %d | falhas: %d',
                $deleted,
                $failed
            )
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AlignAyoRokanthemesConfigPaths::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    private function getLegacyPaths(): array
    {
        return [
            // Themeoption legacy section
            'rokanthemes_themeoption/general/auto_render_less',
            'rokanthemes_themeoption/general/page_width',
            'rokanthemes_themeoption/general/copyright',
            'rokanthemes_themeoption/general/layout',
            'rokanthemes_themeoption/font/custom',
            'rokanthemes_themeoption/font/google_font',
            'rokanthemes_themeoption/font/basic_font_family',
            'rokanthemes_themeoption/font/basic_font_size',
            'rokanthemes_themeoption/font/heading_font_family',
            'rokanthemes_themeoption/font/heading_font_weight',
            'rokanthemes_themeoption/colors/custom',
            'rokanthemes_themeoption/colors/primary_color',
            'rokanthemes_themeoption/colors/secondary_color',
            'rokanthemes_themeoption/colors/text_color',
            'rokanthemes_themeoption/colors/link_color',
            'rokanthemes_themeoption/colors/link_hover_color',
            'rokanthemes_themeoption/colors/button_text_color',
            'rokanthemes_themeoption/colors/button_bg_color',
            'rokanthemes_themeoption/colors/button_hover_text_color',
            'rokanthemes_themeoption/colors/button_hover_bg_color',
            'rokanthemes_themeoption/colors/header_bg_color',
            'rokanthemes_themeoption/colors/header_text_color',
            'rokanthemes_themeoption/colors/footer_bg_color',
            'rokanthemes_themeoption/colors/footer_text_color',
            'rokanthemes_themeoption/sticky_header/enable',
            'rokanthemes_themeoption/sticky_header/bg_color',
            'rokanthemes_themeoption/sticky_header/text_color',
            'rokanthemes_themeoption/newsletter_popup/enable',
            'rokanthemes_themeoption/newsletter_popup/width',
            'rokanthemes_themeoption/newsletter_popup/height',
            'rokanthemes_themeoption/newsletter_popup/delay',
            'rokanthemes_themeoption/newsletter_popup/title',
            'rokanthemes_themeoption/newsletter_popup/description',
            'rokanthemes_themeoption/newsletter_popup/button_text',

            // Legacy prefixes no longer used by current modules
            'rokanthemes_custommenu/general/enable',
            'rokanthemes_custommenu/general/default_menu_type',
            'rokanthemes_custommenu/general/menu_type',
            'rokanthemes_custommenu/general/visible_menu_depth',
            'rokanthemes_custommenu/general/visible_depth',
            'rokanthemes_verticalmenu/general/enable',
            'rokanthemes_verticalmenu/general/default_menu_type',
            'rokanthemes_verticalmenu/general/menu_type',
            'rokanthemes_verticalmenu/general/visible_menu_depth',
            'rokanthemes_verticalmenu/general/visible_depth',
            'rokanthemes_verticalmenu/general/limit_show_more_cat',
            'rokanthemes_verticalmenu/general/limit_cat',
            'rokanthemes_layeredajax/general/enable',
            'rokanthemes_layeredajax/general/open_all_tab',
            'rokanthemes_layeredajax/general/use_price_range_slider',
            'rokanthemes_layeredajax/general/price_slider',
            'rokanthemes_layeredajax/general/scroll_to_top',
            'rokanthemes_onepagecheckout/general/enable',
            'rokanthemes_onepagecheckout/general/show_coupon',
            'rokanthemes_onepagecheckout/general/show_comment',
            'rokanthemes_onepagecheckout/general/show_order_comment',
            'rokanthemes_onepagecheckout/general/show_discount',
            'rokanthemes_onepagecheckout/terms/enable',
            'rokanthemes_onepagecheckout/terms/checkbox_text',
            'rokanthemes_onepagecheckout/terms/title_warning',
            'rokanthemes_onepagecheckout/terms/content_warning',
            'rokanthemes_ajaxsuite/general/enable',
            'rokanthemes_ajaxsuite/general/ajaxcart_enable',
            'rokanthemes_ajaxsuite/general/ajaxcompare_enable',
            'rokanthemes_ajaxsuite/general/ajaxwishlist_enable',
            'rokanthemes_ajaxsuite/general/popup_addtocart',
            'rokanthemes_ajaxsuite/general/popup_wishlist',
            'rokanthemes_ajaxsuite/general/popup_compare',
            'rokanthemes_searchsuiteautocomplete/general/enable',
            'rokanthemes_searchsuiteautocomplete/general/min_chars',
            'rokanthemes_searchsuiteautocomplete/general/max_results',
            'rokanthemes_searchsuiteautocomplete/general/show_image',
            'rokanthemes_searchsuiteautocomplete/general/show_price',
            'rokanthemes_searchsuiteautocomplete/general/show_description',
            'rokanthemes_searchsuiteautocomplete/general/show_product_image',
            'rokanthemes_searchsuiteautocomplete/general/show_product_price',
            'rokanthemes_searchbycat/general/enable',
            'rokanthemes_blog/general/enabled',
            'rokanthemes_blog/general/title',
            'rokanthemes_blog/general/route',
            'rokanthemes_blog/general/meta_title',
            'rokanthemes_blog/general/meta_description',
            'rokanthemes_blog/sidebar/recent_posts_count',
            'rokanthemes_blog/sidebar/most_viewed_count',
            'rokanthemes_blog/sidebar/recent_posts',
            'rokanthemes_blog/sidebar/most_viewed',
            'rokanthemes_blog/slider/enable',
            'rokanthemes_blog/slider/title',
            'rokanthemes_blog/slider/description',
            'rokanthemes_blog/slider/items_desktop',
            'rokanthemes_blog/slider/items_tablet',
            'rokanthemes_blog/slider/items_mobile',
            'rokanthemes_testimonials/general/enable',
            'rokanthemes_testimonials/general/title',
            'rokanthemes_testimonials/general/auto_slider',
            'rokanthemes_testimonials/general/auto',
            'rokanthemes_testimonials/general/items_default',
            'rokanthemes_testimonials/general/items_desktop',
            'rokanthemes_testimonials/general/items_tablet',
            'rokanthemes_testimonials/general/items_mobile',
            'rokanthemes_superdeals/general/enable',
            'rokanthemes_superdeals/general/title',
            'rokanthemes_superdeals/general/description',
            'rokanthemes_superdeals/general/items_desktop',
            'rokanthemes_superdeals/general/items_tablet',
            'rokanthemes_superdeals/general/items_mobile',
            'rokanthemes_pricecountdown/general/enable',
            'rokanthemes_pricecountdown/general/title',
            'rokanthemes_pricecountdown/general/auto',
            'rokanthemes_pricecountdown/general/show_price',
            'rokanthemes_pricecountdown/general/show_addtocart',
            'rokanthemes_pricecountdown/general/qty_products',
            'rokanthemes_brand/general/enable',
            'rokanthemes_brand/general/enabled',
            'rokanthemes_brand/general/route',
            'rokanthemes_brand/general/title',
            'rokanthemes_brand/general/meta_title',
            'rokanthemes_brand/general/meta_description',
            'rokanthemes_brand/general/items_desktop',
            'rokanthemes_brand/general/items_tablet',
            'rokanthemes_brand/general/items_mobile',
            'rokanthemes_storelocator/general/enable',
            'rokanthemes_storelocator/general/title',
            'rokanthemes_storelocator/general/meta_title',
            'rokanthemes_storelocator/general/meta_description',
            'rokanthemes_faq/general/enable',
            'rokanthemes_faq/general/route',
            'rokanthemes_faq/general/title',
            'rokanthemes_faq/general/meta_title',
            'rokanthemes_faq/general/meta_description',
            'rokanthemes_slidebanner/general/enabled',

            // Legacy product modules using wrong sections/groups/fields
            'rokanthemes_newproduct/general/enable',
            'rokanthemes_newproduct/general/title',
            'rokanthemes_newproduct/general/description',
            'rokanthemes_newproduct/general/qty_products',
            'rokanthemes_newproduct/general/autoplay',
            'rokanthemes_newproduct/general/auto',
            'rokanthemes_newproduct/general/items_default',
            'rokanthemes_newproduct/general/items_desktop',
            'rokanthemes_newproduct/general/items_desktop_small',
            'rokanthemes_newproduct/general/items_tablet',
            'rokanthemes_newproduct/general/items_mobile',
            'rokanthemes_newproduct/general/show_price',
            'rokanthemes_newproduct/general/show_addtocart',
            'rokanthemes_newproduct/general/show_wishlist',
            'rokanthemes_newproduct/general/show_rating',
            'rokanthemes_onsaleproduct/general/enable',
            'rokanthemes_onsaleproduct/general/title',
            'rokanthemes_onsaleproduct/general/description',
            'rokanthemes_onsaleproduct/general/qty_products',
            'rokanthemes_onsaleproduct/general/autoplay',
            'rokanthemes_onsaleproduct/general/auto',
            'rokanthemes_onsaleproduct/general/items_default',
            'rokanthemes_onsaleproduct/general/items_desktop',
            'rokanthemes_onsaleproduct/general/items_tablet',
            'rokanthemes_onsaleproduct/general/items_mobile',
            'rokanthemes_onsaleproduct/general/show_price',
            'rokanthemes_onsaleproduct/general/show_addtocart',
            'rokanthemes_bestsellerproduct/general/enable',
            'rokanthemes_bestsellerproduct/general/title',
            'rokanthemes_bestsellerproduct/general/description',
            'rokanthemes_bestsellerproduct/general/qty_products',
            'rokanthemes_bestsellerproduct/general/autoplay',
            'rokanthemes_bestsellerproduct/general/auto',
            'rokanthemes_bestsellerproduct/general/items_default',
            'rokanthemes_bestsellerproduct/general/items_desktop',
            'rokanthemes_bestsellerproduct/general/items_tablet',
            'rokanthemes_bestsellerproduct/general/items_mobile',
            'rokanthemes_bestsellerproduct/general/show_price',
            'rokanthemes_bestsellerproduct/general/show_addtocart',
            'rokanthemes_mostviewedproduct/general/enable',
            'rokanthemes_mostviewedproduct/general/title',
            'rokanthemes_mostviewedproduct/general/description',
            'rokanthemes_mostviewedproduct/general/qty_products',
            'rokanthemes_mostviewedproduct/general/autoplay',
            'rokanthemes_mostviewedproduct/general/auto',
            'rokanthemes_mostviewedproduct/general/items_default',
            'rokanthemes_mostviewedproduct/general/items_desktop',
            'rokanthemes_mostviewedproduct/general/items_tablet',
            'rokanthemes_mostviewedproduct/general/items_mobile',
            'rokanthemes_mostviewedproduct/general/show_price',
            'rokanthemes_mostviewedproduct/general/show_addtocart',
            'rokanthemes_featuredpro/general/enable',
            'rokanthemes_featuredpro/general/title',
            'rokanthemes_featuredpro/general/description',
            'rokanthemes_featuredpro/general/qty_products',
            'rokanthemes_featuredpro/general/autoplay',
            'rokanthemes_featuredpro/general/auto',
            'rokanthemes_featuredpro/general/items_default',
            'rokanthemes_featuredpro/general/items_desktop',
            'rokanthemes_featuredpro/general/items_tablet',
            'rokanthemes_featuredpro/general/items_mobile',
            'rokanthemes_featuredpro/general/show_price',
            'rokanthemes_featuredpro/general/show_addtocart',
            'rokanthemes_toprate/general/enable',
            'rokanthemes_toprate/general/title',
            'rokanthemes_toprate/general/description',
            'rokanthemes_toprate/general/qty_products',
            'rokanthemes_toprate/general/autoplay',
            'rokanthemes_toprate/general/auto',
            'rokanthemes_toprate/general/items_default',
            'rokanthemes_toprate/general/items_desktop',
            'rokanthemes_toprate/general/items_tablet',
            'rokanthemes_toprate/general/items_mobile',
            'rokanthemes_toprate/general/show_price',
            'rokanthemes_toprate/general/show_addtocart',
            'rokanthemes_categorytab/general/enable',
            'rokanthemes_categorytab/general/title',
            'rokanthemes_categorytab/general/description',
            'rokanthemes_categorytab/general/items_default',
            'rokanthemes_categorytab/general/items_desktop',
            'rokanthemes_categorytab/general/items_tablet',
            'rokanthemes_categorytab/general/items_mobile',
        ];
    }
}
