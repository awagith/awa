<?php
$recipient = $argv[1] ?? getenv('MAGENTO_TEST_EMAIL') ?? 'grupoawamotos@gmail.com';
$sender = getenv('MAGENTO_SENDER_EMAIL') ?: 'j@jessestain.com.br';
$senderName = getenv('MAGENTO_SENDER_NAME') ?: 'Loja Brasil';

$subject = sprintf('Teste rápido PHP mail() - %s', date('d/m/Y H:i'));
$body = "Teste simples enviado via função mail() diretamente do servidor.\nSe você recebeu este email, o PHP mail() está operacional.";

$headers = [
    sprintf('From: %s <%s>', $senderName, $sender),
    sprintf('Reply-To: %s', $sender),
    'Content-Type: text/plain; charset=UTF-8',
];

$success = mail($recipient, $subject, $body, implode("\r\n", $headers), '-f' . $sender);

echo $success ? "Email enviado para {$recipient}\n" : "Falha ao enviar email para {$recipient}\n";
