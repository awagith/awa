<?php
/**
 * Script para otimização SEO on-page
 * - Configurar URLs amigáveis
 * - Configurar sitemap
 * - Otimizar configurações de SEO
 */

require_once 'app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$cacheManager = $objectManager->get(\Magento\Framework\App\Cache\Manager::class);

echo "🔧 Configurando otimizações SEO on-page...\n\n";

// 1. Configurações de URL
echo "1️⃣ Configurando URLs amigáveis...\n";

$urlConfigs = [
    // Remover category path dos produtos
    'catalog/seo/product_use_categories' => 0,
    
    // Salvar histórico de rewrites
    'catalog/seo/save_rewrites_history' => 1,
    
    // URLs de categoria com sufixo .html
    'catalog/seo/category_url_suffix' => '.html',
    
    // URLs de produto com sufixo .html  
    'catalog/seo/product_url_suffix' => '.html',
    
    // CMS pages com sufixo .html
    'web/seo/use_rewrites' => 1
];

foreach ($urlConfigs as $path => $value) {
    $configWriter->save($path, $value, 'default', 0);
    echo "✅ $path = $value\n";
}

// 2. Sitemap configuration
echo "\n2️⃣ Configurando Sitemap XML...\n";

$sitemapConfigs = [
    'sitemap/generate/enabled' => 1,
    'sitemap/generate/time' => '02:00:00',
    'sitemap/generate/frequency' => 'D', // Daily
    'sitemap/category/changefreq' => 'weekly',
    'sitemap/category/priority' => '0.8',
    'sitemap/product/changefreq' => 'daily',
    'sitemap/product/priority' => '0.7',
    'sitemap/page/changefreq' => 'monthly',
    'sitemap/page/priority' => '0.6'
];

foreach ($sitemapConfigs as $path => $value) {
    $configWriter->save($path, $value, 'default', 0);
    echo "✅ $path = $value\n";
}

// 3. Meta tags e SEO geral
echo "\n3️⃣ Configurando Meta Tags e SEO...\n";

$seoConfigs = [
    // Meta robots
    'design/search_engine_robots/default_robots' => 'INDEX,FOLLOW',
    
    // Meta charset
    'design/head/default_charset' => 'UTF-8',
    
    // Meta viewport — já definido pelo module-theme base (vendor/magento/module-theme);
    // não duplicar via design/head/includes
    // 'design/head/includes' => '',
    
    // Canonical URL
    'catalog/seo/canonical_url' => 1,
    
    // Title separator
    'catalog/seo/title_separator' => ' | ',
    
    // Category meta title template
    'catalog/seo/category_meta_title' => '{{name}} - Grupo Awamotos',
    
    // Product meta title template  
    'catalog/seo/product_meta_title' => '{{name}} - {{brand}} | Grupo Awamotos',
    
    // Default meta description para homepage
    'design/head/default_description' => 'Grupo Awamotos - Especialistas em peças e acessórios para motocicletas. Capacetes, baús, luvas, escapamentos e muito mais com entrega rápida e segura para todo o Brasil.',
    
    // Default title
    'design/head/default_title' => 'Grupo Awamotos - Peças e Acessórios para Motos',
    
    // Title prefix/suffix
    'design/head/title_prefix' => '',
    'design/head/title_suffix' => ' | Grupo Awamotos'
];

foreach ($seoConfigs as $path => $value) {
    $configWriter->save($path, $value, 'default', 0);
    echo "✅ $path = $value\n";
}

// 4. Performance e cache
echo "\n4️⃣ Otimizações de Performance...\n";

$performanceConfigs = [
    // Habilitar flat catalog
    'catalog/frontend/flat_catalog_category' => 1,
    'catalog/frontend/flat_catalog_product' => 1,
    
    // Lazy loading
    'dev/js/session_storage_logging' => 0,
    'dev/js/session_storage_key' => 'mage-cache-timeout',
    
    // Merge e minify
    'dev/css/merge_css_files' => 1,
    'dev/css/minify_files' => 1,
    'dev/js/merge_files' => 1,
    'dev/js/minify_files' => 1,
    
    // Template minification
    'dev/template/minify_html' => 1
];

foreach ($performanceConfigs as $path => $value) {
    $configWriter->save($path, $value, 'default', 0);
    echo "✅ $path = $value\n";
}

echo "\n5️⃣ Criando página 404 customizada...\n";

// Criar página CMS 404 personalizada
$cmsPageData = [
    'title' => 'Página Não Encontrada - 404',
    'identifier' => 'no-route',
    'content' => '
<div class="page-404" style="text-align: center; padding: 60px 20px;">
    <div class="error-code" style="font-size: 120px; font-weight: bold; color: #b73337; margin-bottom: 20px;">404</div>
    <h1 style="color: #333; margin-bottom: 20px;">Oops! Página não encontrada</h1>
    <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
        A página que você está procurando pode ter sido removida, teve seu nome alterado ou está temporariamente indisponível.
    </p>
    
    <div style="margin: 40px 0;">
        <h3 style="color: #333; margin-bottom: 20px;">O que você pode fazer:</h3>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-bottom: 40px;">
            <a href="/" style="background: #b73337; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">🏠 Voltar ao Início</a>
            <a href="/catalog/category/view/id/3" style="background: #333; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">🛍️ Ver Produtos</a>
            <a href="/contact" style="background: #666; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">📞 Contato</a>
        </div>
    </div>

    <div style="margin: 40px 0;">
        <h3 style="color: #333; margin-bottom: 20px;">Procure por produtos:</h3>
        <form action="/catalogsearch/result/" method="get" style="max-width: 400px; margin: 0 auto; display: flex;">
            <input type="text" name="q" placeholder="Digite o que você procura..." 
                   style="flex: 1; padding: 12px; border: 1px solid #ddd; border-right: none; border-radius: 5px 0 0 5px;">
            <button type="submit" 
                    style="padding: 12px 20px; background: #b73337; color: white; border: none; border-radius: 0 5px 5px 0; cursor: pointer;">
                🔍 Buscar
            </button>
        </form>
    </div>

    <div style="margin: 40px 0;">
        <h3 style="color: #333; margin-bottom: 20px;">Produtos em Destaque:</h3>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 200px;">
                <h4 style="color: #b73337;">⛑️ Capacetes</h4>
                <p>Proteção e estilo para sua segurança</p>
                <a href="/capacetes" style="color: #b73337; text-decoration: none;">Ver Capacetes →</a>
            </div>
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 200px;">
                <h4 style="color: #b73337;">🧳 Baús</h4>
                <p>Praticidade para suas viagens</p>
                <a href="/baus" style="color: #b73337; text-decoration: none;">Ver Baús →</a>
            </div>
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; max-width: 200px;">
                <h4 style="color: #b73337;">🧤 Luvas</h4>
                <p>Conforto e proteção para suas mãos</p>
                <a href="/luvas" style="color: #b73337; text-decoration: none;">Ver Luvas →</a>
            </div>
        </div>
    </div>

    <div style="margin: 40px 0; padding: 20px; background: #f8f8f8; border-radius: 8px;">
        <h3 style="color: #333; margin-bottom: 15px;">💬 Precisa de ajuda?</h3>
        <p style="margin-bottom: 20px;">Nossa equipe está sempre pronta para ajudar!</p>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <a href="https://wa.me/5511999999999?text=Olá, preciso de ajuda!" 
               style="background: #25d366; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                📱 WhatsApp
            </a>
            <a href="mailto:contato@grupoawamotos.com.br" 
               style="background: #d73527; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                ✉️ Email
            </a>
        </div>
    </div>
</div>

<script>
// Registrar 404 no Google Analytics se disponível
if (typeof gtag !== "undefined") {
    gtag("event", "page_view", {
        page_title: "404 - Page Not Found",
        page_location: window.location.href
    });
}
</script>
',
    'meta_keywords' => '404, página não encontrada, erro, grupo awamotos',
    'meta_description' => 'Página não encontrada. Explore nossos produtos de motos: capacetes, baús, luvas e acessórios na Grupo Awamotos.',
    'is_active' => 1
];

// Verificar se página já existe
$resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resourceConnection->getConnection();

$pageExists = $connection->fetchOne(
    "SELECT page_id FROM cms_page WHERE identifier = ?",
    [$cmsPageData['identifier']]
);

if (!$pageExists) {
    $connection->insert('cms_page', [
        'title' => $cmsPageData['title'],
        'page_layout' => '1column',
        'identifier' => $cmsPageData['identifier'],
        'content_heading' => '',
        'content' => $cmsPageData['content'],
        'creation_time' => date('Y-m-d H:i:s'),
        'update_time' => date('Y-m-d H:i:s'),
        'is_active' => $cmsPageData['is_active'],
        'sort_order' => 0,
        'layout_update_xml' => '',
        'custom_theme' => '',
        'custom_root_template' => '',
        'custom_layout_update_xml' => '',
        'meta_keywords' => $cmsPageData['meta_keywords'],
        'meta_description' => $cmsPageData['meta_description']
    ]);
    
    $pageId = $connection->lastInsertId();
    
    // Associar com store
    $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
    $storeId = $storeManager->getStore()->getId();
    
    $connection->insert('cms_page_store', [
        'page_id' => $pageId,
        'store_id' => $storeId
    ]);
    
    echo "✅ Página 404 customizada criada (ID: $pageId)\n";
} else {
    echo "✅ Página 404 já existe\n";
}

echo "\n6️⃣ Limpando cache...\n";
$cacheManager->flush(['config', 'layout', 'block_html', 'full_page']);

echo "\n🎉 Otimização SEO On-Page concluída!\n\n";

echo "📊 Configurações aplicadas:\n";
echo "- URLs amigáveis (sem category path)\n";
echo "- Sufixos .html para SEO\n";  
echo "- Sitemap XML automático (diário)\n";
echo "- Meta tags otimizadas\n";
echo "- Página 404 customizada\n";
echo "- Flat catalog habilitado\n";
echo "- CSS/JS merge e minify\n";
echo "- HTML minification\n\n";

echo "📝 Próximos passos manuais:\n";
echo "1. Executar reindex: php bin/magento indexer:reindex\n";
echo "2. Deploy estático: php bin/magento setup:static-content:deploy\n";  
echo "3. Gerar sitemap: php bin/magento sitemap:generate\n";
echo "4. Configurar robots.txt no admin\n";
echo "5. Submeter sitemap.xml ao Google Search Console\n";