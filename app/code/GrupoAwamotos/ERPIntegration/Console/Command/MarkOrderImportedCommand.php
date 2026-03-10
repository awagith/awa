<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Console\Command;

use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Marks Magento orders as imported in oc_order_imported (SQL Bridge dedup table).
 *
 * Usage:
 *   erp:bridge:mark-imported --order-id=17      (single order)
 *   erp:bridge:mark-imported --all-acked         (all orders in entity_map)
 *   erp:bridge:mark-imported --status            (show current state)
 */
class MarkOrderImportedCommand extends Command
{
    private SyncLog $syncLogResource;

    public function __construct(SyncLog $syncLogResource)
    {
        parent::__construct();
        $this->syncLogResource = $syncLogResource;
    }

    protected function configure(): void
    {
        $this->setName('erp:bridge:mark-imported')
            ->setDescription('Marca pedidos como importados na tabela oc_order_imported (bridge SQL)')
            ->addOption('order-id', 'o', InputOption::VALUE_REQUIRED, 'ID do pedido Magento (entity_id)')
            ->addOption('all-acked', 'a', InputOption::VALUE_NONE, 'Marca todos os pedidos do entity_map como importados')
            ->addOption('status', 's', InputOption::VALUE_NONE, 'Mostra estado atual das tabelas de dedup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->syncLogResource->getConnection();

        if ($input->getOption('status')) {
            return $this->showStatus($connection, $output);
        }

        $orderId = $input->getOption('order-id');
        $allAcked = $input->getOption('all-acked');

        if (!$orderId && !$allAcked) {
            $output->writeln('<error>Informe --order-id=XX, --all-acked, ou --status</error>');
            return Command::FAILURE;
        }

        if ($orderId) {
            return $this->markSingleOrder($connection, (int) $orderId, $output);
        }

        return $this->markAllAcked($connection, $output);
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function markSingleOrder($connection, int $orderId, OutputInterface $output): int
    {
        $exists = (bool) $connection->fetchOne(
            'SELECT 1 FROM oc_order_imported WHERE order_id = ?',
            [$orderId]
        );

        if ($exists) {
            $output->writeln("<comment>Pedido #{$orderId} já está em oc_order_imported.</comment>");
            return Command::SUCCESS;
        }

        $connection->insert('oc_order_imported', [
            'order_id' => $orderId,
            'date_imported' => date('Y-m-d H:i:s'),
        ]);

        $output->writeln("<info>Pedido #{$orderId} marcado como importado.</info>");
        return Command::SUCCESS;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function markAllAcked($connection, OutputInterface $output): int
    {
        $sql = "INSERT IGNORE INTO oc_order_imported (order_id, date_imported)
                SELECT magento_entity_id, COALESCE(last_sync_at, NOW())
                FROM grupoawamotos_erp_entity_map
                WHERE entity_type = 'order' AND magento_entity_id IS NOT NULL";

        $affected = $connection->exec($sql);

        $output->writeln("<info>{$affected} pedido(s) do entity_map marcados como importados.</info>");
        return Command::SUCCESS;
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function showStatus($connection, OutputInterface $output): int
    {
        $viewCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM oc_order');
        $importedCount = (int) $connection->fetchOne('SELECT COUNT(*) FROM oc_order_imported');
        $entityMapCount = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM grupoawamotos_erp_entity_map WHERE entity_type = 'order'"
        );

        $inMapNotImported = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM grupoawamotos_erp_entity_map em
             LEFT JOIN oc_order_imported oi ON oi.order_id = em.magento_entity_id
             WHERE em.entity_type = 'order' AND oi.order_id IS NULL"
        );

        $output->writeln('');
        $output->writeln('<info>ERP Bridge - Status de Dedup</info>');
        $output->writeln('');
        $output->writeln("  Pedidos na VIEW oc_order:     <comment>{$viewCount}</comment> (pendentes p/ Sectra)");
        $output->writeln("  Pedidos em oc_order_imported: <comment>{$importedCount}</comment> (já importados)");
        $output->writeln("  Pedidos em entity_map:        <comment>{$entityMapCount}</comment> (ACK via REST API)");
        $output->writeln("  Em entity_map SEM imported:   <comment>{$inMapNotImported}</comment> (gap de dedup)");
        $output->writeln('');

        if ($inMapNotImported > 0) {
            $output->writeln('<error>ATENÇÃO: Há pedidos no entity_map que NÃO estão em oc_order_imported!</error>');
            $output->writeln('Execute: <comment>erp:bridge:mark-imported --all-acked</comment> para corrigir.');
        } else {
            $output->writeln('<info>Dedup consistente. Nenhum gap detectado.</info>');
        }

        return Command::SUCCESS;
    }
}
