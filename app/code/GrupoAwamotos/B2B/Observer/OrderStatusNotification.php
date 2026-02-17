<?php
/**
 * Observer para notificar sobre mudanças de status de pedido
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use GrupoAwamotos\B2B\Model\Notification\WhatsAppService;
use Psr\Log\LoggerInterface;

class OrderStatusNotification implements ObserverInterface
{
    private ScopeConfigInterface $scopeConfig;
    private WhatsAppService $whatsAppService;
    private CustomerRepositoryInterface $customerRepository;
    private LoggerInterface $logger;
    private array $notifiedOrders = [];

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WhatsAppService $whatsAppService,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->whatsAppService = $whatsAppService;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        try {
            // Verifica se notificação está habilitada
            if (!$this->isWhatsAppNotificationEnabled()) {
                return;
            }

            $order = $observer->getEvent()->getOrder();

            if (!$order || !$order->getCustomerId()) {
                return;
            }

            // Verifica se houve mudança de status
            $originalStatus = $order->getOrigData('status');
            $newStatus = $order->getStatus();

            if ($originalStatus === $newStatus) {
                return;
            }

            // Evita notificações duplicadas na mesma requisição
            $orderKey = $order->getId() . '_' . $newStatus;
            if (isset($this->notifiedOrders[$orderKey])) {
                return;
            }
            $this->notifiedOrders[$orderKey] = true;

            // Verifica se é cliente B2B
            $customer = $this->customerRepository->getById($order->getCustomerId());
            $b2bGroups = [4, 5, 6, 7];

            if (!in_array($customer->getGroupId(), $b2bGroups)) {
                return;
            }

            // Obtém telefone
            $phoneAttr = $customer->getCustomAttribute('b2b_phone');
            $phone = $phoneAttr ? $phoneAttr->getValue() : '';

            if (empty($phone)) {
                return;
            }

            // Monta dados do pedido
            $trackingInfo = $this->getTrackingInfo($order);

            $orderData = [
                'order_id' => $order->getIncrementId(),
                'customer_name' => $customer->getFirstname(),
                'status' => $this->getStatusLabel($newStatus),
                'tracking_info' => $trackingInfo,
                'customer_phone' => $phone
            ];

            $this->whatsAppService->notifyOrderStatusUpdate($orderData);

        } catch (\Exception $e) {
            $this->logger->error('B2B Order Status Notification Error: ' . $e->getMessage());
        }
    }

    private function isWhatsAppNotificationEnabled(): bool
    {
        $enabled = $this->scopeConfig->getValue(
            'grupoawamotos_b2b/whatsapp/enabled',
            ScopeInterface::SCOPE_STORE
        );

        $typeEnabled = $this->scopeConfig->getValue(
            'grupoawamotos_b2b/whatsapp/notify_order_status',
            ScopeInterface::SCOPE_STORE
        );

        return $enabled && $typeEnabled;
    }

    private function getTrackingInfo($order): string
    {
        $tracks = [];
        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getAllTracks() as $track) {
                $tracks[] = "🚚 *{$track->getTitle()}:* {$track->getNumber()}";
            }
        }

        return !empty($tracks) ? implode("\n", $tracks) : '';
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => '⏳ Pendente',
            'pending_payment' => '💳 Aguardando Pagamento',
            'processing' => '🔄 Em Processamento',
            'complete' => '✅ Entregue',
            'canceled' => '❌ Cancelado',
            'closed' => '🔒 Fechado',
            'holded' => '⏸️ Em Espera',
            'payment_review' => '🔍 Revisão de Pagamento',
            'fraud' => '⚠️ Suspeita de Fraude'
        ];

        return $labels[$status] ?? ucfirst($status);
    }
}
