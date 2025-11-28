<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Console\Command;

use GrupoAwamotos\StoreSetup\Setup\StoreConfigurator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StoreSetupCommand extends Command
{
    private StoreConfigurator $configurator;

    public function __construct(StoreConfigurator $configurator, ?string $name = null)
    {
        parent::__construct($name);
        $this->configurator = $configurator;
    }

    protected function configure(): void
    {
        $this->setName('grupoawamotos:store:setup')
            ->setDescription('Aplica blocos CMS, homepage, categorias e configurações do tema para a loja base Ayo.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Iniciando configuração completa da loja...</info>');
        $this->configurator->run($output);
        $output->writeln('<info>Configuração finalizada com sucesso.</info>');

        return Command::SUCCESS;
    }
}
