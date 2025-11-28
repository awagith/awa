<?php
/**
 * Registrar Temas Ayo no Banco de Dados
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🎨 Registrando Temas Ayo no Banco de Dados\n";
echo "==========================================\n\n";

$themeFactory = $objectManager->get('Magento\Theme\Model\ThemeFactory');
$themeCollection = $objectManager->get('Magento\Theme\Model\ResourceModel\Theme\CollectionFactory')->create();

// Temas Ayo disponíveis
$ayoThemes = [
    'ayo_default',
    'ayo_home2',
    'ayo_home3',
    'ayo_home4',
    'ayo_home5',
    'ayo_home6',
    'ayo_home7',
    'ayo_home8',
    'ayo_home9',
    'ayo_home10',
    'ayo_home11',
    'ayo_home12',
    'ayo_home13',
    'ayo_home14',
    'ayo_home15',
    'ayo_home16'
];

foreach ($ayoThemes as $themeName) {
    $themePath = "frontend/ayo/{$themeName}";
    
    // Verificar se já existe
    $existingTheme = $themeCollection->getThemeByFullPath($themePath);
    
    if ($existingTheme && $existingTheme->getId()) {
        echo "   - Tema já existe: {$themePath} (ID: {$existingTheme->getId()})\n";
        continue;
    }
    
    try {
        // Criar novo tema
        $theme = $themeFactory->create();
        $theme->setData([
            'parent_id' => null,
            'theme_path' => $themeName,
            'theme_title' => 'Ayo ' . ucfirst(str_replace('_', ' ', $themeName)),
            'theme_version' => '1.0.0',
            'type' => \Magento\Framework\View\Design\ThemeInterface::TYPE_PHYSICAL,
            'area' => 'frontend',
            'code' => "ayo/{$themeName}"
        ]);
        
        $theme->save();
        
        echo "   ✓ Tema registrado: {$themePath} (ID: {$theme->getId()})\n";
        
    } catch (\Exception $e) {
        echo "   ✗ Erro ao registrar {$themePath}: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ Processo concluído!\n";
echo "======================\n\n";

// Listar todos os temas registrados
echo "Temas Frontend Registrados:\n";
$allThemes = $objectManager->create('Magento\Theme\Model\ResourceModel\Theme\Collection');
foreach ($allThemes as $theme) {
    if ($theme->getArea() == 'frontend') {
        echo "  ID: " . $theme->getId() . " | " . $theme->getFullPath() . "\n";
    }
}
