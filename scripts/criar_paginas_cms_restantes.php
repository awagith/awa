<?php
/**
 * Script para criar páginas CMS restantes (Contact, FAQ)
 * Conforme auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Execução:
 * php scripts/criar_paginas_cms_restantes.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Page Factory e Repository
$pageFactory = $objectManager->get(\Magento\Cms\Model\PageFactory::class);
$pageRepository = $objectManager->get(\Magento\Cms\Api\PageRepositoryInterface::class);

echo "=== CRIAÇÃO DE PÁGINAS CMS RESTANTES ===\n\n";

// Páginas a criar
$pages = [
    [
        'identifier' => 'contact-us',
        'title' => 'Entre em Contato',
        'page_layout' => '1column',
        'content' => <<<HTML
<div class="contact-page">
    <div class="page-title-wrapper">
        <h1 class="page-title">Entre em Contato</h1>
    </div>
    
    <div class="contact-intro">
        <p>Estamos aqui para ajudar! Entre em contato conosco através de qualquer um dos canais abaixo.</p>
    </div>
    
    <div class="contact-info-grid">
        <div class="contact-info-item">
            <div class="icon">📞</div>
            <h3>Telefone</h3>
            <p><strong>Vendas:</strong> (11) 1234-5678</p>
            <p><strong>WhatsApp:</strong> (11) 98765-4321</p>
            <p>Segunda a Sexta: 9h às 18h<br>Sábado: 9h às 13h</p>
        </div>
        
        <div class="contact-info-item">
            <div class="icon">📧</div>
            <h3>E-mail</h3>
            <p><strong>Vendas:</strong> vendas@grupoawamotos.com.br</p>
            <p><strong>SAC:</strong> sac@grupoawamotos.com.br</p>
            <p><strong>Suporte:</strong> suporte@grupoawamotos.com.br</p>
        </div>
        
        <div class="contact-info-item">
            <div class="icon">📍</div>
            <h3>Endereço</h3>
            <p>Rua Exemplo, 123 - Centro<br>
            São Paulo - SP<br>
            CEP: 01234-567</p>
        </div>
        
        <div class="contact-info-item">
            <div class="icon">🕐</div>
            <h3>Horário de Atendimento</h3>
            <p><strong>Segunda a Sexta:</strong> 9h às 18h</p>
            <p><strong>Sábado:</strong> 9h às 13h</p>
            <p><strong>Domingo e Feriados:</strong> Fechado</p>
        </div>
    </div>
    
    <div class="contact-form-section">
        <h2>Formulário de Contato</h2>
        <p>Preencha o formulário abaixo e entraremos em contato em até 24 horas úteis.</p>
        
        {{block class="Magento\\Contact\\Block\\ContactForm" name="contactForm" template="Magento_Contact::form.phtml"}}
    </div>
    
    <style>
        .contact-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .contact-info-item {
            background: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
        }
        
        .contact-info-item .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .contact-info-item h3 {
            color: #b73337;
            margin-bottom: 15px;
        }
        
        .contact-form-section {
            background: #f9f9f9;
            padding: 40px;
            margin-top: 40px;
            border-radius: 8px;
        }
    </style>
</div>
HTML,
        'meta_title' => 'Entre em Contato - Grupo Awamotos',
        'meta_keywords' => 'contato, atendimento, suporte, telefone, email',
        'meta_description' => 'Entre em contato com a Grupo Awamotos através de nossos canais de atendimento. Estamos prontos para ajudar!',
    ],
    
    [
        'identifier' => 'faq',
        'title' => 'Perguntas Frequentes (FAQ)',
        'page_layout' => '1column',
        'content' => <<<HTML
<div class="faq-page">
    <div class="page-title-wrapper">
        <h1 class="page-title">Perguntas Frequentes (FAQ)</h1>
    </div>
    
    <div class="faq-intro">
        <p>Encontre respostas rápidas para as perguntas mais comuns sobre nossos produtos e serviços.</p>
    </div>
    
    <div class="faq-section">
        <h2>📦 Envio e Entrega</h2>
        
        <div class="faq-item">
            <h3>Qual o prazo de entrega?</h3>
            <p>O prazo de entrega varia conforme a região e o tipo de frete escolhido. Após a aprovação do pagamento:</p>
            <ul>
                <li><strong>Região Sudeste:</strong> 3 a 7 dias úteis</li>
                <li><strong>Região Sul/Centro-Oeste:</strong> 5 a 10 dias úteis</li>
                <li><strong>Região Norte/Nordeste:</strong> 7 a 15 dias úteis</li>
            </ul>
        </div>
        
        <div class="faq-item">
            <h3>Vocês entregam em todo o Brasil?</h3>
            <p>Sim! Realizamos entregas em todo o território nacional através dos Correios e transportadoras parceiras.</p>
        </div>
        
        <div class="faq-item">
            <h3>Qual o valor do frete?</h3>
            <p>O valor do frete é calculado automaticamente no carrinho de compras, baseado no CEP de entrega e peso dos produtos.</p>
        </div>
        
        <div class="faq-item">
            <h3>Posso retirar o produto na loja?</h3>
            <p>Sim! Oferecemos a opção de retirada gratuita em nossa loja física. Basta selecionar "Retirar na Loja" no checkout.</p>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>💳 Pagamento</h2>
        
        <div class="faq-item">
            <h3>Quais formas de pagamento vocês aceitam?</h3>
            <ul>
                <li><strong>PIX:</strong> Aprovação instantânea</li>
                <li><strong>Boleto Bancário:</strong> Aprovação em 1-2 dias úteis</li>
                <li><strong>Cartão de Crédito:</strong> Visa, Mastercard, Elo, Amex, Hipercard</li>
                <li><strong>Parcelamento:</strong> Em até 12x sem juros (consulte valor mínimo)</li>
            </ul>
        </div>
        
        <div class="faq-item">
            <h3>O pagamento é seguro?</h3>
            <p>Sim! Todas as transações são processadas em ambiente seguro com certificação SSL. Não armazenamos dados de cartão de crédito.</p>
        </div>
        
        <div class="faq-item">
            <h3>Posso parcelar compras no boleto?</h3>
            <p>Não. O parcelamento está disponível apenas para pagamentos com cartão de crédito.</p>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>🔄 Trocas e Devoluções</h2>
        
        <div class="faq-item">
            <h3>Qual o prazo para troca ou devolução?</h3>
            <p>Você tem até 7 dias corridos após o recebimento do produto para solicitar troca ou devolução, conforme o Código de Defesa do Consumidor.</p>
        </div>
        
        <div class="faq-item">
            <h3>Como solicitar uma troca?</h3>
            <p>Entre em contato com nosso SAC através do e-mail sac@grupoawamotos.com.br ou telefone (11) 1234-5678 informando o número do pedido e motivo da troca.</p>
        </div>
        
        <div class="faq-item">
            <h3>Quem paga o frete da troca?</h3>
            <ul>
                <li><strong>Produto com defeito:</strong> Frete por nossa conta</li>
                <li><strong>Arrependimento (direito de arrependimento):</strong> Frete por conta do cliente</li>
            </ul>
        </div>
        
        <div class="faq-item">
            <h3>Em quanto tempo recebo o reembolso?</h3>
            <p>Após recebermos e analisarmos o produto devolvido, o reembolso é processado em até 10 dias úteis.</p>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>🛍️ Produtos</h2>
        
        <div class="faq-item">
            <h3>Os produtos têm garantia?</h3>
            <p>Sim! Todos os produtos possuem garantia do fabricante. O prazo varia conforme o produto e está especificado na descrição.</p>
        </div>
        
        <div class="faq-item">
            <h3>Os produtos são originais?</h3>
            <p>Sim! Trabalhamos apenas com produtos originais de fabricantes homologados e fornecedores autorizados.</p>
        </div>
        
        <div class="faq-item">
            <h3>Posso comprar em atacado?</h3>
            <p>Sim! Temos preços especiais para compras em grande quantidade. Entre em contato através do e-mail vendas@grupoawamotos.com.br para solicitar uma cotação.</p>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>👤 Conta e Cadastro</h2>
        
        <div class="faq-item">
            <h3>Preciso criar uma conta para comprar?</h3>
            <p>Não é obrigatório, mas recomendamos criar uma conta para acompanhar seus pedidos, salvar endereços e ter um checkout mais rápido.</p>
        </div>
        
        <div class="faq-item">
            <h3>Esqueci minha senha, o que fazer?</h3>
            <p>Na página de login, clique em "Esqueceu sua senha?" e siga as instruções para redefinir.</p>
        </div>
        
        <div class="faq-item">
            <h3>Como alterar meus dados cadastrais?</h3>
            <p>Acesse sua conta em "Minha Conta" > "Informações da Conta" e edite os dados desejados.</p>
        </div>
    </div>
    
    <div class="faq-section">
        <h2>📋 Pedidos</h2>
        
        <div class="faq-item">
            <h3>Como acompanho meu pedido?</h3>
            <p>Acesse "Minha Conta" > "Meus Pedidos" ou use o código de rastreio enviado por e-mail.</p>
        </div>
        
        <div class="faq-item">
            <h3>Posso cancelar meu pedido?</h3>
            <p>Sim, desde que o pagamento ainda não tenha sido aprovado ou o produto não tenha sido enviado. Entre em contato imediatamente com nosso SAC.</p>
        </div>
        
        <div class="faq-item">
            <h3>Não recebi o código de rastreio, o que fazer?</h3>
            <p>O código de rastreio é enviado em até 2 dias úteis após a aprovação do pagamento. Verifique sua caixa de spam ou entre em contato conosco.</p>
        </div>
    </div>
    
    <div class="faq-footer">
        <h3>Não encontrou a resposta que procurava?</h3>
        <p>Nossa equipe de atendimento está pronta para ajudar!</p>
        <a href="/contact-us" class="btn btn-primary">Entre em Contato</a>
    </div>
    
    <style>
        .faq-section {
            margin: 40px 0;
            padding: 30px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .faq-section h2 {
            color: #b73337;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #b73337;
        }
        
        .faq-item {
            margin-bottom: 25px;
            padding: 20px;
            background: #ffffff;
            border-radius: 6px;
        }
        
        .faq-item h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }
        
        .faq-item ul {
            margin-left: 20px;
        }
        
        .faq-footer {
            text-align: center;
            margin-top: 50px;
            padding: 40px;
            background: #b73337;
            color: #ffffff;
            border-radius: 8px;
        }
        
        .faq-footer h3 {
            color: #ffffff;
        }
        
        .faq-footer .btn {
            margin-top: 20px;
        }
    </style>
</div>
HTML,
        'meta_title' => 'Perguntas Frequentes - FAQ - Grupo Awamotos',
        'meta_keywords' => 'faq, perguntas frequentes, dúvidas, ajuda, suporte',
        'meta_description' => 'Encontre respostas para as perguntas mais frequentes sobre produtos, envio, pagamento, trocas e devoluções.',
    ],
];

$createdCount = 0;

foreach ($pages as $pageData) {
    echo "📄 Criando página: {$pageData['title']}...\n";
    
    try {
        // Verificar se já existe
        try {
            $existingPage = $pageRepository->getById($pageData['identifier']);
            echo "   ⚠️  Página '{$pageData['identifier']}' já existe (ID: {$existingPage->getId()})\n";
            continue;
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // Página não existe, vamos criar
        }
        
        $page = $pageFactory->create();
        $page->setData([
            'title' => $pageData['title'],
            'identifier' => $pageData['identifier'],
            'page_layout' => $pageData['page_layout'],
            'content' => $pageData['content'],
            'meta_title' => $pageData['meta_title'],
            'meta_keywords' => $pageData['meta_keywords'],
            'meta_description' => $pageData['meta_description'],
            'is_active' => 1,
            'stores' => [0], // All Store Views
            'sort_order' => 0,
        ]);
        
        $pageRepository->save($page);
        echo "   ✅ Página criada! ID: " . $page->getId() . "\n";
        echo "   📊 Tamanho do conteúdo: " . strlen($pageData['content']) . " bytes\n";
        $createdCount++;
        
    } catch (\Exception $e) {
        echo "   ❌ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "✅ Páginas criadas: $createdCount/2\n";
echo "✅ Contact Us: /contact-us\n";
echo "✅ FAQ: /faq\n\n";

echo "📌 PÁGINAS ANTERIORMENTE CRIADAS:\n";
echo "✅ About Us: /about-us\n";
echo "✅ Terms and Conditions: /terms\n";
echo "✅ Privacy Policy: /privacy-policy\n";
echo "✅ Shipping Policy: /shipping-policy\n\n";

echo "✅ Script concluído!\n";
