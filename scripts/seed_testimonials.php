<?php
/**
 * Script para popular depoimentos de clientes (Testimonials)
 * 
 * Uso: php scripts/seed_testimonials.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
try {
    $state->setAreaCode('adminhtml');
} catch (\Exception $e) {
    // Area já definida
}

$testimonialFactory = $objectManager->get(\Rokanthemes\Testimonials\Model\TestimonialsFactory::class);

// Definir depoimentos realistas
$testimonials = [
    [
        'name' => 'João Silva',
        'email' => 'joao.silva@email.com',
        'company' => 'São Paulo, SP',
        'content' => 'Comprei um baú para minha CB 500X e chegou super rápido. Produto original, instalação fácil e o atendimento pelo WhatsApp foi excelente. Recomendo de olhos fechados!',
        'image' => 'testimonials/avatar1.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Maria Santos',
        'email' => 'maria.santos@email.com',
        'company' => 'Rio de Janeiro, RJ',
        'content' => 'Capacete Shark chegou antes do prazo, muito bem embalado. Qualidade impecável e preço justo. Já comprei outras vezes e sempre tive ótima experiência.',
        'image' => 'testimonials/avatar2.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Carlos Rodrigues',
        'email' => 'carlos.rod@email.com',
        'company' => 'Belo Horizonte, MG',
        'content' => 'Luvas X11 de ótima qualidade. Pesquisei muito antes de comprar e aqui encontrei o melhor preço. Entrega rápida e produto exatamente como descrito.',
        'image' => 'testimonials/avatar3.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Ana Paula Costa',
        'email' => 'ana.costa@email.com',
        'company' => 'Porto Alegre, RS',
        'content' => 'Primeira compra online de equipamentos de moto e foi perfeita! Jaqueta impermeável chegou certinha, proteções todas no lugar. Super satisfeita!',
        'image' => 'testimonials/avatar4.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Roberto Mendes',
        'email' => 'roberto.mendes@email.com',
        'company' => 'Curitiba, PR',
        'content' => 'Escapamento esportivo para minha MT-03. Som perfeito, instalação sem problemas. Equipe muito atenciosa tirando todas as dúvidas antes da compra.',
        'image' => 'testimonials/avatar5.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Juliana Ferreira',
        'email' => 'juliana.f@email.com',
        'company' => 'Brasília, DF',
        'content' => 'Comprei capacete e luvas. Produtos de primeira linha, embalagem impecável. Chegou em 3 dias úteis. Virei cliente fiel!',
        'image' => 'testimonials/avatar6.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Fernando Lima',
        'email' => 'fernando.lima@email.com',
        'company' => 'Salvador, BA',
        'content' => 'Baú GIVI original com preço imbatível. Pesquisei em várias lojas e aqui foi o melhor custo-benefício. Entrega rápida para o Nordeste.',
        'image' => 'testimonials/avatar7.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Patrícia Alves',
        'email' => 'patricia.alves@email.com',
        'company' => 'Fortaleza, CE',
        'content' => 'Intercomunicador Sena funcionando perfeitamente. Compra super tranquila, site claro, pagamento fácil. Parabéns pela seriedade!',
        'image' => 'testimonials/avatar8.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Marcelo Oliveira',
        'email' => 'marcelo.oli@email.com',
        'company' => 'Recife, PE',
        'content' => 'Já fiz três pedidos e todos chegaram antes do prazo. Produtos sempre originais e muito bem embalados. Melhor loja de acessórios do Brasil!',
        'image' => 'testimonials/avatar9.jpg',
        'rating' => 5,
        'status' => 1
    ],
    [
        'name' => 'Camila Torres',
        'email' => 'camila.torres@email.com',
        'company' => 'Campinas, SP',
        'content' => 'Jaqueta feminina Alpinestars perfeita! Tamanho certinho, material de qualidade. Atendimento nota 10 pelo chat. Super recomendo!',
        'image' => 'testimonials/avatar10.jpg',
        'rating' => 5,
        'status' => 1
    ]
];

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║  SEED DE DEPOIMENTOS - ROKANTHEMES TESTIMONIALS         ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

// Verificar se já existem testimonials
$collection = $objectManager->create(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\Collection::class);
$existingCount = $collection->getSize();

if ($existingCount > 0) {
    echo "⚠️  Atenção: Já existem {$existingCount} depoimentos cadastrados.\n";
    echo "   Deseja continuar e adicionar mais? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 's') {
        echo "❌ Operação cancelada.\n\n";
        exit(0);
    }
    fclose($handle);
    echo "\n";
}

$successCount = 0;
$errorCount = 0;

foreach ($testimonials as $index => $data) {
    try {
        $testimonial = $testimonialFactory->create();
        
        // Verificar se já existe pelo email
        $existing = $objectManager->create(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\Collection::class);
        $existing->addFieldToFilter('email', $data['email']);
        
        if ($existing->getSize() > 0) {
            echo sprintf("⏭️  Depoimento %d: %s (já existe, pulando)\n", $index + 1, $data['name']);
            continue;
        }
        
        $testimonial->setData([
            'name' => $data['name'],
            'email' => $data['email'],
            'company' => $data['company'],
            'testimonial' => $data['content'],
            'avatar' => $data['image'],
            'rating' => $data['rating'],
            'is_active' => 1, // Ativo (1 = aprovado)
            'position' => $index + 1,
            'stores' => [0] // Associar a todas as stores (0 = All Store Views)
        ]);
        
        $testimonial->save();
        $successCount++;
        
        echo sprintf("✅ Depoimento %d: %s\n", $index + 1, $data['name']);
        
    } catch (\Exception $e) {
        $errorCount++;
        echo sprintf("❌ Erro ao criar depoimento %d (%s): %s\n", $index + 1, $data['name'], $e->getMessage());
    }
}

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo sprintf("║  ✅ Criados: %2d depoimentos                           ║\n", $successCount);
echo sprintf("║  ❌ Erros: %2d                                          ║\n", $errorCount);
echo sprintf("║  📊 Total no banco: %2d                                 ║\n", $existingCount + $successCount);
echo "╚══════════════════════════════════════════════════════════╝\n\n";

if ($successCount > 0) {
    echo "🎯 Próximos passos:\n";
    echo "   1. Limpar cache: php bin/magento cache:flush\n";
    echo "   2. Configurar widget na homepage (StoreConfigurator)\n";
    echo "   3. Fazer deploy: php bin/magento setup:static-content:deploy pt_BR -f\n\n";
}

echo "✨ Script finalizado!\n\n";
