<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use GrupoAwamotos\StoreSetup\Setup\AyoCanonicalConfig;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Alinha paths de configuração do tema Ayo aos sections/groups/fields reais
 * definidos pelos módulos Rokanthemes instalados neste projeto.
 */
class AlignAyoRokanthemesConfigPaths implements DataPatchInterface
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

        $saved = 0;
        $failed = 0;

        foreach (AyoCanonicalConfig::getDefaultConfigs() as $path => $value) {
            try {
                $this->configWriter->save($path, $value, 'default', 0);
                $saved++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->logger->warning(
                    sprintf(
                        '[AlignAyoRokanthemesConfigPaths] Falha ao salvar "%s": %s',
                        $path,
                        $exception->getMessage()
                    )
                );
            }
        }

        $this->logger->info(
            sprintf(
                '[AlignAyoRokanthemesConfigPaths] Configurações salvas: %d | falhas: %d',
                $saved,
                $failed
            )
        );

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AyoThemeFullConfiguration::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }
}
