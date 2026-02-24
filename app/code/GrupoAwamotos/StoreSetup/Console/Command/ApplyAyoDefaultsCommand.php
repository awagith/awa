<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Console\Command;

use GrupoAwamotos\StoreSetup\Setup\AyoCanonicalConfig;
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
        return AyoCanonicalConfig::getDefaultConfigs();
    }
}
