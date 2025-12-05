<?php
require_once 'app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);

$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
$blockFactory = $objectManager->get(\Magento\Cms\Model\BlockFactory::class);
$blockRepository = $objectManager->get(\Magento\Cms\Api\BlockRepositoryInterface::class);

// Schema.org content
$schemaContent = '
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Grupo Awamotos",
  "url": "https://srv1113343.hstgr.cloud/",
  "logo": "https://srv1113343.hstgr.cloud/media/logo/default/logo.png",
  "description": "Especialistas em peças e acessórios para motocicletas. Capacetes, baús, luvas, escapamentos e muito mais.",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+55-11-99999-9999",
    "contactType": "Customer Service",
    "areaServed": "BR"
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "São Paulo",
    "addressRegion": "SP", 
    "addressCountry": "BR"
  },
  "sameAs": [
    "https://facebook.com/grupoawamotos",
    "https://instagram.com/grupoawamotos"
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Grupo Awamotos",
  "url": "https://srv1113343.hstgr.cloud/",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://srv1113343.hstgr.cloud/catalogsearch/result/?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>';

try {
    // Tentar buscar bloco existente
    try {
        $block = $blockRepository->getById('schema_org_homepage');
        echo "Atualizando bloco existente...\n";
    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
        // Criar novo bloco
        echo "Criando novo bloco Schema.org...\n";
        $block = $blockFactory->create();
        $block->setIdentifier('schema_org_homepage');
    }

    $block->setTitle('Schema.org Homepage');
    $block->setContent($schemaContent);
    $block->setIsActive(true);
    $block->setStoreId([0]); // All store views

    $blockRepository->save($block);
    echo "✅ CMS Block 'schema_org_homepage' criado com sucesso!\n";
    echo "📝 Para usar: {{block id='schema_org_homepage'}}\n";

} catch (\Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
