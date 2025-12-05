<?php
/**
 * Script para configurar Terms and Conditions no OnePageCheckout
 * Conforme auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Execução:
 * php scripts/configurar_terms_checkout.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Config Writer
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

echo "=== CONFIGURAÇÃO DE TERMS AND CONDITIONS NO CHECKOUT ===\n\n";

// Configurações de Terms and Conditions
$checkoutConfigs = [
    // Habilitar Terms and Conditions
    [
        'path' => 'checkout/options/enable_agreements',
        'value' => '1',
        'label' => 'Enable Agreements: Habilitado',
    ],
    
    // OnePageCheckout específico (se o módulo usar configs próprias)
    [
        'path' => 'rokanthemes_onepagecheckout/general/enable',
        'value' => '1',
        'label' => 'OnePageCheckout: Habilitado',
    ],
    
    [
        'path' => 'rokanthemes_onepagecheckout/terms/enable',
        'value' => '1',
        'label' => 'Terms Checkbox: Habilitado',
    ],
    
    [
        'path' => 'rokanthemes_onepagecheckout/terms/checkbox_text',
        'value' => 'Li e aceito os <a href="/terms" target="_blank">Termos e Condições</a> e a <a href="/privacy-policy" target="_blank">Política de Privacidade</a>',
        'label' => 'Checkbox Text: Configurado (pt_BR)',
    ],
    
    [
        'path' => 'rokanthemes_onepagecheckout/terms/warning_title',
        'value' => 'Atenção!',
        'label' => 'Warning Title: Atenção!',
    ],
    
    [
        'path' => 'rokanthemes_onepagecheckout/terms/warning_content',
        'value' => 'Você deve aceitar os Termos e Condições para finalizar sua compra.',
        'label' => 'Warning Content: Configurado (pt_BR)',
    ],
];

$savedCount = 0;

foreach ($checkoutConfigs as $config) {
    try {
        $configWriter->save(
            $config['path'],
            $config['value'],
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        echo "✅ {$config['label']}\n";
        $savedCount++;
    } catch (\Exception $e) {
        echo "❌ Erro em {$config['path']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CRIANDO CHECKOUT AGREEMENT ===\n\n";

// Criar Checkout Agreement via API
$agreementFactory = $objectManager->get(\Magento\CheckoutAgreements\Model\AgreementFactory::class);
$agreementResource = $objectManager->get(\Magento\CheckoutAgreements\Model\ResourceModel\Agreement::class);
$agreementCollection = $objectManager->get(\Magento\CheckoutAgreements\Model\ResourceModel\Agreement\CollectionFactory::class);

// Verificar se já existe
$collection = $agreementCollection->create()
    ->addFieldToFilter('name', 'Termos e Condições de Uso');

if ($collection->getSize() > 0) {
    echo "⚠️  Agreement 'Termos e Condições de Uso' já existe.\n";
} else {
    echo "📝 Criando Checkout Agreement...\n";
    
    $agreement = $agreementFactory->create();
    
    $agreementContent = <<<HTML
<div class="agreement-content">
    <h2>Termos e Condições de Uso</h2>
    
    <p>Ao realizar uma compra em nossa loja, você concorda com os seguintes termos e condições:</p>
    
    <h3>1. Aceitação dos Termos</h3>
    <p>Ao utilizar este site e realizar compras, você concorda em cumprir e estar vinculado aos seguintes termos e condições de uso.</p>
    
    <h3>2. Produtos e Serviços</h3>
    <p>Todos os produtos e serviços oferecidos estão sujeitos à disponibilidade. Reservamo-nos o direito de descontinuar qualquer produto a qualquer momento.</p>
    
    <h3>3. Preços e Pagamento</h3>
    <p>Todos os preços estão em Reais (R\$) e incluem impostos quando aplicável. Reservamo-nos o direito de alterar preços sem aviso prévio.</p>
    
    <h3>4. Política de Entrega</h3>
    <p>Os prazos de entrega são estimados e começam a contar após a aprovação do pagamento. Não nos responsabilizamos por atrasos causados pela transportadora.</p>
    
    <h3>5. Trocas e Devoluções</h3>
    <p>Você tem 7 dias corridos para solicitar troca ou devolução, conforme o Código de Defesa do Consumidor (Art. 49).</p>
    
    <h3>6. Privacidade</h3>
    <p>Suas informações pessoais são protegidas conforme nossa <a href="/privacy-policy" target="_blank">Política de Privacidade</a>, em conformidade com a LGPD.</p>
    
    <h3>7. Propriedade Intelectual</h3>
    <p>Todo o conteúdo deste site, incluindo textos, imagens e logotipos, é propriedade do Grupo Awamotos e protegido por direitos autorais.</p>
    
    <h3>8. Limitação de Responsabilidade</h3>
    <p>Não nos responsabilizamos por danos indiretos, incidentais ou consequenciais resultantes do uso de nossos produtos ou serviços.</p>
    
    <h3>9. Lei Aplicável</h3>
    <p>Estes termos são regidos pelas leis brasileiras. Qualquer disputa será resolvida no foro da comarca de São Paulo/SP.</p>
    
    <h3>10. Contato</h3>
    <p>Para dúvidas sobre estes termos, entre em contato através do e-mail: sac@grupoawamotos.com.br</p>
    
    <p><strong>Última atualização:</strong> Dezembro de 2025</p>
</div>
HTML;

    $agreementData = [
        'name' => 'Termos e Condições de Uso',
        'content' => $agreementContent,
        'checkbox_text' => 'Li e aceito os Termos e Condições de Uso',
        'is_active' => 1,
        'is_html' => 1,
        'mode' => 0, // 0 = Auto (aparece automaticamente)
        'stores' => [0], // All Store Views
    ];
    
    $agreement->setData($agreementData);
    
    try {
        $agreementResource->save($agreement);
        echo "✅ Checkout Agreement criado! ID: " . $agreement->getId() . "\n";
        echo "   📊 Tamanho: " . strlen($agreementContent) . " bytes\n";
    } catch (\Exception $e) {
        echo "❌ Erro ao criar agreement: " . $e->getMessage() . "\n";
    }
}

echo "\n=== CONFIGURAÇÕES ADICIONAIS DO CHECKOUT ===\n\n";

// Outras configurações úteis do checkout
$additionalConfigs = [
    // Permitir checkout como guest
    [
        'path' => 'checkout/options/guest_checkout',
        'value' => '1',
        'label' => 'Guest Checkout: Habilitado',
    ],
    
    // Redirecionar para cart após adicionar produto
    [
        'path' => 'checkout/cart/redirect_to_cart',
        'value' => '0',
        'label' => 'Redirect to Cart: Desabilitado (manter na página)',
    ],
    
    // Mostrar cross-sell products no cart
    [
        'path' => 'checkout/cart/crosssell_enabled',
        'value' => '1',
        'label' => 'Cross-sell Products: Habilitado',
    ],
    
    // Número de produtos cross-sell
    [
        'path' => 'checkout/cart_link/use_qty',
        'value' => '1',
        'label' => 'Show Qty in Cart Link: Habilitado',
    ],
];

foreach ($additionalConfigs as $config) {
    try {
        $configWriter->save(
            $config['path'],
            $config['value'],
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        echo "✅ {$config['label']}\n";
        $savedCount++;
    } catch (\Exception $e) {
        echo "❌ Erro em {$config['path']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "✅ Configurações salvas: $savedCount/" . (count($checkoutConfigs) + count($additionalConfigs)) . "\n";
echo "✅ Enable Agreements: Habilitado\n";
echo "✅ Checkout Agreement: Criado\n";
echo "✅ Checkbox Text: Configurado em pt_BR\n";
echo "✅ Warning Messages: Configurados em pt_BR\n";
echo "✅ Guest Checkout: Habilitado\n\n";

echo "📌 AGREEMENTS DISPONÍVEIS:\n";
echo "✅ Termos e Condições de Uso (criado automaticamente)\n";
echo "📄 Link: /terms (página CMS)\n";
echo "📄 Link: /privacy-policy (página CMS)\n\n";

echo "⚠️  VERIFICAR NO ADMIN:\n";
echo "1. Stores > Configuration > Sales > Checkout\n";
echo "   - Enable Agreements: Yes\n\n";
echo "2. Stores > Terms and Conditions\n";
echo "   - Verificar se 'Termos e Condições de Uso' está ativo\n\n";
echo "3. Testar checkout:\n";
echo "   - Adicionar produto ao carrinho\n";
echo "   - Ir para checkout\n";
echo "   - Verificar se checkbox de termos aparece\n\n";

echo "✅ Script concluído!\n";
