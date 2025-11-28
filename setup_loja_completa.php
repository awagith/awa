<?php
/**
 * Script de Configuração Completa da Loja Ayo
 * Cria blocos CMS, páginas e configurações básicas
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🚀 Iniciando Configuração da Loja Ayo\n";
echo "=====================================\n\n";

// 1. CRIAR BLOCOS CMS ESSENCIAIS
echo "📄 Criando Blocos CMS...\n";

$blockFactory = $objectManager->get('Magento\Cms\Model\BlockFactory');

$blocks = [
    [
        'identifier' => 'footer_info',
        'title' => 'Footer - Informações',
        'content' => '<div class="footer-info">
            <h4>Sobre Nossa Loja</h4>
            <p>Loja completa com tema Ayo Magento 2</p>
            <p>Endereço: Rua Exemplo, 123 - São Paulo, SP</p>
            <p>Telefone: (11) 1234-5678</p>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'social_block',
        'title' => 'Redes Sociais',
        'content' => '<div class="social-links">
            <a href="#" class="facebook">Facebook</a>
            <a href="#" class="instagram">Instagram</a>
            <a href="#" class="twitter">Twitter</a>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'footer_menu',
        'title' => 'Footer - Menu',
        'content' => '<div class="footer-menu">
            <h4>Links Úteis</h4>
            <ul>
                <li><a href="{{store url="about-us"}}">Sobre Nós</a></li>
                <li><a href="{{store url="customer/account"}}">Minha Conta</a></li>
                <li><a href="{{store url="contact"}}">Contato</a></li>
            </ul>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'home_slider',
        'title' => 'Home - Slider Principal',
        'content' => '<div class="banner-slider">
            {{block class="Rokanthemes\\SlideBanner\\Block\\Slider" slider_id="homepageslider" template="slider.phtml"}}
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'home_featured',
        'title' => 'Home - Produtos em Destaque',
        'content' => '<div class="featured-products">
            <h2>Produtos em Destaque</h2>
            {{block class="Rokanthemes\\Featuredpro\\Block\\Widget\\Featuredpro" template="widget/featuredpro_list.phtml"}}
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'home_new_products',
        'title' => 'Home - Novos Produtos',
        'content' => '<div class="new-products">
            <h2>Novos Produtos</h2>
            {{block class="Rokanthemes\\Newproduct\\Block\\Widget\\Newproduct" template="widget/newproduct_list.phtml"}}
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ],
    [
        'identifier' => 'home_banner_promo',
        'title' => 'Home - Banner Promocional',
        'content' => '<div class="promo-banner">
            <h2>🔥 Promoções Especiais</h2>
            <p>Confira nossas ofertas incríveis!</p>
        </div>',
        'is_active' => 1,
        'stores' => [0]
    ]
];

foreach ($blocks as $blockData) {
    try {
        $block = $blockFactory->create();
        $block->load($blockData['identifier'], 'identifier');
        
        if (!$block->getId()) {
            $block->setData($blockData)->save();
            echo "   ✓ Bloco criado: {$blockData['identifier']}\n";
        } else {
            echo "   - Bloco já existe: {$blockData['identifier']}\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Erro ao criar {$blockData['identifier']}: " . $e->getMessage() . "\n";
    }
}

// 2. CRIAR PÁGINA INICIAL
echo "\n📝 Criando Página Inicial...\n";

$pageFactory = $objectManager->get('Magento\Cms\Model\PageFactory');

$homePage = $pageFactory->create();
$homePage->load('home', 'identifier');

$homeContent = <<<HTML
{{block class="Magento\\Cms\\Block\\Block" block_id="home_slider"}}

<div class="home-content">
    {{block class="Magento\\Cms\\Block\\Block" block_id="home_featured"}}
    {{block class="Magento\\Cms\\Block\\Block" block_id="home_banner_promo"}}
    {{block class="Magento\\Cms\\Block\\Block" block_id="home_new_products"}}
</div>
HTML;

try {
    if ($homePage->getId()) {
        $homePage->setContent($homeContent);
        $homePage->save();
        echo "   ✓ Página inicial atualizada\n";
    } else {
        $homePage->setData([
            'title' => 'Home Page',
            'identifier' => 'home',
            'content' => $homeContent,
            'is_active' => 1,
            'page_layout' => '1column',
            'stores' => [0]
        ])->save();
        echo "   ✓ Página inicial criada\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 3. CONFIGURAR HOMEPAGE PADRÃO
echo "\n⚙️  Configurando Homepage Padrão...\n";

$configWriter = $objectManager->get('Magento\Framework\App\Config\Storage\WriterInterface');

try {
    $configWriter->save('web/default/cms_home_page', 'home');
    echo "   ✓ Homepage padrão configurada\n";
} catch (\Exception $e) {
    echo "   ✗ Erro: " . $e->getMessage() . "\n";
}

// 4. CRIAR CATEGORIAS BÁSICAS
echo "\n📁 Criando Categorias...\n";

$categoryFactory = $objectManager->get('Magento\Catalog\Model\CategoryFactory');
$storeManager = $objectManager->get('Magento\Store\Model\StoreManager');

$categories = [
    ['name' => 'Eletrônicos', 'url_key' => 'eletronicos'],
    ['name' => 'Moda', 'url_key' => 'moda'],
    ['name' => 'Casa e Decoração', 'url_key' => 'casa-decoracao'],
    ['name' => 'Esportes', 'url_key' => 'esportes']
];

$rootCategoryId = $storeManager->getStore()->getRootCategoryId();

foreach ($categories as $catData) {
    try {
        $category = $categoryFactory->create();
        $category->getResource()->load($category, $catData['url_key'], 'url_key');
        
        if (!$category->getId()) {
            $category->setData([
                'name' => $catData['name'],
                'url_key' => $catData['url_key'],
                'is_active' => 1,
                'include_in_menu' => 1,
                'parent_id' => $rootCategoryId
            ]);
            $category->save();
            echo "   ✓ Categoria criada: {$catData['name']}\n";
        } else {
            echo "   - Categoria já existe: {$catData['name']}\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Erro ao criar {$catData['name']}: " . $e->getMessage() . "\n";
    }
}

// 5. CONFIGURAÇÕES DO TEMA
echo "\n🎨 Aplicando Configurações do Tema...\n";

$configs = [
    // Newsletter Popup
    ['path' => 'rokanthemes_themeoption/newsletter_popup/enable', 'value' => '1'],
    ['path' => 'rokanthemes_themeoption/newsletter_popup/width', 'value' => '600'],
    ['path' => 'rokanthemes_themeoption/newsletter_popup/height', 'value' => '400'],
    
    // Custom Menu
    ['path' => 'rokanthemes_custommenu/general/enable', 'value' => '1'],
    
    // Quick View
    ['path' => 'rokanthemes_quickview/general/enable', 'value' => '1'],
    
    // Ajax Cart
    ['path' => 'rokanthemes_ajaxsuite/general/ajaxcart_enable', 'value' => '1'],
    ['path' => 'rokanthemes_ajaxsuite/general/ajaxcompare_enable', 'value' => '1'],
    ['path' => 'rokanthemes_ajaxsuite/general/ajaxwishlist_enable', 'value' => '1'],
];

foreach ($configs as $config) {
    try {
        $configWriter->save($config['path'], $config['value']);
        echo "   ✓ {$config['path']}\n";
    } catch (\Exception $e) {
        echo "   ✗ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Configuração Completa!\n";
echo "=====================================\n\n";
echo "📌 Próximos Passos:\n";
echo "1. Acesse o Admin: http://seu-dominio.com/admin\n";
echo "2. Configure MercadoPago: Stores > Configuration > Sales > Payment Methods\n";
echo "3. Configure Correios: Stores > Configuration > Sales > Shipping Methods\n";
echo "4. Adicione produtos: Catalog > Products\n";
echo "5. Configure slider: Rokanthemes > Manager Slider\n\n";
