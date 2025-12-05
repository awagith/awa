<?php
/**
 * Script para Atualizar Blocos Footer com HTML Correto
 * Conforme estrutura da documentação oficial do tema Ayo
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🎨 Atualizando Blocos Footer com HTML Correto (Padrão Ayo)\n";
echo "======================================================\n\n";

$blockRepository = $objectManager->get('Magento\Cms\Api\BlockRepositoryInterface');

$blocksToUpdate = [
    'footer_info' => [
        'title' => 'Footer Info - Informações da Loja',
        'content' => '<div class="vela-contactinfo velaBlock">
    <div class="vela-content">
        <div class="contacinfo-logo clearfix">
            <div class="velaFooterLogo">
                <a href="{{store url=\'\'}}">
                    <img src="{{media url=\'logo.png\'}}" alt="Grupo Awamotos" title="Grupo Awamotos">
                </a>
            </div>
        </div>
        <div class="intro-footer d-flex">
            Especialistas em peças e acessórios automotivos há mais de 10 anos. Oferecemos os melhores produtos com qualidade garantida e atendimento personalizado.
        </div>
        <div class="contacinfo-phone contactinfo-item clearfix">
            <div class="d-flex align-items-center">
                <div class="image_hotline">
                    <i class="fa fa-phone-square fa-2x" style="color: #b73337;"></i>
                </div>
                <div class="wrap" style="margin-left: 10px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 3px;">Central 24/7:</label>
                    <a href="tel:+5511999999999" style="font-size: 18px; color: #b73337; font-weight: bold;">(11) 99999-9999</a>
                </div>
            </div>
        </div>
        <div class="contacinfo-address contactinfo-item d-flex" style="margin-top: 15px;">
            <div class="d-flex align-items-start">
                <i class="fa fa-map-marker fa-lg" style="color: #b73337; margin-right: 10px; margin-top: 3px;"></i>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 3px;">Endereço:</label>
                    <span>Rua Exemplo, 123 - Centro<br>São Paulo, SP - CEP 01234-567</span>
                </div>
            </div>
        </div>
        <div class="contacinfo-email contactinfo-item d-flex" style="margin-top: 15px;">
            <div class="d-flex align-items-center">
                <i class="fa fa-envelope fa-lg" style="color: #b73337; margin-right: 10px;"></i>
                <div>
                    <label style="display: block; font-weight: 600; margin-bottom: 3px;">E-mail:</label>
                    <a href="mailto:contato@grupoawamotos.com.br" style="color: #333;">contato@grupoawamotos.com.br</a>
                </div>
            </div>
        </div>
    </div>
</div>'
    ],
    'social_block' => [
        'title' => 'Social Links - Redes Sociais',
        'content' => '<div class="velaBlock social-block-footer">
    <h4 class="velaFooterTitle">Siga-nos nas Redes Sociais</h4>
    <div class="velaContent">
        <ul class="velaFooterLinks social-links list-unstyled">
            <li class="social-item facebook">
                <a href="https://facebook.com/grupoawamotos" target="_blank" title="Facebook" style="color: #3b5998;">
                    <i class="fa fa-facebook-square fa-lg"></i>
                    <span>Facebook</span>
                </a>
            </li>
            <li class="social-item instagram">
                <a href="https://instagram.com/grupoawamotos" target="_blank" title="Instagram" style="color: #e4405f;">
                    <i class="fa fa-instagram fa-lg"></i>
                    <span>Instagram</span>
                </a>
            </li>
            <li class="social-item youtube">
                <a href="https://youtube.com/@grupoawamotos" target="_blank" title="YouTube" style="color: #ff0000;">
                    <i class="fa fa-youtube-play fa-lg"></i>
                    <span>YouTube</span>
                </a>
            </li>
            <li class="social-item whatsapp">
                <a href="https://wa.me/5511999999999?text=Olá,%20vim%20do%20site%20e%20gostaria%20de%20mais%20informações" target="_blank" title="WhatsApp" style="color: #25d366;">
                    <i class="fa fa-whatsapp fa-lg"></i>
                    <span>WhatsApp</span>
                </a>
            </li>
            <li class="social-item twitter">
                <a href="https://twitter.com/grupoawamotos" target="_blank" title="Twitter" style="color: #1da1f2;">
                    <i class="fa fa-twitter fa-lg"></i>
                    <span>Twitter</span>
                </a>
            </li>
            <li class="social-item linkedin">
                <a href="https://linkedin.com/company/grupoawamotos" target="_blank" title="LinkedIn" style="color: #0077b5;">
                    <i class="fa fa-linkedin-square fa-lg"></i>
                    <span>LinkedIn</span>
                </a>
            </li>
        </ul>
        <div class="social-description" style="margin-top: 20px; color: #666;">
            <p>Acompanhe nossas novidades, promoções exclusivas e dicas sobre peças automotivas. Estamos sempre compartilhando conteúdo de qualidade!</p>
        </div>
    </div>
</div>

<style>
.social-links li {
    display: inline-block;
    margin: 5px 10px 5px 0;
}
.social-links li a {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 4px;
    transition: all 0.3s ease;
    text-decoration: none;
}
.social-links li a:hover {
    opacity: 0.8;
    transform: translateY(-2px);
}
.social-links li a i {
    margin-right: 8px;
}
</style>'
    ],
    'footer_menu' => [
        'title' => 'Footer Menu - Links do Rodapé',
        'content' => '<div class="rowFlex rowFlexMargin footer-menu-wrapper">
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="velaFooterMenu velaBlock">
            <h4 class="velaFooterTitle">Informações</h4>
            <div class="velaContent">
                <ul class="velaFooterLinks list-unstyled">
                    <li><a title="Sobre Nós" href="{{store url=\'about-us\'}}">Sobre Nós</a></li>
                    <li><a title="Perguntas Frequentes" href="{{store url=\'faq\'}}">Perguntas Frequentes</a></li>
                    <li><a title="Política de Envio" href="{{store url=\'shipping-policy\'}}">Política de Envio</a></li>
                    <li><a title="Trocas e Devoluções" href="{{store url=\'returns\'}}">Trocas e Devoluções</a></li>
                    <li><a title="Política de Privacidade" href="{{store url=\'privacy-policy\'}}">Política de Privacidade</a></li>
                    <li><a title="Termos e Condições" href="{{store url=\'terms\'}}">Termos e Condições</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="velaFooterMenu velaBlock">
            <h4 class="velaFooterTitle">Atendimento</h4>
            <div class="velaContent">
                <ul class="velaFooterLinks list-unstyled">
                    <li><a title="Central de Ajuda" href="{{store url=\'help\'}}">Central de Ajuda</a></li>
                    <li><a title="Rastreamento de Pedidos" href="{{store url=\'sales/order/history\'}}">Rastrear Pedido</a></li>
                    <li><a title="Formas de Pagamento" href="{{store url=\'payment-methods\'}}">Formas de Pagamento</a></li>
                    <li><a title="Contato" href="{{store url=\'contact\'}}">Fale Conosco</a></li>
                    <li><a title="Trabalhe Conosco" href="{{store url=\'careers\'}}">Trabalhe Conosco</a></li>
                    <li><a title="Seja um Revendedor" href="{{store url=\'reseller\'}}">Seja um Revendedor</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12 col-sm-6 col-md-4">
        <div class="velaFooterMenu velaBlock">
            <h4 class="velaFooterTitle">Minha Conta</h4>
            <div class="velaContent">
                <ul class="velaFooterLinks list-unstyled">
                    <li><a title="Minha Conta" href="{{store url=\'customer/account\'}}">Minha Conta</a></li>
                    <li><a title="Meus Pedidos" href="{{store url=\'sales/order/history\'}}">Meus Pedidos</a></li>
                    <li><a title="Lista de Desejos" href="{{store url=\'wishlist\'}}">Lista de Desejos</a></li>
                    <li><a title="Comparar Produtos" href="{{store url=\'catalog/product_compare\'}}">Comparar Produtos</a></li>
                    <li><a title="Endereços" href="{{store url=\'customer/address\'}}">Meus Endereços</a></li>
                    <li><a title="Newsletter" href="{{store url=\'newsletter/manage\'}}">Newsletter</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>'
    ]
];

$updated = 0;
$errors = 0;

foreach ($blocksToUpdate as $identifier => $data) {
    try {
        echo "📝 Atualizando bloco '$identifier'...\n";
        
        $block = $blockRepository->getById($identifier);
        $block->setTitle($data['title']);
        $block->setContent($data['content']);
        $block->setIsActive(1);
        
        $blockRepository->save($block);
        
        echo "✅ Bloco '$identifier' atualizado com sucesso!\n";
        echo "   Novo título: {$data['title']}\n";
        echo "   Tamanho conteúdo: " . strlen($data['content']) . " bytes\n\n";
        
        $updated++;
        
    } catch (\Exception $e) {
        echo "❌ Erro ao atualizar bloco '$identifier': {$e->getMessage()}\n\n";
        $errors++;
    }
}

echo "\n======================================================\n";
echo "📊 Resumo da Execução:\n";
echo "   ✅ Blocos atualizados: $updated\n";
echo "   ❌ Erros: $errors\n";
echo "   📦 Total processado: " . count($blocksToUpdate) . "\n";
echo "======================================================\n\n";

if ($updated > 0) {
    echo "🎉 Blocos footer atualizados com HTML da documentação Ayo!\n";
    echo "📋 Melhorias aplicadas:\n";
    echo "   - Classes CSS corretas (velaBlock, d-flex, etc.)\n";
    echo "   - Estrutura HTML conforme documentação oficial\n";
    echo "   - Ícones Font Awesome integrados\n";
    echo "   - Links atualizados para pt_BR\n";
    echo "   - Cores da paleta #b73337 aplicadas\n\n";
    echo "🔄 Execute: php bin/magento cache:flush\n";
}

echo "\n✅ Script concluído!\n";
