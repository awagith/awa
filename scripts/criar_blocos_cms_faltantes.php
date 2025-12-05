<?php
/**
 * Script para Criar Blocos CMS Faltantes do Tema Ayo
 * Baseado na auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Blocos a criar:
 * 1. top-left-static
 * 2. hotline_header
 * 3. footer_static
 * 4. footer_payment
 * 5. fixed_right
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🔧 Criando Blocos CMS Faltantes do Tema Ayo\n";
echo "==========================================\n\n";

$blockFactory = $objectManager->get('Magento\Cms\Model\BlockFactory');
$blockRepository = $objectManager->get('Magento\Cms\Api\BlockRepositoryInterface');

$blocksToCreate = [
    [
        'identifier' => 'top-left-static',
        'title' => 'Top Header - Mensagem Promocional',
        'content' => '<div class="top-left-message">
            <span class="promo-text">🚚 Frete Grátis para compras acima de R$ 299,00</span>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'hotline_header',
        'title' => 'Header - Hotline/Contato',
        'content' => '<div class="header-hotline">
            <div class="hotline-wrapper d-flex align-items-center">
                <div class="hotline-icon">
                    <i class="fa fa-phone"></i>
                </div>
                <div class="hotline-info">
                    <span class="hotline-label">Central de Atendimento</span>
                    <a href="tel:+5511999999999" class="hotline-number">(11) 99999-9999</a>
                </div>
            </div>
            <div class="whatsapp-link">
                <a href="https://wa.me/5511999999999" target="_blank" class="btn-whatsapp">
                    <i class="fa fa-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'footer_static',
        'title' => 'Footer - Conteúdo Principal',
        'content' => '<div class="velaNewsletterFooter">
    <div class="velaNewsletterInner clearfix">
        <h4 class="velaFooterTitle">Cadastre-se na Newsletter</h4>
        <div class="velaContent">
            <div class="newsletterDescription">
                Inscreva-se para receber informações sobre nossos lançamentos e ganhe acesso exclusivo às compras antecipadas. 
                <span class="text-subcrib">Junte-se a mais de 10.000 assinantes</span> e receba um novo cupom de desconto todo sábado.
            </div>
            {{block class="Magento\\Newsletter\\Block\\Subscribe" template="subscribe.phtml"}}
        </div>
    </div>
</div>
<div class="container">
    <div class="rowFlex rowFlexMargin">
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="vela-contactinfo velaBlock">
                <div class="vela-content">
                    <div class="contacinfo-logo clearfix">
                        <div class="velaFooterLogo">
                            <a href="{{store url=\'\'}}">
                                <img src="{{media url=\'logo.png\'}}" alt="Grupo Awamotos">
                            </a>
                        </div>
                    </div>
                    <div class="intro-footer d-flex">
                        Especialistas em peças e acessórios automotivos com os melhores preços e atendimento personalizado para você.
                    </div>
                    <div class="contacinfo-phone contactinfo-item clearfix">
                        <div class="d-flex">
                            <div class="image_hotline">
                                <i class="fa fa-phone"></i>
                            </div>
                            <div class="wrap">
                                <label>Central 24/7:</label>
                                <a href="tel:+5511999999999">(11) 99999-9999</a>
                            </div>
                        </div>
                    </div>
                    <div class="contacinfo-address contactinfo-item d-flex">
                        <label>Endereço:</label>
                        <span>São Paulo, SP - Brasil</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="rowFlex rowFlexMargin">
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Informações</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a title="Sobre Nós" href="{{store url=\'about-us\'}}">Sobre Nós</a></li>
                                <li><a title="Perguntas Frequentes" href="{{store url=\'faq\'}}">Perguntas Frequentes</a></li>
                                <li><a title="Rastreamento" href="{{store url=\'sales/order/history\'}}">Rastreamento de Pedidos</a></li>
                                <li><a title="Contato" href="{{store url=\'contact\'}}">Contato</a></li>
                                <li><a title="Blog" href="{{store url=\'blog\'}}">Blog</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-xs-12 col-sm-6">
                    <div class="velaFooterMenu velaBlock">
                        <h4 class="velaFooterTitle">Minha Conta</h4>
                        <div class="velaContent">
                            <ul class="velaFooterLinks list-unstyled">
                                <li><a title="Minha Conta" href="{{store url=\'customer/account\'}}">Minha Conta</a></li>
                                <li><a title="Meus Pedidos" href="{{store url=\'sales/order/history\'}}">Meus Pedidos</a></li>
                                <li><a title="Lista de Desejos" href="{{store url=\'wishlist\'}}">Lista de Desejos</a></li>
                                <li><a title="Trocar e Devoluções" href="{{store url=\'returns\'}}">Trocas e Devoluções</a></li>
                                <li><a title="Política de Privacidade" href="{{store url=\'privacy-policy\'}}">Política de Privacidade</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-12 col-md-4">
            <div class="velaFooterMenu velaBlock">
                <h4 class="velaFooterTitle">Siga-nos</h4>
                <div class="velaContent">
                    <ul class="velaFooterLinks social-links list-unstyled">
                        <li><a style="color: #3b5998;" title="Facebook" href="#" target="_blank"><i class="fa fa-facebook"></i> Facebook</a></li>
                        <li><a style="color: #1da1f2;" title="Twitter" href="#" target="_blank"><i class="fa fa-twitter"></i> Twitter</a></li>
                        <li><a style="color: #e4405f;" title="Instagram" href="#" target="_blank"><i class="fa fa-instagram"></i> Instagram</a></li>
                        <li><a style="color: #ff0000;" title="YouTube" href="#" target="_blank"><i class="fa fa-youtube"></i> YouTube</a></li>
                        <li><a style="color: #25d366;" title="WhatsApp" href="https://wa.me/5511999999999" target="_blank"><i class="fa fa-whatsapp"></i> WhatsApp</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'footer_payment',
        'title' => 'Footer - Métodos de Pagamento',
        'content' => '<div class="payment-methods-footer">
    <h5 class="payment-title">Formas de Pagamento</h5>
    <div class="payment-method">
        <img src="{{media url=\'payment/pix.png\'}}" alt="PIX" title="PIX - Pagamento Instantâneo">
        <img src="{{media url=\'payment/boleto.png\'}}" alt="Boleto" title="Boleto Bancário">
        <img src="{{media url=\'payment/visa.png\'}}" alt="Visa" title="Visa">
        <img src="{{media url=\'payment/mastercard.png\'}}" alt="Mastercard" title="Mastercard">
        <img src="{{media url=\'payment/amex.png\'}}" alt="American Express" title="American Express">
        <img src="{{media url=\'payment/elo.png\'}}" alt="Elo" title="Elo">
        <img src="{{media url=\'payment/hipercard.png\'}}" alt="Hipercard" title="Hipercard">
    </div>
    <div class="security-seals">
        <img src="{{media url=\'security/ssl.png\'}}" alt="SSL Seguro" title="Site 100% Seguro">
        <img src="{{media url=\'security/google-safe.png\'}}" alt="Google Safe" title="Google Safe Browsing">
    </div>
</div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'fixed_right',
        'title' => 'Menu Fixo Lateral Direito',
        'content' => '<ul class="fixed-right-menu">
    <li class="fixed_account">
        <a href="{{store url=\'customer/account\'}}" title="Minha Conta">
            <i class="fa fa-user"></i>
            <span>Conta</span>
        </a>
    </li>
    <li class="fixed_wishlist">
        <a href="{{store url=\'wishlist\'}}" title="Lista de Desejos">
            <i class="fa fa-heart"></i>
            <span>Favoritos</span>
        </a>
    </li>
    <li class="fixed_compare">
        <a href="{{store url=\'catalog/product_compare\'}}" title="Comparar Produtos">
            <i class="fa fa-exchange"></i>
            <span>Comparar</span>
        </a>
    </li>
    <li class="fixed_contact">
        <a href="{{store url=\'contact\'}}" title="Contato">
            <i class="fa fa-envelope"></i>
            <span>Contato</span>
        </a>
    </li>
    <li class="fixed_whatsapp">
        <a href="https://wa.me/5511999999999" target="_blank" title="WhatsApp">
            <i class="fa fa-whatsapp"></i>
            <span>WhatsApp</span>
        </a>
    </li>
</ul>',
        'is_active' => 1,
        'stores' => [0]
    ]
];

$created = 0;
$errors = 0;

foreach ($blocksToCreate as $blockData) {
    try {
        // Verificar se bloco já existe
        try {
            $existingBlock = $blockRepository->getById($blockData['identifier']);
            echo "⚠️  Bloco '{$blockData['identifier']}' já existe (ID: {$existingBlock->getId()}). Atualizando...\n";
            
            $block = $existingBlock;
            $block->setTitle($blockData['title']);
            $block->setContent($blockData['content']);
            $block->setIsActive($blockData['is_active']);
            
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            echo "➕ Criando bloco '{$blockData['identifier']}'...\n";
            
            $block = $blockFactory->create();
            $block->setIdentifier($blockData['identifier']);
            $block->setTitle($blockData['title']);
            $block->setContent($blockData['content']);
            $block->setIsActive($blockData['is_active']);
            $block->setStores($blockData['stores']);
        }
        
        $blockRepository->save($block);
        
        echo "✅ Bloco '{$blockData['identifier']}' salvo com sucesso!\n";
        echo "   Título: {$blockData['title']}\n";
        echo "   Tamanho conteúdo: " . strlen($blockData['content']) . " bytes\n\n";
        
        $created++;
        
    } catch (\Exception $e) {
        echo "❌ Erro ao criar bloco '{$blockData['identifier']}': {$e->getMessage()}\n\n";
        $errors++;
    }
}

echo "\n==========================================\n";
echo "📊 Resumo da Execução:\n";
echo "   ✅ Blocos criados/atualizados: $created\n";
echo "   ❌ Erros: $errors\n";
echo "   📦 Total processado: " . count($blocksToCreate) . "\n";
echo "==========================================\n\n";

if ($created > 0) {
    echo "🎉 Blocos CMS criados com sucesso!\n";
    echo "📋 Próximos passos:\n";
    echo "   1. Verificar blocos em: Admin > Content > Blocks\n";
    echo "   2. Ajustar conteúdo conforme necessário\n";
    echo "   3. Upload de imagens: pub/media/payment/ e pub/media/security/\n";
    echo "   4. Limpar cache: php bin/magento cache:flush\n";
}

echo "\n✅ Script concluído!\n";
