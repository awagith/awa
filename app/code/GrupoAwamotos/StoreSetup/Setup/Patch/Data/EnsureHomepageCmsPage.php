<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Garante que a CMS Page "homepage_ayo_home5" exista no banco.
 *
 * O patch ConfigureAyoHome5Parity seta a config web/default/cms_home_page
 * para "homepage_ayo_home5", mas não cria a página correspondente.
 * Sem essa página, o Magento exibe:
 * "There was no Home CMS page configured or found."
 */
class EnsureHomepageCmsPage implements DataPatchInterface
{
    private const IDENTIFIER = 'homepage_ayo_home5';
    private const PAGE_TITLE = 'Homepage Ayo Home 5';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly PageFactory $pageFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        try {
            $page = $this->pageFactory->create();
            $page->setStoreId(0);
            $page->load(self::IDENTIFIER, 'identifier');

            if (!$page->getId()) {
                $page->setData([
                    'title'       => self::PAGE_TITLE,
                    'identifier'  => self::IDENTIFIER,
                    'content'     => $this->getPageContent(),
                    'is_active'   => 1,
                    'page_layout' => '1column',
                ]);
                $page->setStores([0]);
                $page->save();

                $this->logger->info(
                    sprintf('CMS page "%s" criada pelo Data Patch EnsureHomepageCmsPage.', self::IDENTIFIER)
                );
            } elseif (!$page->getIsActive()) {
                $page->setIsActive(true);
                $page->setTitle(self::PAGE_TITLE);
                $page->setPageLayout('1column');
                $page->save();

                $this->logger->info(
                    sprintf('CMS page "%s" existia mas estava inativa — ativada pelo Data Patch.', self::IDENTIFIER)
                );
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('EnsureHomepageCmsPage: falha ao criar página "%s" — %s', self::IDENTIFIER, $e->getMessage())
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            ConfigureAyoHome5Parity::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * Conteúdo complementar da homepage (blocos AWA exclusivos).
     *
     * O conteúdo principal é renderizado pelo layout do tema ayo_home5
     * via top-home.phtml (Canal A). Aqui incluímos apenas as seções AWA
     * que não duplicam o Canal A: Schema.org, benefícios, selos e FAQ.
     */
    private function getPageContent(): string
    {
        return <<<'HTML'
<div class="ayo-home5-wrapper">
    {{block class="Magento\Cms\Block\Block" block_id="home_schema_org"}}
    {{block class="Magento\Cms\Block\Block" block_id="home_benefits_bar"}}
    {{block class="Magento\Cms\Block\Block" block_id="home_security_seals"}}

    <section class="ayo-home5-section ayo-home5-section--trust-badges">
        {{block class="Magento\Cms\Block\Block" block_id="trust_badges_homepage"}}
    </section>

    <section class="ayo-home5-section container ayo-home5-section--faq">
        {{block class="Magento\Cms\Block\Block" block_id="home_faq_quick"}}
    </section>
</div>
HTML;
    }
}
