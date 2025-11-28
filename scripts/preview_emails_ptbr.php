<?php
use Magento\Email\Model\TemplateFactory;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory as TemplateCollectionFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;
use Magento\Framework\Translate\Inline\StateInterface as InlineTranslationState;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Item as ShipmentItem;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Creditmemo\Item as CreditmemoItem;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

/** @var State $state */
$state = $objectManager->get(State::class);
try {
    $state->setAreaCode('frontend');
} catch (\Throwable $e) {
}

/** @var TemplateFactory $templateFactory */
$templateFactory = $objectManager->get(TemplateFactory::class);
/** @var InlineTranslationState $inlineTranslation */
$inlineTranslation = $objectManager->get(InlineTranslationState::class);
/** @var AddressRenderer $addressRenderer */
$addressRenderer = $objectManager->get(AddressRenderer::class);
/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
/** @var TemplateCollectionFactory $templateCollectionFactory */
$templateCollectionFactory = $objectManager->get(TemplateCollectionFactory::class);
$scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
$store = $storeManager->getDefaultStoreView();

$inlineTranslation->suspend();

$sampleData = buildSampleData($objectManager, $addressRenderer, $store);
$previews = getPreviewMap($sampleData, $store, $scopeConfig, $objectManager);

$outputDir = BP . '/relatorios/email_previews';
if (!is_dir($outputDir) && !mkdir($outputDir, 0775, true) && !is_dir($outputDir)) {
    throw new \RuntimeException('Não foi possível criar o diretório de saída: ' . $outputDir);
}

foreach ($previews as $templateCode => $variables) {
    $collection = $templateCollectionFactory->create();
    $collection->addFieldToFilter('template_code', $templateCode)->setPageSize(1);
    $templateModel = $collection->getFirstItem();
    if (!$templateModel->getId()) {
        echo sprintf("[AVISO] Template %s não encontrado. Execute scripts/configurar_emails_ptbr.php\n", $templateCode);
        continue;
    }
    $template = $templateFactory->create()->load($templateModel->getId());

    try {
        $html = $template->getProcessedTemplate($variables);
        $file = sprintf('%s/%s.html', $outputDir, $templateCode);
        file_put_contents($file, $html);
        echo sprintf("[OK] Preview gerado em %s\n", $file);
    } catch (\Throwable $e) {
        echo sprintf("[ERRO] Falha ao gerar preview de %s: %s\n", $templateCode, $e->getMessage());
    }
}

$inlineTranslation->resume();

echo "Concluído.\n";

function buildSampleData($objectManager, AddressRenderer $addressRenderer, $store): array
{
    /** @var Order $order */
    $order = $objectManager->create(Order::class);
    $order->setEntityId(9999);
    $order->setIncrementId('100000987');
    $order->setCustomerFirstname('João');
    $order->setCustomerLastname('Silva');
    $order->setCustomerEmail('cliente+teste@awamotos.com.br');
    $order->setCustomerIsGuest(false);
    $order->setStoreId((int) $store->getId());
    $order->setState('processing');
    $order->setStatus('processing');
    $order->setCreatedAt(date('Y-m-d H:i:s'));
    $order->setOrderCurrencyCode('BRL');
    $order->setGrandTotal(1579.80);
    $order->setBaseGrandTotal(1579.80);
    $order->setSubtotal(1539.90);
    $order->setShippingAmount(39.90);
    $order->setShippingDescription('Entrega Expressa (Correios SEDEX)');

    $billing = createSampleAddress($objectManager, [
        'firstname' => 'João',
        'lastname' => 'Silva',
        'street' => ['Rua das Flores, 123', 'Centro'],
        'city' => 'São Paulo',
        'region' => 'SP',
        'postcode' => '01000-000',
        'country_id' => 'BR',
        'telephone' => '(11) 99999-0000',
    ]);
    $shipping = createSampleAddress($objectManager, [
        'firstname' => 'João',
        'lastname' => 'Silva',
        'street' => ['Rua das Flores, 123', 'Centro'],
        'city' => 'São Paulo',
        'region' => 'SP',
        'postcode' => '01000-000',
        'country_id' => 'BR',
        'telephone' => '(11) 99999-0000',
    ]);

    $order->setBillingAddress($billing);
    $order->setShippingAddress($shipping);

    $items = [];
    $items[] = createSampleOrderItem($objectManager, $order, [
        'name' => 'Capacete Brave X Carbon',
        'sku' => 'CAP-BRAVE-X',
        'qty_ordered' => 1,
        'price' => 899.90,
        'row_total' => 899.90,
    ]);
    $items[] = createSampleOrderItem($objectManager, $order, [
        'name' => 'Luvas Ride Pro',
        'sku' => 'LUV-RIDE-PRO',
        'qty_ordered' => 1,
        'price' => 639.90,
        'row_total' => 639.90,
    ]);
    $order->setItems($items);

    /** @var Invoice $invoice */
    $invoice = $objectManager->create(Invoice::class);
    $invoice->setIncrementId('000000432');
    $invoice->setOrder($order);
    $invoice->setBillingAddress($billing);
    $invoice->setShippingAddress($shipping);
    $invoiceItems = [];
    foreach ($items as $item) {
        /** @var InvoiceItem $invoiceItem */
        $invoiceItem = $objectManager->create(InvoiceItem::class);
        $invoiceItem->setOrderItem($item);
        $invoiceItem->setQty($item->getQtyOrdered());
        $invoiceItems[] = $invoiceItem;
    }
    $invoice->setItems($invoiceItems);

    /** @var Shipment $shipment */
    $shipment = $objectManager->create(Shipment::class);
    $shipment->setIncrementId('000000765');
    $shipment->setOrder($order);
    $shipment->setShippingAddress($shipping);
    $shipmentItems = [];
    foreach ($items as $item) {
        /** @var ShipmentItem $shipmentItem */
        $shipmentItem = $objectManager->create(ShipmentItem::class);
        $shipmentItem->setOrderItem($item)->setQty($item->getQtyOrdered());
        $shipmentItems[] = $shipmentItem;
    }
    $shipment->setItems($shipmentItems);
    $track = $objectManager->create(Track::class);
    $track->setNumber('BR123456789BR')->setTitle('Correios - SEDEX');
    $shipment->addTrack($track);

    /** @var Creditmemo $creditmemo */
    $creditmemo = $objectManager->create(Creditmemo::class);
    $creditmemo->setIncrementId('000000215');
    $creditmemo->setOrder($order);
    $creditmemoItems = [];
    foreach ($items as $item) {
        /** @var CreditmemoItem $creditmemoItem */
        $creditmemoItem = $objectManager->create(CreditmemoItem::class);
        $creditmemoItem->setOrderItem($item)->setQty($item->getQtyOrdered());
        $creditmemoItems[] = $creditmemoItem;
    }
    $creditmemo->setItems($creditmemoItems);

    return [
        'order' => $order,
        'invoice' => $invoice,
        'shipment' => $shipment,
        'creditmemo' => $creditmemo,
        'formatted_billing' => $addressRenderer->format($billing, 'html'),
        'formatted_shipping' => $addressRenderer->format($shipping, 'html'),
    ];
}

function createSampleAddress($objectManager, array $data): Address
{
    /** @var Address $address */
    $address = $objectManager->create(Address::class);
    foreach ($data as $key => $value) {
        $address->setData($key, $value);
    }
    return $address;
}

function createSampleOrderItem($objectManager, Order $order, array $data): OrderItem
{
    /** @var OrderItem $item */
    $item = $objectManager->create(OrderItem::class);
    $item->setOrder($order);
    $item->setProductType('simple');
    $item->setQtyOrdered($data['qty_ordered']);
    $item->setName($data['name']);
    $item->setSku($data['sku']);
    $item->setPrice($data['price']);
    $item->setRowTotal($data['row_total']);
    $item->setBasePrice($data['price']);
    $item->setBaseRowTotal($data['row_total']);
    return $item;
}

function getPreviewMap(array $sampleData, $store, $scopeConfig, $objectManager): array
{
    $storePhone = $scopeConfig->getValue('general/store_information/phone', ScopeInterface::SCOPE_STORE) ?: '(11) 4002-8922';
    $storeEmail = $scopeConfig->getValue('trans_email/ident_sales/email', ScopeInterface::SCOPE_STORE) ?: 'contato@awamotos.com.br';
    $storeHours = 'Seg a Sex - 08h às 18h';

    $order = $sampleData['order'];
    $invoice = $sampleData['invoice'];
    $shipment = $sampleData['shipment'];
    $creditmemo = $sampleData['creditmemo'];

    $orderVars = [
        'store' => $store,
        'order' => $order,
        'order_id' => 9999,
        'order_data' => [
            'customer_name' => $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname(),
            'is_not_virtual' => 1,
            'email_customer_note' => 'Mensagem automática de teste.',
        ],
        'formattedBillingAddress' => $sampleData['formatted_billing'],
        'formattedShippingAddress' => $sampleData['formatted_shipping'],
        'payment_html' => '<p><strong>Pix</strong><br/>Pagamento aprovado em 27/11/2025.</p>',
        'created_at_formatted' => date('d/m/Y H:i'),
        'shipping_msg' => 'Entrega prevista em até 5 dias úteis.',
        'store_phone' => $storePhone,
        'store_email' => $storeEmail,
        'store_hours' => $storeHours,
    ];

    $shipmentVars = $orderVars;
    $shipmentVars['shipment'] = $shipment;
    $shipmentVars['track_number'] = 'BR123456789BR';
    $shipmentVars['track_url'] = 'https://rastreamento.correios.com.br/app/index.php';

    $invoiceVars = $orderVars;
    $invoiceVars['invoice'] = $invoice;

    $creditmemoVars = $orderVars;
    $creditmemoVars['creditmemo'] = $creditmemo;

    $customer = new DataObject([
        'name' => 'João Silva',
        'email' => 'cliente+teste@awamotos.com.br',
        'id' => 555,
        'rp_token' => 'RESET123TOKEN',
    ]);
    $customerVars = [
        'store' => $store,
        'customer' => $customer,
    ];

    /** @var Subscriber $subscriber */
    $subscriber = $objectManager->create(Subscriber::class);
    $subscriber->setSubscriberId(321);
    $subscriber->setEmail('cliente+newsletter@awamotos.com.br');
    $subscriber->setConfirmationCode('CONFIRMA123');

    $contactData = new \Magento\Framework\DataObject([
        'name' => 'João Cliente',
        'email' => 'cliente+contato@awamotos.com.br',
        'subject' => 'Dúvidas sobre disponibilidade',
        'telephone' => '(11) 98888-1234',
        'comment' => "Olá! Gostaria de saber se o modelo X está disponível para retirada.",
    ]);

    return [
        'grupoawamotos_sales_order_new_ptbr' => $orderVars,
        'grupoawamotos_sales_invoice_new_ptbr' => $invoiceVars,
        'grupoawamotos_sales_shipment_new_ptbr' => $shipmentVars,
        'grupoawamotos_sales_creditmemo_new_ptbr' => $creditmemoVars,
        'grupoawamotos_customer_account_new_ptbr' => array_merge($customerVars, ['store' => $store]),
        'grupoawamotos_customer_password_reset_ptbr' => array_merge($customerVars, ['store' => $store]),
        'grupoawamotos_newsletter_confirm_ptbr' => ['store' => $store, 'subscriber' => $subscriber],
        'grupoawamotos_newsletter_success_ptbr' => ['store' => $store, 'subscriber' => $subscriber],
        'grupoawamotos_newsletter_unsubscribe_ptbr' => ['store' => $store, 'subscriber' => $subscriber],
        'grupoawamotos_contact_form_ptbr' => ['store' => $store, 'data' => $contactData],
    ];
}
