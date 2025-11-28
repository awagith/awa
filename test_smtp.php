<?php
/**
 * Script de teste de envio de e-mail via SMTP
 * Valida se o fix do Reply-To (MailboxListHeader) está funcionando
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    $state = $objectManager->get(\Magento\Framework\App\State::class);
    $state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);
} catch (\Exception $e) {
    // Área já definida
}

echo "=== Teste de Envio de E-mail SMTP ===\n\n";

// Verificar configurações
$scopeConfig = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);

$smtpActive = $scopeConfig->getValue('system/gmailsmtpapp/active');
$smtpHost = $scopeConfig->getValue('system/gmailsmtpapp/smtphost');
$smtpPort = $scopeConfig->getValue('system/gmailsmtpapp/smtpport');
$smtpUser = $scopeConfig->getValue('system/gmailsmtpapp/username');

echo "Configurações SMTP:\n";
echo "- Ativo: " . ($smtpActive ? 'Sim' : 'Não') . "\n";
echo "- Host: {$smtpHost}\n";
echo "- Porta: {$smtpPort}\n";
echo "- Usuário: {$smtpUser}\n\n";

if (!$smtpActive) {
    echo "❌ SMTP não está ativo. Ative em Stores > Configuration > MagePal > Gmail SMTP App\n";
    exit(1);
}

// Tentar enviar e-mail de teste
try {
    /** @var \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder */
    $transportBuilder = $objectManager->get(\Magento\Framework\Mail\Template\TransportBuilder::class);
    
    // Usar template simples de contato ou genérico
    $transport = $transportBuilder
        ->setTemplateIdentifier('contact_email_email_template')
        ->setTemplateOptions([
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => 1
        ])
        ->setTemplateVars([
            'data' => new \Magento\Framework\DataObject([
                'name' => 'Teste SMTP',
                'email' => $smtpUser,
                'telephone' => '(11) 99999-9999',
                'comment' => 'Este é um e-mail de teste para validar o funcionamento do SMTP após a correção do Reply-To (MailboxListHeader).'
            ])
        ])
        ->setFromByScope([
            'email' => $smtpUser,
            'name' => 'Teste SMTP GrupoAwamotos'
        ])
        ->addTo($smtpUser, 'Teste Destinatário')
        ->getTransport();
    
    echo "Enviando e-mail de teste para: {$smtpUser}...\n";
    
    $transport->sendMessage();
    
    echo "\n✅ E-mail enviado com sucesso!\n";
    echo "O fix do Reply-To (MailboxListHeader) está funcionando corretamente.\n";
    echo "Verifique sua caixa de entrada em: {$smtpUser}\n";
    
} catch (\Exception $e) {
    echo "\n❌ Erro ao enviar e-mail:\n";
    echo $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'reply-to') !== false || strpos($e->getMessage(), 'MailboxListHeader') !== false) {
        echo "⚠️ O erro ainda está relacionado ao Reply-To.\n";
        echo "Verifique se o módulo GrupoAwamotos_SmtpFix está habilitado e recompile:\n";
        echo "php bin/magento module:status | grep SmtpFix\n";
        echo "php bin/magento setup:di:compile\n";
    } else {
        echo "Stack trace:\n";
        echo $e->getTraceAsString() . "\n";
    }
    
    exit(1);
}

echo "\n=== Teste concluído ===\n";
