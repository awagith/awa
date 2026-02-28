<?php
/**
 * Script para associar imagens SVG aos slides do homepage slider
 * 
 * Execução:
 * php scripts/associar_imagens_slides.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Resources
$slideFactory = $objectManager->get(\Rokanthemes\SlideBanner\Model\SlideFactory::class);
$slideResource = $objectManager->get(\Rokanthemes\SlideBanner\Model\Resource\Slide::class);

echo "=== ASSOCIAR IMAGENS AOS SLIDES ===\n\n";

// Buscar slides do slider homepageslider
$sliderFactory = $objectManager->get(\Rokanthemes\SlideBanner\Model\SliderFactory::class);
$sliderCollection = $objectManager->get(\Rokanthemes\SlideBanner\Model\Resource\Slider\CollectionFactory::class);

$slider = $sliderCollection->create()
    ->addFieldToFilter('slider_identifier', 'homepageslider')
    ->getFirstItem();

if (!$slider->getId()) {
    echo "❌ Slider 'homepageslider' não encontrado!\n";
    exit(1);
}

echo "✅ Slider encontrado: ID {$slider->getId()}\n\n";

// Buscar slides via query direta
$connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)->getConnection();
$slideTable = $connection->getTableName('rokanthemes_slide');

$slideIds = $connection->fetchAll(
    "SELECT slide_id, slide_position FROM {$slideTable} WHERE slider_id = ? ORDER BY slide_position ASC",
    [$slider->getId()]
);

if (empty($slideIds)) {
    echo "❌ Nenhum slide encontrado para este slider!\n";
    exit(1);
}

// Imagens correspondentes
$images = [
    1 => 'slidebanner/slide-1-bem-vindo.svg',
    2 => 'slidebanner/slide-2-ofertas.svg',
    3 => 'slidebanner/slide-3-frete-gratis.svg',
];

$updatedCount = 0;

foreach ($slideIds as $slideData) {
    $position = $slideData['slide_position'];
    $slideId = $slideData['slide_id'];
    
    if (isset($images[$position])) {
        echo "📸 Atualizando Slide {$position} (ID: {$slideId})...\n";
        
        // Carregar slide
        $slide = $slideFactory->create();
        $slideResource->load($slide, $slideId);
        
        if ($slide->getId()) {
            $slide->setData('slide_image', $images[$position]);
            
            try {
                $slideResource->save($slide);
                echo "   ✅ Imagem associada: {$images[$position]}\n";
                $updatedCount++;
            } catch (\Exception $e) {
                echo "   ❌ Erro: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n=== RESUMO ===\n";
echo "✅ Slides atualizados: $updatedCount/3\n";
echo "✅ Imagens SVG associadas automaticamente\n";
echo "✅ Slider homepage pronto para uso!\n\n";

echo "📍 VERIFICAR NO FRONTEND:\n";
echo "   URL: https://srv1113343.hstgr.cloud/\n";
echo "   O slider deve aparecer na homepage com as 3 imagens SVG\n\n";

echo "✅ Script concluído!\n";
