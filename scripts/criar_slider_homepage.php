<?php
/**
 * Script para criar slider homepage com ID "homepageslider"
 * Conforme documentação Ayo: https://ayo.nextsky.co/documentation/
 * 
 * Execução:
 * php scripts/criar_slider_homepage.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Factories e Resources
$sliderFactory = $objectManager->get(\Rokanthemes\SlideBanner\Model\SliderFactory::class);
$sliderResource = $objectManager->get(\Rokanthemes\SlideBanner\Model\Resource\Slider::class);
$sliderCollectionFactory = $objectManager->get(\Rokanthemes\SlideBanner\Model\Resource\Slider\CollectionFactory::class);

$slideFactory = $objectManager->get(\Rokanthemes\SlideBanner\Model\SlideFactory::class);
$slideResource = $objectManager->get(\Rokanthemes\SlideBanner\Model\Resource\Slide::class);

echo "=== CRIAÇÃO DO SLIDER HOMEPAGE ===\n\n";

// Verificar se slider já existe
$collection = $sliderCollectionFactory->create()
    ->addFieldToFilter('slider_identifier', 'homepageslider');

if ($collection->getSize() > 0) {
    echo "⚠️  Slider 'homepageslider' já existe.\n";
    $slider = $collection->getFirstItem();
} else {
    // Criar novo slider
    echo "📋 Criando slider 'homepageslider'...\n";
    $slider = $sliderFactory->create();
    
    // Configurações do slider como JSON
    $sliderSettings = json_encode([
        'items' => 1,
        'itemsDesktop' => '[1199,1]',
        'itemsDesktopSmall' => '[980,1]',
        'itemsTablet' => '[768,1]',
        'itemsMobile' => '[479,1]',
        'slideSpeed' => 500,
        'paginationSpeed' => 500,
        'rewindSpeed' => 1000,
        'autoPlay' => 5000,
        'stopOnHover' => true,
        'navigation' => true,
        'pagination' => true,
    ]);
    
    $sliderData = [
        'slider_title' => 'Homepage Slider',
        'slider_identifier' => 'homepageslider',
        'store_ids' => '0', // All Store Views
        'slider_status' => 1,
        'slider_setting' => $sliderSettings,
        'slider_template' => 'slider.phtml',
    ];
    
    $slider->setData($sliderData);
    
    try {
        $sliderResource->save($slider);
        echo "✅ Slider criado com sucesso! ID: " . $slider->getId() . "\n\n";
    } catch (\Exception $e) {
        echo "❌ Erro ao criar slider: " . $e->getMessage() . "\n";
        exit(1);
    }
}

// Criar slides
echo "=== CRIANDO SLIDES ===\n\n";

$slides = [
    [
        'text' => '<div class="slide-content"><h2>Bem-vindo ao Grupo Awamotos</h2><p>As melhores peças e acessórios para seu veículo</p><a href="/catalog" class="btn btn-primary">Ver Catálogo</a></div>',
        'link' => '/catalog',
        'position' => 1,
    ],
    [
        'text' => '<div class="slide-content"><h2>Ofertas Imperdíveis</h2><p>Até 50% de desconto em produtos selecionados</p><a href="/sale" class="btn btn-danger">Aproveitar</a></div>',
        'link' => '/sale',
        'position' => 2,
    ],
    [
        'text' => '<div class="slide-content"><h2>Frete Grátis</h2><p>Em compras acima de R$ 299,00</p><a href="/shipping-policy" class="btn btn-success">Saiba Mais</a></div>',
        'link' => '/shipping-policy',
        'position' => 3,
    ],
];

$createdCount = 0;

foreach ($slides as $slideData) {
    echo "📸 Criando slide {$slideData['position']}...\n";
    
    $slide = $slideFactory->create();
    
    $slideDataToSave = [
        'slider_id' => $slider->getId(),
        'slide_type' => 1, // Tipo padrão
        'slide_text' => $slideData['text'],
        'slide_image' => '', // Vazio - será necessário upload manual via admin
        'slide_image_mobile' => '', // Vazio
        'slide_link' => $slideData['link'],
        'slide_status' => 1,
        'slide_position' => $slideData['position'],
    ];
    
    $slide->setData($slideDataToSave);
    
    try {
        $slideResource->save($slide);
        echo "   ✅ Slide criado! ID: " . $slide->getId() . "\n";
        $createdCount++;
    } catch (\Exception $e) {
        echo "   ❌ Erro ao criar slide: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESUMO ===\n";
echo "✅ Slider ID: homepageslider (" . $slider->getId() . ")\n";
echo "✅ Slides criados: $createdCount/3\n";
echo "✅ Autoplay: Habilitado (5s)\n";
echo "✅ Navigation: Habilitado\n";
echo "✅ Pagination: Habilitado\n\n";

echo "⚠️  AÇÃO NECESSÁRIA:\n";
echo "1. Fazer upload das imagens dos slides via Admin:\n";
echo "   Admin > Rokanthemes > Manage Slider Items\n";
echo "2. Recomendação: Imagens 1920x600px\n";
echo "3. Formatos: JPG, PNG\n";
echo "4. Tamanho máximo: 2MB por imagem\n\n";

echo "✅ Script concluído!\n";
