<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();

$appState = $obj->get('Magento\Framework\App\State');
$appState->setAreaCode('adminhtml');

$pageRepository = $obj->get('Magento\Cms\Api\PageRepositoryInterface');
$blockRepository = $obj->get('Magento\Cms\Api\BlockRepositoryInterface');
$searchCriteriaBuilder = $obj->get('Magento\Framework\Api\SearchCriteriaBuilder');

// Mapeamento de Páginas
$pagesToTranslate = [
    'no-route' => [
        'title' => '404 Não Encontrado',
        'content_heading' => 'Página não encontrada'
    ],
    'home' => [
        'title' => 'Página Inicial'
    ],
    'about-us' => [
        'title' => 'Sobre Nós',
        'content_heading' => 'Sobre Nós',
        'content' => '<div class="about-info cms-content">
    <p class="cms-content-important">Sobre a Nossa Loja</p>
    <p>Somos uma empresa dedicada a oferecer os melhores produtos com qualidade e excelência no atendimento.</p>
</div>'
    ],
    'customer-service' => [
        'title' => 'Atendimento ao Cliente',
        'content_heading' => 'Atendimento ao Cliente',
        'content' => '<p>Estamos aqui para ajudar! Entre em contato conosco através do formulário abaixo ou pelos nossos canais de atendimento.</p>'
    ],
    'privacy-policy-cookie-restriction-mode' => [
        'title' => 'Política de Privacidade',
        'content_heading' => 'Política de Privacidade',
        'content' => '<p>Sua privacidade é importante para nós. Esta política explica como coletamos, usamos e protegemos suas informações pessoais.</p>'
    ],
    'enable-cookies' => [
        'title' => 'Habilitar Cookies',
        'content_heading' => 'Habilitar Cookies'
    ]
];

// Páginas para criar se não existirem
$pagesToCreate = [
    'about-us' => [
        'title' => 'Sobre Nós',
        'content_heading' => 'Sobre Nós',
        'content' => '<div class="about-info cms-content">
    <p class="cms-content-important">Sobre a Nossa Loja</p>
    <p>Somos uma empresa dedicada a oferecer os melhores produtos com qualidade e excelência no atendimento.</p>
    <p>Nossa missão é proporcionar a melhor experiência de compra online para nossos clientes.</p>
</div>',
        'meta_title' => 'Sobre Nós',
        'meta_keywords' => 'sobre nós, loja, empresa',
        'meta_description' => 'Saiba mais sobre nossa empresa e nossa história.'
    ],
    'customer-service' => [
        'title' => 'Atendimento ao Cliente',
        'content_heading' => 'Atendimento ao Cliente',
        'content' => '<div class="customer-service cms-content">
    <p>Estamos aqui para ajudar! Entre em contato conosco através do formulário abaixo ou pelos nossos canais de atendimento.</p>
    <ul>
        <li>Email: contato@loja.com.br</li>
        <li>Telefone: (11) 99999-9999</li>
        <li>Horário de Atendimento: Seg-Sex, 9h às 18h</li>
    </ul>
</div>',
        'meta_title' => 'Atendimento ao Cliente',
        'meta_keywords' => 'atendimento, contato, suporte',
        'meta_description' => 'Entre em contato com nosso atendimento ao cliente.'
    ]
];

// Mapeamento de Blocos (Exemplos comuns do Magento Luma/Base, ajuste conforme seu tema)
$blocksToTranslate = [
    'footer_info' => [
        'title' => 'Footer - Informações',
        'content' => '<div class="footer-info">
    <h4>Sobre Nossa Loja</h4>
    <p>Loja completa com tema Ayo Magento 2</p>
    <p>Endereço: Rua Exemplo, 123 - São Paulo, SP</p>
    <p>Telefone: (11) 1234-5678</p>
</div>'
    ],
    'footer_menu' => [
        'title' => 'Footer - Menu',
        'content' => '<div class="footer-menu">
    <h4>Links Úteis</h4>
    <ul>
        <li><a href="{{store url="about-us"}}">Sobre Nós</a></li>
        <li><a href="{{store url="customer/account"}}">Minha Conta</a></li>
        <li><a href="{{store url="contact"}}">Contato</a></li>
        <li><a href="{{store url="privacy-policy-cookie-restriction-mode"}}">Política de Privacidade</a></li>
    </ul>
</div>'
    ],
    'social_block' => [
        'title' => 'Redes Sociais',
        'content' => '<div class="social-links">
    <a href="#" target="_blank" class="facebook" title="Facebook">Facebook</a>
    <a href="#" target="_blank" class="instagram" title="Instagram">Instagram</a>
    <a href="#" target="_blank" class="twitter" title="Twitter">Twitter</a>
</div>'
    ]
];

echo "Iniciando tradução de CMS Pages...\n";

foreach ($pagesToTranslate as $identifier => $data) {
    $searchCriteria = $searchCriteriaBuilder->addFilter('identifier', $identifier, 'eq')->create();
    $pages = $pageRepository->getList($searchCriteria)->getItems();

    if (empty($pages)) {
        echo "Página '$identifier' não encontrada.\n";
        continue;
    }

    foreach ($pages as $page) {
        echo "Atualizando página: " . $page->getTitle() . " ($identifier)\n";
        $page->setTitle($data['title']);
        if (isset($data['content_heading'])) {
            $page->setContentHeading($data['content_heading']);
        }
        // Cuidado ao sobrescrever conteúdo de páginas complexas como Home
        if (isset($data['content']) && $identifier !== 'home') {
            $page->setContent($data['content']);
        }
        try {
            $pageRepository->save($page);
            echo " - Sucesso!\n";
        } catch (\Exception $e) {
            echo " - Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nIniciando criação de CMS Pages faltantes...\n";

foreach ($pagesToCreate as $identifier => $data) {
    $searchCriteria = $searchCriteriaBuilder->addFilter('identifier', $identifier, 'eq')->create();
    $pages = $pageRepository->getList($searchCriteria)->getItems();

    if (empty($pages)) {
        echo "Criando página '$identifier'...\n";
        $page = $obj->create('Magento\Cms\Model\Page');
        $page->setIdentifier($identifier);
        $page->setTitle($data['title']);
        $page->setContentHeading($data['content_heading']);
        $page->setContent($data['content']);
        $page->setMetaTitle($data['meta_title']);
        $page->setMetaKeywords($data['meta_keywords']);
        $page->setMetaDescription($data['meta_description']);
        $page->setPageLayout('1column');
        $page->setIsActive(1);
        $page->setStoreId([0]); // All Store Views
        
        try {
            $pageRepository->save($page);
            echo " - Criada com sucesso!\n";
        } catch (\Exception $e) {
            echo " - Erro ao criar: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Página '$identifier' já existe. Atualizando...\n";
        foreach ($pages as $page) {
            $page->setTitle($data['title']);
            $page->setContentHeading($data['content_heading']);
            $page->setContent($data['content']);
            try {
                $pageRepository->save($page);
                echo " - Atualizada com sucesso!\n";
            } catch (\Exception $e) {
                echo " - Erro ao atualizar: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\nIniciando tradução de CMS Blocks...\n";

foreach ($blocksToTranslate as $identifier => $data) {
    $searchCriteria = $searchCriteriaBuilder->addFilter('identifier', $identifier, 'eq')->create();
    $blocks = $blockRepository->getList($searchCriteria)->getItems();

    if (empty($blocks)) {
        echo "Bloco '$identifier' não encontrado.\n";
        continue;
    }

    foreach ($blocks as $block) {
        echo "Atualizando bloco: " . $block->getTitle() . " ($identifier)\n";
        $block->setTitle($data['title']);
        if (isset($data['content'])) {
            $block->setContent($data['content']);
        }
        try {
            $blockRepository->save($block);
            echo " - Sucesso!\n";
        } catch (\Exception $e) {
            echo " - Erro: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nConcluído.\n";
