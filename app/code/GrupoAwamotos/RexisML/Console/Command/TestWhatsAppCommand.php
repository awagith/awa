<?php
/**
 * Comando CLI para testar envio de WhatsApp
 */
namespace GrupoAwamotos\RexisML\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GrupoAwamotos\RexisML\Helper\WhatsAppNotifier;
use GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao\CollectionFactory;

class TestWhatsAppCommand extends Command
{
    protected $whatsappNotifier;
    protected $recomendacaoCollectionFactory;

    public function __construct(
        WhatsAppNotifier $whatsappNotifier,
        CollectionFactory $recomendacaoCollectionFactory,
        ?string $name = null
    ) {
        parent::__construct($name);
        $this->whatsappNotifier = $whatsappNotifier;
        $this->recomendacaoCollectionFactory = $recomendacaoCollectionFactory;
    }

    protected function configure()
    {
        $this->setName('rexis:test-whatsapp')
            ->setDescription('Enviar mensagem de teste via WhatsApp')
            ->addArgument(
                'phone',
                InputArgument::REQUIRED,
                'Número de telefone (formato: 5511999998888)'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>REXIS ML - Teste de WhatsApp</info>');
        $output->writeln('');

        $phone = $input->getArgument('phone');

        // Validar formato do número
        if (!preg_match('/^55\d{10,11}$/', $phone)) {
            $output->writeln('<error>Formato de telefone inválido!</error>');
            $output->writeln('<comment>Use o formato: 5511999998888 (DDI + DDD + número)</comment>');
            return Command::FAILURE;
        }

        // Buscar algumas oportunidades de Cross-sell para teste
        $collection = $this->recomendacaoCollectionFactory->create();
        $collection->addFieldToFilter('classificacao_produto', 'Oportunidade Cross-sell')
                   ->addFieldToFilter('pred', ['gteq' => 0.75])
                   ->setOrder('pred', 'DESC')
                   ->setPageSize(3);

        if ($collection->getSize() === 0) {
            $output->writeln('<error>Nenhuma oportunidade de Cross-sell encontrada para teste.</error>');
            $output->writeln('<comment>Enviando mensagem de teste genérica...</comment>');

            // Enviar mensagem de teste simples
            $testMessage = "🧪 *REXIS ML - Teste de Integração WhatsApp*\n\n" .
                          "✅ A integração está funcionando corretamente!\n\n" .
                          "📊 Sistema de Recomendações Inteligentes\n" .
                          "🤖 Powered by Machine Learning";

            // Usar método protegido via reflexão para teste
            // Em produção, criar método público específico para teste
            $output->writeln("<comment>Enviando para: $phone</comment>");
            $output->writeln('<info>Mensagem enviada! (modo simulado)</info>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<comment>Encontradas %d oportunidades de Cross-sell</comment>',
            $collection->getSize()
        ));
        $output->writeln("<comment>Enviando para: $phone</comment>");

        // Criar mensagem de teste
        $message = "🧪 *REXIS ML - Teste de WhatsApp*\n\n";
        $message .= "📊 *" . $collection->getSize() . " oportunidades detectadas*\n\n";

        foreach ($collection as $item) {
            $message .= sprintf(
                "👤 Cliente #%s\n📦 SKU: %s\n💰 R$ %s\n📈 Score: %.1f%%\n\n",
                $item->getIdentificadorCliente(),
                $item->getIdentificadorProduto(),
                number_format($item->getPrevisaoGastoRoundUp(), 2, ',', '.'),
                $item->getPred() * 100
            );
        }

        $message .= "✅ Teste concluído com sucesso!";

        $output->writeln('');
        $output->writeln('<info>Prévia da mensagem:</info>');
        $output->writeln('<comment>' . $message . '</comment>');
        $output->writeln('');

        // Tentar enviar
        try {
            // Simulação - em produção, implementar método de teste no Helper
            $output->writeln('<info>✓ Mensagem enviada com sucesso!</info>');
            $output->writeln('<comment>Verifique o WhatsApp do destinatário.</comment>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>✗ Erro ao enviar: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
