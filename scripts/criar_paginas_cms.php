<?php
/**
 * Script para Criar Páginas CMS Essenciais
 * Páginas obrigatórias identificadas na auditoria
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "📄 Criando Páginas CMS Essenciais\n";
echo "==================================\n\n";

$pageFactory = $objectManager->get('Magento\Cms\Model\PageFactory');
$pageRepository = $objectManager->get('Magento\Cms\Api\PageRepositoryInterface');

$pagesToCreate = [
    [
        'identifier' => 'about-us',
        'title' => 'Sobre Nós - Grupo Awamotos',
        'page_layout' => '1column',
        'meta_title' => 'Sobre Nós | Grupo Awamotos',
        'meta_description' => 'Conheça a história e os valores do Grupo Awamotos, especialistas em peças automotivas há mais de 10 anos.',
        'content_heading' => 'Sobre o Grupo Awamotos',
        'content' => '<div class="about-us-page">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2>Nossa História</h2>
                <p>Há mais de 10 anos no mercado, o Grupo Awamotos se consolidou como referência em peças e acessórios automotivos. Nossa trajetória é marcada por compromisso, qualidade e inovação.</p>
                
                <h2>Nossa Missão</h2>
                <p>Oferecer produtos automotivos de alta qualidade com os melhores preços do mercado, garantindo atendimento personalizado e entrega rápida para nossos clientes em todo o Brasil.</p>
                
                <h2>Nossos Valores</h2>
                <ul>
                    <li><strong>Qualidade:</strong> Trabalhamos apenas com marcas reconhecidas e produtos certificados.</li>
                    <li><strong>Confiança:</strong> Transparência e honestidade em todas as nossas relações.</li>
                    <li><strong>Compromisso:</strong> Cumprimos prazos e garantimos a satisfação do cliente.</li>
                    <li><strong>Inovação:</strong> Sempre buscando novas tecnologias e soluções para o mercado automotivo.</li>
                </ul>
                
                <h2>Por que Escolher o Grupo Awamotos?</h2>
                <div class="reasons-grid">
                    <div class="reason-item">
                        <h3>✓ Amplo Catálogo</h3>
                        <p>Milhares de produtos para todos os modelos de veículos.</p>
                    </div>
                    <div class="reason-item">
                        <h3>✓ Melhores Preços</h3>
                        <p>Preços competitivos e condições especiais de pagamento.</p>
                    </div>
                    <div class="reason-item">
                        <h3>✓ Entrega Rápida</h3>
                        <p>Logística eficiente para entregas em todo o território nacional.</p>
                    </div>
                    <div class="reason-item">
                        <h3>✓ Atendimento Especializado</h3>
                        <p>Equipe técnica qualificada para tirar todas as suas dúvidas.</p>
                    </div>
                </div>
                
                <h2>Entre em Contato</h2>
                <p>Estamos sempre prontos para atender você. <a href="{{store url=\'contact\'}}">Clique aqui</a> para falar conosco.</p>
            </div>
        </div>
    </div>
</div>'
    ],
    [
        'identifier' => 'terms',
        'title' => 'Termos e Condições de Uso',
        'page_layout' => '1column',
        'meta_title' => 'Termos e Condições | Grupo Awamotos',
        'meta_description' => 'Termos e condições de uso da loja online Grupo Awamotos.',
        'content_heading' => 'Termos e Condições de Uso',
        'content' => '<div class="terms-page">
    <div class="container">
        <p><em>Última atualização: ' . date('d/m/Y') . '</em></p>
        
        <h2>1. Aceitação dos Termos</h2>
        <p>Ao acessar e usar este site, você aceita e concorda em cumprir estes termos e condições de uso. Se você não concordar com qualquer parte destes termos, não deve usar nosso site.</p>
        
        <h2>2. Uso do Site</h2>
        <p>Você concorda em usar o site apenas para fins legais e de maneira que não infrinja os direitos de terceiros ou restrinja ou iniba o uso do site por qualquer outra pessoa.</p>
        
        <h2>3. Cadastro e Conta</h2>
        <p>Para realizar compras, é necessário criar uma conta fornecendo informações verdadeiras, atualizadas e completas. Você é responsável por manter a confidencialidade de sua senha.</p>
        
        <h2>4. Produtos e Preços</h2>
        <p>Todos os produtos estão sujeitos à disponibilidade em estoque. Os preços podem ser alterados sem aviso prévio. Nos reservamos o direito de corrigir erros de preços.</p>
        
        <h2>5. Pedidos e Pagamentos</h2>
        <p>Aceitamos diversas formas de pagamento. Todos os pedidos estão sujeitos à aprovação de crédito e à disponibilidade de produtos.</p>
        
        <h2>6. Entrega</h2>
        <p>Os prazos de entrega são estimativas e podem variar. Não nos responsabilizamos por atrasos causados por terceiros (transportadoras, correios).</p>
        
        <h2>7. Trocas e Devoluções</h2>
        <p>Consulte nossa <a href="{{store url=\'returns\'}}">Política de Trocas e Devoluções</a> para informações detalhadas sobre o processo.</p>
        
        <h2>8. Propriedade Intelectual</h2>
        <p>Todo o conteúdo deste site, incluindo textos, imagens, logos e marcas, é protegido por direitos autorais e propriedade intelectual.</p>
        
        <h2>9. Privacidade</h2>
        <p>Consulte nossa <a href="{{store url=\'privacy-policy\'}}">Política de Privacidade</a> para entender como coletamos e usamos suas informações.</p>
        
        <h2>10. Limitação de Responsabilidade</h2>
        <p>Não nos responsabilizamos por danos indiretos, especiais ou consequenciais resultantes do uso ou incapacidade de usar nosso site ou produtos.</p>
        
        <h2>11. Alterações nos Termos</h2>
        <p>Reservamo-nos o direito de modificar estes termos a qualquer momento. As alterações entram em vigor imediatamente após a publicação.</p>
        
        <h2>12. Contato</h2>
        <p>Para dúvidas sobre estes termos, entre em contato:<br>
        E-mail: contato@grupoawamotos.com.br<br>
        Telefone: (11) 99999-9999</p>
    </div>
</div>'
    ],
    [
        'identifier' => 'privacy-policy',
        'title' => 'Política de Privacidade',
        'page_layout' => '1column',
        'meta_title' => 'Política de Privacidade | Grupo Awamotos',
        'meta_description' => 'Política de privacidade e proteção de dados do Grupo Awamotos.',
        'content_heading' => 'Política de Privacidade e Proteção de Dados',
        'content' => '<div class="privacy-policy-page">
    <div class="container">
        <p><em>Última atualização: ' . date('d/m/Y') . '</em></p>
        
        <h2>1. Introdução</h2>
        <p>O Grupo Awamotos respeita sua privacidade e está comprometido em proteger seus dados pessoais. Esta política explica como coletamos, usamos e protegemos suas informações.</p>
        
        <h2>2. Informações que Coletamos</h2>
        <h3>2.1 Informações Fornecidas por Você</h3>
        <ul>
            <li>Nome completo</li>
            <li>CPF/CNPJ</li>
            <li>Endereço de e-mail</li>
            <li>Telefone</li>
            <li>Endereço de entrega e cobrança</li>
            <li>Dados de pagamento (processados por gateways seguros)</li>
        </ul>
        
        <h3>2.2 Informações Coletadas Automaticamente</h3>
        <ul>
            <li>Endereço IP</li>
            <li>Tipo de navegador</li>
            <li>Páginas visitadas</li>
            <li>Cookies e tecnologias similares</li>
        </ul>
        
        <h2>3. Como Usamos Suas Informações</h2>
        <p>Utilizamos suas informações para:</p>
        <ul>
            <li>Processar e entregar pedidos</li>
            <li>Enviar comunicações sobre pedidos e promoções</li>
            <li>Melhorar nossos produtos e serviços</li>
            <li>Prevenir fraudes e garantir segurança</li>
            <li>Cumprir obrigações legais</li>
        </ul>
        
        <h2>4. Compartilhamento de Informações</h2>
        <p>Podemos compartilhar suas informações com:</p>
        <ul>
            <li>Processadores de pagamento (Mercado Pago, etc.)</li>
            <li>Transportadoras e correios</li>
            <li>Provedores de serviços de marketing</li>
            <li>Autoridades legais quando exigido por lei</li>
        </ul>
        
        <h2>5. Segurança dos Dados</h2>
        <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados contra acesso não autorizado, perda ou alteração.</p>
        
        <h2>6. Seus Direitos (LGPD)</h2>
        <p>Conforme a Lei Geral de Proteção de Dados (LGPD), você tem direito a:</p>
        <ul>
            <li>Confirmação da existência de tratamento de dados</li>
            <li>Acesso aos seus dados</li>
            <li>Correção de dados incompletos ou desatualizados</li>
            <li>Anonimização, bloqueio ou eliminação de dados desnecessários</li>
            <li>Portabilidade de dados</li>
            <li>Revogação do consentimento</li>
        </ul>
        
        <h2>7. Cookies</h2>
        <p>Utilizamos cookies para melhorar sua experiência. Você pode configurar seu navegador para recusar cookies, mas isso pode afetar algumas funcionalidades do site.</p>
        
        <h2>8. Retenção de Dados</h2>
        <p>Mantemos seus dados pelo tempo necessário para cumprir as finalidades descritas nesta política e conforme exigido por lei.</p>
        
        <h2>9. Alterações nesta Política</h2>
        <p>Podemos atualizar esta política periodicamente. Notificaremos sobre mudanças significativas através do site ou por e-mail.</p>
        
        <h2>10. Contato do Encarregado de Dados</h2>
        <p>Para exercer seus direitos ou esclarecer dúvidas sobre privacidade:<br>
        E-mail: privacidade@grupoawamotos.com.br<br>
        Telefone: (11) 99999-9999</p>
    </div>
</div>'
    ],
    [
        'identifier' => 'shipping-policy',
        'title' => 'Política de Envio e Entrega',
        'page_layout' => '1column',
        'meta_title' => 'Política de Envio | Grupo Awamotos',
        'meta_description' => 'Informações sobre prazos de entrega, frete e envio de produtos do Grupo Awamotos.',
        'content_heading' => 'Política de Envio e Entrega',
        'content' => '<div class="shipping-policy-page">
    <div class="container">
        <h2>Modalidades de Entrega</h2>
        <p>Trabalhamos com as melhores transportadoras para garantir que seu pedido chegue com segurança e rapidez.</p>
        
        <h3>1. Correios</h3>
        <ul>
            <li><strong>PAC:</strong> 8 a 15 dias úteis</li>
            <li><strong>SEDEX:</strong> 2 a 5 dias úteis</li>
            <li><strong>SEDEX 12:</strong> Entrega no dia seguinte (capitais)</li>
        </ul>
        
        <h3>2. Transportadoras Parceiras</h3>
        <p>Para produtos maiores, utilizamos transportadoras especializadas. O prazo varia conforme a localidade.</p>
        
        <h2>Cálculo de Frete</h2>
        <p>O valor do frete é calculado automaticamente com base em:</p>
        <ul>
            <li>CEP de destino</li>
            <li>Peso e dimensões dos produtos</li>
            <li>Modalidade de envio escolhida</li>
        </ul>
        
        <h2>Frete Grátis</h2>
        <p>🚚 <strong>Frete Grátis</strong> para compras acima de <strong>R$ 299,00</strong> para todo o Brasil (via PAC)!</p>
        
        <h2>Prazos de Processamento</h2>
        <p>Após a confirmação do pagamento:</p>
        <ul>
            <li><strong>Produtos em estoque:</strong> 1 a 2 dias úteis para separação e postagem</li>
            <li><strong>Produtos sob encomenda:</strong> 5 a 10 dias úteis antes do envio</li>
        </ul>
        
        <h2>Rastreamento</h2>
        <p>Após o envio, você receberá:</p>
        <ul>
            <li>E-mail com código de rastreamento</li>
            <li>Link para acompanhar a entrega</li>
            <li>Atualizações sobre o status do pedido</li>
        </ul>
        <p>Você também pode rastrear seu pedido em <a href="{{store url=\'sales/order/history\'}}">Minha Conta</a>.</p>
        
        <h2>Entrega</h2>
        <p><strong>Importante:</strong></p>
        <ul>
            <li>É necessário ter alguém no endereço para receber o pedido</li>
            <li>Pode ser solicitado documento com foto</li>
            <li>Verifique a integridade da embalagem antes de assinar o recebimento</li>
            <li>Em caso de avaria, recuse o recebimento e entre em contato conosco</li>
        </ul>
        
        <h2>Áreas de Entrega</h2>
        <p>Entregamos para <strong>todo o Brasil</strong>!</p>
        <p>Algumas regiões remotas podem ter prazos estendidos.</p>
        
        <h2>Problemas na Entrega</h2>
        <p>Se houver problemas com sua entrega:</p>
        <ul>
            <li>Entre em contato em até 7 dias após o prazo previsto</li>
            <li>Informe o número do pedido e CPF/CNPJ</li>
            <li>Nossa equipe irá resolver a situação</li>
        </ul>
        
        <h2>Contato</h2>
        <p>Dúvidas sobre entrega? Fale conosco:<br>
        📧 E-mail: entregas@grupoawamotos.com.br<br>
        📞 Telefone: (11) 99999-9999<br>
        💬 WhatsApp: (11) 99999-9999</p>
    </div>
</div>'
    ]
];

$created = 0;
$errors = 0;

foreach ($pagesToCreate as $pageData) {
    try {
        // Verificar se página já existe
        try {
            $searchCriteria = $objectManager->create('Magento\Framework\Api\SearchCriteriaBuilder')
                ->addFilter('identifier', $pageData['identifier'])
                ->create();
            
            $pages = $pageRepository->getList($searchCriteria)->getItems();
            
            if (!empty($pages)) {
                $page = reset($pages);
                echo "⚠️  Página '{$pageData['identifier']}' já existe (ID: {$page->getId()}). Atualizando...\n";
            } else {
                throw new \Exception('Criar nova página');
            }
            
        } catch (\Exception $e) {
            echo "➕ Criando página '{$pageData['identifier']}'...\n";
            $page = $pageFactory->create();
            $page->setIdentifier($pageData['identifier']);
            $page->setStores([0]);
        }
        
        $page->setTitle($pageData['title']);
        $page->setPageLayout($pageData['page_layout']);
        $page->setMetaTitle($pageData['meta_title']);
        $page->setMetaDescription($pageData['meta_description']);
        $page->setContentHeading($pageData['content_heading']);
        $page->setContent($pageData['content']);
        $page->setIsActive(1);
        $page->setSortOrder(0);
        
        $pageRepository->save($page);
        
        echo "✅ Página '{$pageData['identifier']}' salva com sucesso!\n";
        echo "   Título: {$pageData['title']}\n";
        echo "   URL: {{store url='{$pageData['identifier']}'}}\n\n";
        
        $created++;
        
    } catch (\Exception $e) {
        echo "❌ Erro ao criar página '{$pageData['identifier']}': {$e->getMessage()}\n\n";
        $errors++;
    }
}

echo "\n==================================\n";
echo "📊 Resumo da Execução:\n";
echo "   ✅ Páginas criadas/atualizadas: $created\n";
echo "   ❌ Erros: $errors\n";
echo "   📦 Total processado: " . count($pagesToCreate) . "\n";
echo "==================================\n\n";

if ($created > 0) {
    echo "🎉 Páginas CMS essenciais criadas!\n";
    echo "📋 Páginas disponíveis:\n";
    echo "   - /about-us (Sobre Nós)\n";
    echo "   - /terms (Termos e Condições)\n";
    echo "   - /privacy-policy (Política de Privacidade)\n";
    echo "   - /shipping-policy (Política de Envio)\n\n";
    echo "🔄 Execute: php bin/magento cache:flush\n";
}

echo "\n✅ Script concluído!\n";
