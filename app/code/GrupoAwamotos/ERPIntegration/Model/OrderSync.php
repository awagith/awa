<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\OrderSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class OrderSync implements OrderSyncInterface
{
    private ConnectionInterface $connection;
    private Helper $helper;
    private SyncLogResource $syncLogResource;
    private CustomerSync $customerSync;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        SyncLogResource $syncLogResource,
        CustomerSync $customerSync,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->syncLogResource = $syncLogResource;
        $this->customerSync = $customerSync;
        $this->logger = $logger;
    }

    public function sendOrder(OrderInterface $order): array
    {
        $result = ['success' => false, 'erp_order_id' => null, 'message' => ''];

        try {
            // Find ERP customer by taxvat
            $taxvat = $order->getCustomerTaxvat();
            $erpCustomer = null;
            $erpClientCode = 0;

            if ($taxvat) {
                $erpCustomer = $this->customerSync->getErpCustomerByTaxvat($taxvat);
                if ($erpCustomer) {
                    $erpClientCode = (int) $erpCustomer['CODIGO'];
                }
            }

            // Also check entity map
            if ($erpClientCode === 0 && $order->getCustomerId()) {
                $erpCode = $this->syncLogResource->getErpCodeByMagentoId('customer', (int) $order->getCustomerId());
                if ($erpCode) {
                    $erpClientCode = (int) $erpCode;
                }
            }

            $filial = $this->helper->getStockFilial();

            // Insert order header
            $orderSql = "INSERT INTO VE_PEDIDO (
                            FILIAL, DTPEDIDO, CLIENTE, VENDEDOR, STATUS,
                            VLRBRUTO, VLRDESCONTO, VLRTOTAL, VLRFRETE,
                            PEDIDOWEB, PEDIDOCLI,
                            ENTENDERECO, ENTBAIRRO, ENTCIDADE, ENTCEP, ENTUF,
                            USERNAME1, USERDATE1
                         ) VALUES (
                            :filial, GETDATE(), :cliente, :vendedor, 'A',
                            :vlrbruto, :vlrdesconto, :vlrtotal, :vlrfrete,
                            :pedidoweb, :pedidocli,
                            :entendereco, :entbairro, :entcidade, :entcep, :entuf,
                            'MAGENTO', GETDATE()
                         );
                         SELECT SCOPE_IDENTITY() AS new_id;";

            $shipping = $order->getShippingAddress();

            $params = [
                ':filial' => $filial,
                ':cliente' => $erpClientCode,
                ':vendedor' => 0,
                ':vlrbruto' => (float) $order->getSubtotal(),
                ':vlrdesconto' => abs((float) $order->getDiscountAmount()),
                ':vlrtotal' => (float) $order->getGrandTotal(),
                ':vlrfrete' => (float) $order->getShippingAmount(),
                ':pedidoweb' => $order->getIncrementId(),
                ':pedidocli' => $order->getIncrementId(),
                ':entendereco' => $shipping ? implode(', ', $shipping->getStreet()) : '',
                ':entbairro' => '',
                ':entcidade' => $shipping ? $shipping->getCity() : '',
                ':entcep' => $shipping ? $shipping->getPostcode() : '',
                ':entuf' => $shipping ? $shipping->getRegionCode() : '',
            ];

            $newOrder = $this->connection->fetchOne($orderSql, $params);
            $erpOrderId = $newOrder ? (int) ($newOrder['new_id'] ?? 0) : 0;

            if ($erpOrderId > 0) {
                // Insert order items
                foreach ($order->getItems() as $item) {
                    if ($item->getParentItemId()) {
                        continue; // Skip child items
                    }

                    $itemSql = "INSERT INTO VE_PEDIDOITENS (
                                    PEDIDO, MATERIAL, QTDE, VLRUNITARIO, VLRTOTAL,
                                    VLRDESCONTO, VLRBRUTO
                                ) VALUES (
                                    :pedido, :material, :qtde, :vlrunitario, :vlrtotal,
                                    :vlrdesconto, :vlrbruto
                                )";

                    $this->connection->execute($itemSql, [
                        ':pedido' => $erpOrderId,
                        ':material' => $item->getSku(),
                        ':qtde' => (float) $item->getQtyOrdered(),
                        ':vlrunitario' => (float) $item->getPrice(),
                        ':vlrtotal' => (float) $item->getRowTotal(),
                        ':vlrdesconto' => abs((float) $item->getDiscountAmount()),
                        ':vlrbruto' => (float) $item->getRowTotal() + abs((float) $item->getDiscountAmount()),
                    ]);
                }

                $result['success'] = true;
                $result['erp_order_id'] = $erpOrderId;
                $result['message'] = 'Pedido enviado ao ERP com sucesso. ID ERP: ' . $erpOrderId;

                $this->syncLogResource->addLog(
                    'order',
                    'export',
                    'success',
                    $result['message'],
                    (string) $erpOrderId,
                    (int) $order->getEntityId()
                );

                $this->syncLogResource->setEntityMap(
                    'order',
                    (string) $erpOrderId,
                    (int) $order->getEntityId()
                );
            } else {
                $result['message'] = 'Nao foi possivel obter o ID do pedido criado no ERP.';
                $this->syncLogResource->addLog(
                    'order',
                    'export',
                    'error',
                    $result['message'],
                    null,
                    (int) $order->getEntityId()
                );
            }
        } catch (\Exception $e) {
            $result['message'] = 'Erro ao enviar pedido ao ERP: ' . $e->getMessage();
            $this->logger->error('[ERP] Order sync error: ' . $e->getMessage());
            $this->syncLogResource->addLog(
                'order',
                'export',
                'error',
                $e->getMessage(),
                null,
                (int) $order->getEntityId()
            );
        }

        return $result;
    }

    public function getOrderHistory(int $erpClientCode, int $limit = 50): array
    {
        try {
            $sql = "SELECT TOP (:limit)
                        p.CODIGO AS pedido_id,
                        p.DTPEDIDO AS data_pedido,
                        p.VLRTOTAL AS valor_total,
                        p.STATUS AS status,
                        (SELECT COUNT(*) FROM VE_PEDIDOITENS pi WHERE pi.PEDIDO = p.CODIGO) AS total_itens
                    FROM VE_PEDIDO p
                    WHERE p.CLIENTE = :cliente
                    ORDER BY p.DTPEDIDO DESC";

            return $this->connection->query($sql, [
                ':limit' => $limit,
                ':cliente' => $erpClientCode,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Order history error: ' . $e->getMessage());
            return [];
        }
    }
}
