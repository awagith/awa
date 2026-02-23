<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ConfigureAyoHome5Parity implements DataPatchInterface
{
    private const HOME_PAGE_IDENTIFIER = 'homepage_ayo_home5';
    private const THEME_FULL_PATH = 'frontend/ayo/ayo_home5';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly WriterInterface $configWriter,
        private readonly ThemeProviderInterface $themeProvider
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        $this->configWriter->save(
            'web/default/cms_home_page',
            self::HOME_PAGE_IDENTIFIER,
            'default',
            0
        );

        $themeId = $this->resolveThemeId();
        if ($themeId !== null) {
            $this->configWriter->save(
                'design/theme/theme_id',
                (string) $themeId,
                'default',
                0
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    private function resolveThemeId(): ?int
    {
        $theme = $this->themeProvider->getThemeByFullPath(self::THEME_FULL_PATH);

        if ($theme === null || !$theme->getId()) {
            return null;
        }

        $themeId = (int) $theme->getId();
        if ($themeId <= 0) {
            throw new LocalizedException(__('Invalid theme ID for %1', self::THEME_FULL_PATH));
        }

        return $themeId;
    }
}
