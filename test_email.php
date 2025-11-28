<?php
use Magento\Email\Model\TransportFactory;
use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MimeInterface;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(Magento\Framework\App\State::class);
try {
    $state->setAreaCode('frontend');
} catch (Magento\Framework\Exception\LocalizedException $e) {
    // Area already set; ignore.
}

/** @var StoreManagerInterface $storeManager */
$storeManager = $objectManager->get(StoreManagerInterface::class);
$store = $storeManager->getDefaultStoreView();
$storeId = (int)$store->getId();

/** @var ScopeConfigInterface $scopeConfig */
$scopeConfig = $objectManager->get(ScopeConfigInterface::class);

$defaultRecipient = getenv('MAGENTO_TEST_EMAIL') ?: 'grupoawamotos@gmail.com';
$recipient = $argv[1] ?? $defaultRecipient;

$senderName = $scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE, $storeId);
$senderEmail = $scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, $storeId);

if (!$senderName) {
    $senderName = $store->getFrontendName() ?: 'Loja Magento';
}

if (!$senderEmail) {
    $host = parse_url($store->getBaseUrl(), PHP_URL_HOST) ?: 'localhost';
    $senderEmail = 'no-reply@' . $host;
}

$subject = sprintf('Teste de Email Magento (%s)', date('d/m/Y H:i'));
$bodyLines = [
    'Olá!',
    '',
    'Este é um teste automático usando o transporte oficial do Magento (Symfony Mailer).',
    'Se você recebeu esta mensagem, o SMTP configurado para a loja está aceitando conexões e entregando e-mails.',
    '',
    'Resumo do ambiente:',
    sprintf(' • Loja: %s (ID %d)', $store->getName(), $storeId),
    sprintf(' • URL: %s', $store->getBaseUrl()),
    sprintf(' • Host: %s', gethostname()),
    sprintf(' • Transport: %s:%s', $scopeConfig->getValue('system/smtp/host'), $scopeConfig->getValue('system/smtp/port')),
    '',
    'Mensagens adicionais podem ser passadas como argumento:',
    '  php test_email.php destinatario@dominio.com',
    '',
    'Abraços,',
    $senderName,
];

$body = implode("\n", $bodyLines);

$boxLine = str_repeat('═', 58);

echo "\n╔{$boxLine}╗\n";
echo "║           TESTE DE ENVIO DE EMAIL - MAGENTO 2             ║\n";
echo "╚{$boxLine}╝\n\n";

echo "📧 Destinatário: {$recipient}\n";
echo '🏪 Loja: ' . $store->getName() . "\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

/** @var EmailMessageInterfaceFactory $emailMessageFactory */
$emailMessageFactory = $objectManager->get(EmailMessageInterfaceFactory::class);
/** @var MimePartInterfaceFactory $mimePartFactory */
$mimePartFactory = $objectManager->get(MimePartInterfaceFactory::class);
/** @var MimeMessageInterfaceFactory $mimeMessageFactory */
$mimeMessageFactory = $objectManager->get(MimeMessageInterfaceFactory::class);
/** @var TransportFactory $transportFactory */
$transportFactory = $objectManager->get(TransportFactory::class);

$mimePart = $mimePartFactory->create([
    'content' => $body,
    'type' => MimeInterface::TYPE_TEXT,
    'charset' => 'utf-8',
]);

$mimeMessage = $mimeMessageFactory->create([
    'parts' => [$mimePart],
]);

$message = $emailMessageFactory->create([
    'subject' => $subject,
    'body' => $mimeMessage,
    'from' => [['email' => $senderEmail, 'name' => $senderName]],
    'to' => [['email' => $recipient, 'name' => $recipient]],
    'replyTo' => [['email' => $senderEmail, 'name' => $senderName]],
]);

$transport = $transportFactory->create(['message' => $message]);

try {
    $transport->sendMessage();
    $success = true;
    echo "✅ EMAIL ENVIADO COM SUCESSO!\n";
} catch (Throwable $exception) {
    $success = false;
    echo "❌ FALHA AO ENVIAR O EMAIL.\n";
    echo "Motivo: " . $exception->getMessage() . "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

if ($success) {
    echo "📋 PRÓXIMOS PASSOS:\n\n";
    echo "  1. Verifique a caixa de entrada: {$recipient}\n";
    echo "  2. Verifique também SPAM/Lixo Eletrônico\n";
    echo "  3. Se não chegou, verifique:\n\n";
    echo "     • Configurações SMTP em:\n       Stores > Configuration > Advanced > System\n\n";
    echo "     • Logs do Magento:\n       tail -f var/log/system.log | grep -i mail\n\n";
    echo "     • Fila de emails assíncronos:\n       php bin/magento queue:consumers:start async.operations.all\n\n";
} else {
    echo "ℹ️ Veja logs em var/log/system.log e /var/log/mail.log para detalhes.\n\n";
}
