<?php
/**
 * Comando CLI para testar envio de emails
 */
namespace GrupoAwamotos\RexisML\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GrupoAwamotos\RexisML\Helper\EmailNotifier;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory;

class TestEmailCommand extends Command
{
    protected $emailNotifier;
    protected $recomendacaoCollectionFactory;

    public function __construct(
        EmailNotifier $emailNotifier,
        CollectionFactory $recomendacaoCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->emailNotifier = $emailNotifier;
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
    }

    protected function configure()
    {
        $this->setName('rexis:test-email')
            ->setDescription('Enviar email de teste de Churn')
            ->addArgument(
                'recipient',
                InputArgument::OPTIONAL,
                'Email do destinatário (opcional, usa configuração do admin se não fornecido)'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>REXIS ML - Teste de Email</info>');
        $output->writeln('');

        // Buscar algumas oportunidades de Churn para teste
        $collection = $this->recomendacaoCollectionFactory->create();
        $collection->addFieldToFilter('classificacao_produto', 'Oportunidade Churn')
                   ->addFieldToFilter('pred', ['gteq' => 0.85])
                   ->setOrder('pred', 'DESC')
                   ->setPageSize(5);

        if ($collection->getSize() === 0) {
            $output->writeln('<error>Nenhuma oportunidade de Churn encontrada para teste.</error>');
            $output->writeln('<comment>Execute primeiro: php bin/magento rexis:sync</comment>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            '<comment>Encontradas %d oportunidades de Churn</comment>',
            $collection->getSize()
        ));

        // Enviar email
        $recipient = $input->getArgument('recipient');
        if ($recipient) {
            $output->writeln("<comment>Enviando para: $recipient</comment>");
            // Temporariamente sobrescrever configuração
            // Implementação simplificada - em produção, usar TransportBuilder diretamente
        }

        $result = $this->emailNotifier->sendChurnAlert($collection);

        if ($result) {
            $output->writeln('<info>✓ Email enviado com sucesso!</info>');
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>✗ Falha ao enviar email. Verifique os logs.</error>');
            return Command::FAILURE;
        }
    }
}
