<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Console\Command;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Apply Ayo theme default configurations per documentation
 *
 * @see https://ayo.nextsky.co/documentation/
 */
class ApplyAyoDefaultsCommand extends Command
{
    private const OPTION_DRY_RUN = 'dry-run';

    private WriterInterface $configWriter;
    private ScopeConfigInterface $scopeConfig;
    private TypeListInterface $cacheTypeList;

    public function __construct(
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        TypeListInterface $cacheTypeList,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->cacheTypeList = $cacheTypeList;
    }

    protected function configure(): void
    {
        $this->setName('awa:theme:apply-defaults')
            ->setDescription('Aplica configurações padrão do tema Ayo conforme documentação')
            ->addOption(
                self::OPTION_DRY_RUN,
                'd',
                InputOption::VALUE_NONE,
                'Mostra o que seria alterado sem aplicar'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $isDryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);

        $output->writeln('');
        $output->writeln('<info>╔══════════════════════════════════════════════════════════════╗</info>');
        $output->writeln('<info>║   AWA Motos - Configurações Padrão Tema Ayo                  ║</info>');
        $output->writeln('<info>║   Ref: https://ayo.nextsky.co/documentation/                 ║</info>');
        $output->writeln('<info>╚══════════════════════════════════════════════════════════════╝</info>');
        $output->writeln('');

        if ($isDryRun) {
            $output->writeln('<comment>🔍 Modo DRY-RUN: nenhuma alteração será aplicada</comment>');
            $output->writeln('');
        }

        $configs = $this->getDefaultConfigs();

        $applied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($configs as $path => $value) {
            try {
                $currentValue = $this->scopeConfig->getValue(
                    $path,
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT
                );

                if ($currentValue === $value) {
                    $output->writeln("  <comment>⏭️  {$path}</comment> (já configurado)");
                    $skipped++;
                    continue;
                }

                if (!$isDryRun) {
                    $this->configWriter->save(
                        $path,
                        $value,
                        ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                        0
                    );
                }

                $output->writeln("  <info>✅ {$path}</info> = {$value}");
                $applied++;
            } catch (\Exception $e) {
                $output->writeln("  <error>❌ {$path}: {$e->getMessage()}</error>");
                $errors[] = $path;
            }
        }

        $output->writeln('');
        $output->writeln('<info>╔══════════════════════════════════════════════════════════════╗</info>');
        $output->writeln('<info>║                        RESUMO                                ║</info>');
        $output->writeln('<info>╠══════════════════════════════════════════════════════════════╣</info>');
        $output->writeln(sprintf('<info>║  ✅ Aplicadas: %-45d║</info>', $applied));
        $output->writeln(sprintf('<info>║  ⏭️  Já configuradas: %-38d║</info>', $skipped));
        $output->writeln(sprintf('<info>║  ❌ Erros: %-50d║</info>', count($errors)));
        $output->writeln('<info>╚══════════════════════════════════════════════════════════════╝</info>');

        if ($applied > 0 && !$isDryRun) {
            $this->cacheTypeList->cleanType('config');
            $output->writeln('');
            $output->writeln('<info>✅ Cache de configuração limpo automaticamente.</info>');
        }

        if (!empty($errors)) {
            $output->writeln('');
            $output->writeln('<error>❌ Caminhos com erro (podem não existir nesta versão):</error>');
            foreach ($errors as $errorPath) {
                $output->writeln("   - {$errorPath}");
            }
        }

        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * Get all default configurations per Ayo documentation
     *
     * @return array<string, string>
     */
    private function getDefaultConfigs(): array
    {
        return [
            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - General
            // ═══════════════════════════════════════════════════════════════
            'themeoption/general/layout' => 'fullwidth',
            'themeoption/general/copyright' => '© ' . date('Y') . ' AWA Motos - Todos os direitos reservados',

            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - Font (Documentação: Custom Font Settings)
            // ═══════════════════════════════════════════════════════════════
            'themeoption/font/custom' => '1',
            'themeoption/font/font_size' => '14px',
            'themeoption/font/font_family' => 'google',
            'themeoption/font/google_font_family' => 'Roboto',

            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - Colors (Paleta AWA #b73337)
            // ═══════════════════════════════════════════════════════════════
            'themeoption/colors/custom' => '1',
            'themeoption/colors/text_color' => '#333333',
            'themeoption/colors/link_color' => '#b73337',
            'themeoption/colors/link_hover_color' => '#8d2729',
            'themeoption/colors/button_text_color' => '#ffffff',
            'themeoption/colors/button_bg_color' => '#b73337',
            'themeoption/colors/button_hover_text_color' => '#ffffff',
            'themeoption/colors/button_hover_bg_color' => '#8d2729',

            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - Header (Documentação: Sticky Header)
            // ═══════════════════════════════════════════════════════════════
            'themeoption/header/sticky_enable' => '1',
            'themeoption/header/sticky_select_bg_color' => 'custom',
            'themeoption/header/sticky_bg_color_custom' => '#ffffff',

            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - Footer
            // ═══════════════════════════════════════════════════════════════
            'themeoption/footer/footer_menu_mobile' => '1',

            // ═══════════════════════════════════════════════════════════════
            // THEME OPTIONS - Newsletter Popup
            // ═══════════════════════════════════════════════════════════════
            'themeoption/newsletter_popup/enable' => '1',
            'themeoption/newsletter_popup/width' => '600',
            'themeoption/newsletter_popup/height' => '400',
            'themeoption/newsletter_popup/delay' => '5000',
            'themeoption/newsletter_popup/cookie_time' => '7',

            // ═══════════════════════════════════════════════════════════════
            // CUSTOM MENU (Documentação: Menu Settings)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_custommenu/general/enable' => '1',
            'rokanthemes_custommenu/general/menu_type' => 'fullwidth',
            'rokanthemes_custommenu/general/visible_depth' => '3',

            // ═══════════════════════════════════════════════════════════════
            // VERTICAL MENU (Documentação: Vertical Menu Settings)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_verticalmenu/general/enable' => '1',
            'rokanthemes_verticalmenu/general/menu_type' => 'fullwidth',
            'rokanthemes_verticalmenu/general/visible_depth' => '3',
            'rokanthemes_verticalmenu/general/limit_cat' => '10',

            // ═══════════════════════════════════════════════════════════════
            // SLIDEBANNER (Documentação: Slideshow)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_slidebanner/general/enabled' => '1',

            // ═══════════════════════════════════════════════════════════════
            // LAYERED AJAX (Documentação: Layered Navigation)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_layeredajax/general/enable' => '1',
            'rokanthemes_layeredajax/general/price_slider' => '1',
            'rokanthemes_layeredajax/general/open_all_tab' => '0',

            // ═══════════════════════════════════════════════════════════════
            // TESTIMONIALS (Documentação: Testimonials Module)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_testimonials/general/enable' => '1',
            'rokanthemes_testimonials/general/title' => 'O que nossos clientes dizem',
            'rokanthemes_testimonials/general/auto' => '1',
            'rokanthemes_testimonials/general/items_default' => '3',
            'rokanthemes_testimonials/general/items_desktop' => '3',
            'rokanthemes_testimonials/general/items_tablet' => '2',
            'rokanthemes_testimonials/general/items_mobile' => '1',

            // ═══════════════════════════════════════════════════════════════
            // BLOG (Documentação: Blog Post Module)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_blog/general/enabled' => '1',
            'rokanthemes_blog/general/route' => 'blog',
            'rokanthemes_blog/general/title' => 'Blog AWA Motos',
            'rokanthemes_blog/sidebar/recent_posts' => '5',
            'rokanthemes_blog/sidebar/most_viewed' => '5',

            // ═══════════════════════════════════════════════════════════════
            // ONE PAGE CHECKOUT (Documentação: OPC + Terms)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_onepagecheckout/general/enable' => '1',
            'rokanthemes_onepagecheckout/general/show_coupon' => '1',
            'rokanthemes_onepagecheckout/general/show_comment' => '1',
            'rokanthemes_onepagecheckout/terms/enable' => '1',
            'rokanthemes_onepagecheckout/terms/checkbox_text' => 'Li e aceito os Termos e Condições',
            'rokanthemes_onepagecheckout/terms/title_warning' => 'Atenção!',
            'rokanthemes_onepagecheckout/terms/content_warning' => 'Você deve aceitar os termos e condições para finalizar a compra.',

            // ═══════════════════════════════════════════════════════════════
            // SUPERDEALS (Documentação: SuperDeals)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_superdeals/general/enable' => '1',

            // ═══════════════════════════════════════════════════════════════
            // QUICKVIEW (Documentação: Quick View)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_quickview/general/enable' => '1',

            // ═══════════════════════════════════════════════════════════════
            // AJAX SUITE (Add to Cart, Wishlist, Compare)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_ajaxsuite/general/enable' => '1',
            'rokanthemes_ajaxsuite/general/popup_addtocart' => '1',
            'rokanthemes_ajaxsuite/general/popup_wishlist' => '1',
            'rokanthemes_ajaxsuite/general/popup_compare' => '1',

            // ═══════════════════════════════════════════════════════════════
            // SEARCH AUTOCOMPLETE (Documentação: Search Suite)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_searchsuiteautocomplete/general/enable' => '1',
            'rokanthemes_searchsuiteautocomplete/general/min_chars' => '2',
            'rokanthemes_searchsuiteautocomplete/general/max_results' => '10',
            'rokanthemes_searchsuiteautocomplete/general/show_product_image' => '1',
            'rokanthemes_searchsuiteautocomplete/general/show_product_price' => '1',

            // ═══════════════════════════════════════════════════════════════
            // SEARCH BY CATEGORY
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_searchbycat/general/enable' => '1',

            // ═══════════════════════════════════════════════════════════════
            // PRODUCT MODULES (Documentação: ProductTab)
            // ═══════════════════════════════════════════════════════════════
            // New Products
            'rokanthemes_newproduct/general/enable' => '1',
            'rokanthemes_newproduct/general/title' => 'Novos Produtos',
            'rokanthemes_newproduct/general/auto' => '1',
            'rokanthemes_newproduct/general/show_price' => '1',
            'rokanthemes_newproduct/general/show_addtocart' => '1',
            'rokanthemes_newproduct/general/show_wishlist' => '1',
            'rokanthemes_newproduct/general/show_rating' => '1',
            'rokanthemes_newproduct/general/qty_products' => '12',
            'rokanthemes_newproduct/general/items_default' => '4',
            'rokanthemes_newproduct/general/items_desktop' => '4',
            'rokanthemes_newproduct/general/items_desktop_small' => '3',
            'rokanthemes_newproduct/general/items_tablet' => '2',
            'rokanthemes_newproduct/general/items_mobile' => '1',

            // Onsale Products
            'rokanthemes_onsaleproduct/general/enable' => '1',
            'rokanthemes_onsaleproduct/general/title' => 'Ofertas Especiais',
            'rokanthemes_onsaleproduct/general/auto' => '1',
            'rokanthemes_onsaleproduct/general/show_price' => '1',
            'rokanthemes_onsaleproduct/general/show_addtocart' => '1',
            'rokanthemes_onsaleproduct/general/qty_products' => '12',
            'rokanthemes_onsaleproduct/general/items_default' => '4',
            'rokanthemes_onsaleproduct/general/items_desktop' => '4',
            'rokanthemes_onsaleproduct/general/items_tablet' => '2',
            'rokanthemes_onsaleproduct/general/items_mobile' => '1',

            // Bestseller Products
            'rokanthemes_bestsellerproduct/general/enable' => '1',
            'rokanthemes_bestsellerproduct/general/title' => 'Mais Vendidos',
            'rokanthemes_bestsellerproduct/general/auto' => '1',
            'rokanthemes_bestsellerproduct/general/show_price' => '1',
            'rokanthemes_bestsellerproduct/general/show_addtocart' => '1',
            'rokanthemes_bestsellerproduct/general/qty_products' => '12',
            'rokanthemes_bestsellerproduct/general/items_default' => '4',
            'rokanthemes_bestsellerproduct/general/items_desktop' => '4',
            'rokanthemes_bestsellerproduct/general/items_tablet' => '2',
            'rokanthemes_bestsellerproduct/general/items_mobile' => '1',

            // Mostviewed Products
            'rokanthemes_mostviewedproduct/general/enable' => '1',
            'rokanthemes_mostviewedproduct/general/title' => 'Mais Vistos',
            'rokanthemes_mostviewedproduct/general/auto' => '1',
            'rokanthemes_mostviewedproduct/general/show_price' => '1',
            'rokanthemes_mostviewedproduct/general/show_addtocart' => '1',
            'rokanthemes_mostviewedproduct/general/qty_products' => '12',
            'rokanthemes_mostviewedproduct/general/items_default' => '4',
            'rokanthemes_mostviewedproduct/general/items_desktop' => '4',
            'rokanthemes_mostviewedproduct/general/items_tablet' => '2',
            'rokanthemes_mostviewedproduct/general/items_mobile' => '1',

            // Featured Products
            'rokanthemes_featuredpro/general/enable' => '1',
            'rokanthemes_featuredpro/general/title' => 'Produtos em Destaque',
            'rokanthemes_featuredpro/general/auto' => '1',
            'rokanthemes_featuredpro/general/show_price' => '1',
            'rokanthemes_featuredpro/general/show_addtocart' => '1',
            'rokanthemes_featuredpro/general/qty_products' => '12',
            'rokanthemes_featuredpro/general/items_default' => '4',
            'rokanthemes_featuredpro/general/items_desktop' => '4',
            'rokanthemes_featuredpro/general/items_tablet' => '2',
            'rokanthemes_featuredpro/general/items_mobile' => '1',

            // Top Rate Products
            'rokanthemes_toprate/general/enable' => '1',
            'rokanthemes_toprate/general/title' => 'Melhor Avaliados',
            'rokanthemes_toprate/general/auto' => '1',
            'rokanthemes_toprate/general/show_price' => '1',
            'rokanthemes_toprate/general/show_addtocart' => '1',
            'rokanthemes_toprate/general/qty_products' => '12',
            'rokanthemes_toprate/general/items_default' => '4',
            'rokanthemes_toprate/general/items_desktop' => '4',
            'rokanthemes_toprate/general/items_tablet' => '2',
            'rokanthemes_toprate/general/items_mobile' => '1',

            // Price Countdown
            'rokanthemes_pricecountdown/general/enable' => '1',
            'rokanthemes_pricecountdown/general/title' => 'Ofertas por Tempo Limitado',
            'rokanthemes_pricecountdown/general/auto' => '1',
            'rokanthemes_pricecountdown/general/show_price' => '1',
            'rokanthemes_pricecountdown/general/show_addtocart' => '1',
            'rokanthemes_pricecountdown/general/qty_products' => '8',

            // Category Tab
            'rokanthemes_categorytab/general/enable' => '1',

            // ═══════════════════════════════════════════════════════════════
            // BRAND (Documentação: Brand Management)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_brand/general/enable' => '1',
            'rokanthemes_brand/general/route' => 'brand',

            // ═══════════════════════════════════════════════════════════════
            // FAQ
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_faq/general/enable' => '1',
            'rokanthemes_faq/general/route' => 'faq',

            // ═══════════════════════════════════════════════════════════════
            // STORE LOCATOR
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_storelocator/general/enable' => '1',

            // ═══════════════════════════════════════════════════════════════
            // INSTAGRAM FEED (desabilitado - requer API token)
            // ═══════════════════════════════════════════════════════════════
            'rokanthemes_instagram/general/enable' => '0',
        ];
    }
}
