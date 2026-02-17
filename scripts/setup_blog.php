<?php
/**
 * Script para criar artigos de blog SEO-otimizados
 * Rokanthemes Blog
 */

require_once 'app/bootstrap.php';
$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

$resourceConnection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resourceConnection->getConnection();

$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
$storeId = $storeManager->getStore()->getId();

echo "🚀 Criando artigos de blog SEO-otimizados...\n\n";

// Artigos para criar
$articles = [
    [
        'title' => 'Guia Completo: Como Escolher o Capacete Ideal para sua Moto',
        'identifier' => 'guia-capacete-ideal-moto',
        'meta_keywords' => 'capacete moto, capacete ideal, tipos capacete, segurança motociclista',
        'meta_description' => 'Aprenda como escolher o capacete perfeito para sua moto. Guia completo com tipos, tamanhos, certificações e dicas de segurança.',
        'short_content' => 'Escolher o capacete certo é fundamental para a segurança do motociclista. Nosso guia completo ensina tudo sobre tipos, tamanhos e certificações.',
        'content' => '
<h2>Por que o Capacete é o Item Mais Importante?</h2>
<p>O capacete é, sem dúvida, o equipamento de proteção individual (EPI) mais importante para qualquer motociclista. Segundo dados do DPVAT, o uso correto do capacete pode reduzir em até 85% o risco de lesões graves na cabeça em caso de acidentes.</p>

<h2>Tipos de Capacetes: Qual Escolher?</h2>

<h3>1. Capacete Aberto (3/4)</h3>
<p>Ideal para uso urbano, oferece maior ventilação e campo de visão. Protege a parte superior e lateral da cabeça, mas deixa o queixo descoberto.</p>
<ul>
<li><strong>Vantagens:</strong> Ventilação, comunicação mais fácil</li>
<li><strong>Desvantagens:</strong> Menor proteção facial</li>
<li><strong>Recomendado para:</strong> Trajetos urbanos, baixa velocidade</li>
</ul>

<h3>2. Capacete Fechado (Integral)</h3>
<p>Oferece proteção completa da cabeça e face. É o tipo mais seguro e recomendado para viagens e alta velocidade.</p>
<ul>
<li><strong>Vantagens:</strong> Proteção máxima, aerodinâmica</li>
<li><strong>Desvantagens:</strong> Pode ser mais quente</li>
<li><strong>Recomendado para:</strong> Viagens, esportivas, alta velocidade</li>
</ul>

<h3>3. Capacete Modular</h3>
<p>Combina características dos capacetes aberto e fechado, com queixeira articulada.</p>
<ul>
<li><strong>Vantagens:</strong> Versatilidade, facilita comunicação</li>
<li><strong>Desvantagens:</strong> Peso maior, ponto de articulação</li>
<li><strong>Recomendado para:</strong> Touring, uso misto</li>
</ul>

<h2>Como Medir o Tamanho Correto</h2>
<p>Para garantir a proteção adequada, siga estes passos:</p>
<ol>
<li>Use uma fita métrica flexível</li>
<li>Meça a circunferência da cabeça na altura da testa</li>
<li>Passe a fita logo acima das sobrancelhas e orelhas</li>
<li>Compare com a tabela do fabricante</li>
<li>Em caso de dúvida entre dois tamanhos, escolha o menor</li>
</ol>

<h2>Certificações de Segurança</h2>
<p>No Brasil, procure sempre pelo selo do INMETRO. Internacionalmente, as principais certificações são:</p>
<ul>
<li><strong>DOT:</strong> Estados Unidos</li>
<li><strong>ECE 22.05/22.06:</strong> Europa (mais rigorosa)</li>
<li><strong>SNELL:</strong> Padrão mais exigente</li>
</ul>

<h2>Principais Marcas Recomendadas</h2>
<p>Na Grupo Awamotos, trabalhamos com as melhores marcas do mercado:</p>
<ul>
<li><strong>Shark:</strong> Tecnologia francesa, excelente custo-benefício</li>
<li><strong>Helt:</strong> Nacional, ótima qualidade</li>
<li><strong>X11:</strong> Brasileira, variedade de modelos</li>
<li><strong>AGV:</strong> Italiana, alta performance</li>
</ul>

<h2>Dicas de Manutenção</h2>
<ul>
<li>Lave apenas com água e sabão neutro</li>
<li>Nunca use produtos químicos agressivos</li>
<li>Substitua a viseira quando arranhada</li>
<li>Troque o capacete após qualquer impacto forte</li>
<li>Vida útil média: 5 anos</li>
</ul>

<p><strong>Conclusão:</strong> Investir em um bom capacete é investir na sua segurança. Na Grupo Awamotos, temos uma linha completa de capacetes de todas as marcas e tipos. Visite nossa loja ou entre em contato via WhatsApp para receber orientação personalizada!</p>
        ',
        'featured_image' => 'blog/capacete-guia-completo.jpg',
        'tags' => 'capacete,segurança,motociclista,equipamento'
    ],
    [
        'title' => 'Top 10 Acessórios Essenciais para Motociclistas em 2025',
        'identifier' => 'top-10-acessorios-motociclistas-2025',
        'meta_keywords' => 'acessórios moto, equipamentos motociclista, segurança moto, proteção motociclista',
        'meta_description' => 'Descubra os 10 acessórios mais importantes para motociclistas em 2025. Segurança, conforto e praticidade em um só lugar.',
        'short_content' => 'Conheça os acessórios indispensáveis para todo motociclista moderno. Lista completa com itens de segurança, conforto e praticidade.',
        'content' => '
<h2>Introdução</h2>
<p>Andar de moto vai muito além de ter uma boa máquina. Os acessórios certos fazem toda a diferença na segurança, conforto e praticidade do seu dia a dia sobre duas rodas. Confira nossa lista dos 10 itens essenciais para 2025!</p>

<h2>1. Capacete de Qualidade</h2>
<p>Já falamos sobre isso no nosso <a href="/blog/guia-capacete-ideal-moto">guia completo de capacetes</a>, mas não custa reforçar: um bom capacete é inegociável.</p>
<ul>
<li><strong>Investimento:</strong> R$ 300 - R$ 2.000</li>
<li><strong>Marcas recomendadas:</strong> Shark, Helt, X11, AGV</li>
</ul>

<h2>2. Luvas de Proteção</h2>
<p>Protegem suas mãos de quedas, vento e temperatura. Procure por luvas com proteção nos nós dos dedos.</p>
<ul>
<li><strong>Tipos:</strong> Urbanas, esportivas, touring</li>
<li><strong>Material:</strong> Couro, sintético, textil</li>
<li><strong>Preço médio:</strong> R$ 80 - R$ 500</li>
</ul>

<h2>3. Jaqueta com Proteção</h2>
<p>Fundamental para proteção do tronco. Modelos com proteções removíveis são mais versáteis.</p>
<ul>
<li><strong>Características:</strong> Proteções CE, ventilação, impermeabilidade</li>
<li><strong>Marcas:</strong> Texx, Tutto, Alpinestars</li>
</ul>

<h2>4. Baú para Moto</h2>
<p>Essencial para quem usa a moto no dia a dia. Aumenta muito a praticidade de transporte.</p>
<ul>
<li><strong>Capacidades:</strong> 30L a 60L</li>
<li><strong>Marcas líderes:</strong> GIVI, Shad, SW-Motech</li>
<li><strong>Benefícios:</strong> Segurança, organização, proteção contra chuva</li>
</ul>

<h2>5. Intercomunicador Bluetooth</h2>
<p>Revolução na comunicação entre motociclistas e para navegação GPS.</p>
<ul>
<li><strong>Funções:</strong> Música, GPS, telefone, comunicação</li>
<li><strong>Marcas:</strong> Sena, Cardo, Fodsports</li>
<li><strong>Autonomia:</strong> 8-20 horas</li>
</ul>

<h2>6. Suporte de Celular</h2>
<p>Indispensável para navegação GPS. Escolha modelos com proteção contra chuva.</p>
<ul>
<li><strong>Tipos:</strong> Guidão, tanque, braço</li>
<li><strong>Proteção:</strong> IP65 contra água</li>
</ul>

<h2>7. Protetor de Motor</h2>
<p>Protege o motor em caso de quedas, especialmente importante para motos naked e esportivas.</p>
<ul>
<li><strong>Material:</strong> Aço, alumínio</li>
<li><strong>Benefício:</strong> Evita danos caros no motor</li>
</ul>

<h2>8. Kit de Ferramentas</h2>
<p>Para pequenos reparos e ajustes durante viagens.</p>
<ul>
<li><strong>Itens básicos:</strong> Chaves Allen, Phillips, fenda</li>
<li><strong>Extras:</strong> Alicate, chave inglesa, fita isolante</li>
</ul>

<h2>9. Cabo de Aço ou Corrente</h2>
<p>Segurança adicional contra furtos. Use sempre junto com o alarme original.</p>
<ul>
<li><strong>Tipos:</strong> Cabo de aço, corrente, cadeado de disco</li>
<li><strong>Dica:</strong> Prenda sempre em ponto fixo</li>
</ul>

<h2>10. Capa de Proteção</h2>
<p>Protege a moto contra chuva, sol e poeira quando estacionada.</p>
<ul>
<li><strong>Material:</strong> Impermeável com proteção UV</li>
<li><strong>Característica:</strong> Elástico para fixação segura</li>
</ul>

<h2>Bônus: Acessórios Tecnológicos</h2>
<ul>
<li><strong>Carregador USB:</strong> Para manter dispositivos carregados</li>
<li><strong>Câmera de ação:</strong> Registrar viagens e segurança no trânsito</li>
<li><strong>Sensor de pressão dos pneus:</strong> Monitoramento em tempo real</li>
</ul>

<p><strong>Investimento Total:</strong> De R$ 1.500 a R$ 8.000 dependendo das marcas e modelos escolhidos.</p>

<p><strong>Na Grupo Awamotos</strong> você encontra todos esses acessórios das melhores marcas, com garantia e suporte técnico. Consulte nossas promoções e monte seu kit completo!</p>
        ',
        'featured_image' => 'blog/acessorios-moto-2025.jpg',
        'tags' => 'acessórios,equipamentos,segurança,motociclista,2025'
    ],
    [
        'title' => 'Manutenção de Moto: Checklist Completo para Iniciantes',
        'identifier' => 'manutencao-moto-checklist-iniciantes',
        'meta_keywords' => 'manutenção moto, revisão moto, cuidados moto, manutenção preventiva',
        'meta_description' => 'Guia completo de manutenção para motociclistas iniciantes. Checklist detalhado para manter sua moto sempre em perfeito estado.',
        'short_content' => 'Aprenda a cuidar da sua moto com nosso checklist completo de manutenção preventiva. Dicas essenciais para iniciantes.',
        'content' => '
<h2>Por que a Manutenção Preventiva é Importante?</h2>
<p>Uma moto bem cuidada é sinônimo de segurança, economia e durabilidade. A manutenção preventiva pode evitar 90% dos problemas mecânicos e aumentar significativamente a vida útil do seu veículo.</p>

<h2>Checklist Diário (Antes de Sair)</h2>
<h3>✓ Pneus</h3>
<ul>
<li>Verifique a pressão (use um calibrador)</li>
<li>Observe desgastes irregulares</li>
<li>Verifique objetos cravados</li>
<li><strong>Pressão ideal:</strong> Consulte manual da moto</li>
</ul>

<h3>✓ Freios</h3>
<ul>
<li>Teste a eficiência antes de sair</li>
<li>Verifique nível do fluido (se hidráulico)</li>
<li>Observe pastilhas (mínimo 2mm)</li>
</ul>

<h3>✓ Óleo do Motor</h3>
<ul>
<li>Verifique nível na vareta (moto em pé, motor frio)</li>
<li>Observe a cor (escuro = trocar)</li>
<li>Procure vazamentos no chão</li>
</ul>

<h3>✓ Combustível</h3>
<ul>
<li>Sempre abasteça com combustível de qualidade</li>
<li>Evite reserva (prejudica bomba de combustível)</li>
</ul>

<h2>Checklist Semanal</h2>
<h3>🔧 Corrente</h3>
<ul>
<li>Lubrificar a cada 500km ou semanalmente</li>
<li>Verificar tensão (2-3cm de folga)</li>
<li>Limpar antes de lubrificar</li>
</ul>

<h3>🔧 Bateria</h3>
<ul>
<li>Verificar terminais (limpar se oxidados)</li>
<li>Testar voltagem (12.6V moto desligada)</li>
<li>Verificar nível de água (baterias convencionais)</li>
</ul>

<h3>🔧 Luzes e Sinalização</h3>
<ul>
<li>Testar farol alto/baixo</li>
<li>Verificar pisca-pisca</li>
<li>Testar luz de freio</li>
<li>Conferir luz da placa</li>
</ul>

<h2>Checklist Mensal</h2>
<h3>🛠️ Limpeza Geral</h3>
<ul>
<li>Lavar com água e detergente neutro</li>
<li>Secar completamente</li>
<li>Aplicar cera protetora</li>
<li>Lubrificar partes móveis</li>
</ul>

<h3>🛠️ Filtros</h3>
<ul>
<li>Verificar filtro de ar (limpar se necessário)</li>
<li>Observar filtro de combustível</li>
</ul>

<h3>🛠️ Suspensão</h3>
<ul>
<li>Testar amortecedores (balançar a moto)</li>
<li>Verificar vazamentos de óleo</li>
<li>Ajustar regulagens conforme peso/uso</li>
</ul>

<h2>Revisões Periódicas</h2>

<h3>A Cada 1.000km ou 1 Mês</h3>
<ul>
<li>Verificar todas as luzes</li>
<li>Ajustar corrente</li>
<li>Verificar nível de fluidos</li>
<li>Calibrar pneus</li>
</ul>

<h3>A Cada 3.000km ou 3 Meses</h3>
<ul>
<li>Trocar óleo do motor</li>
<li>Trocar filtro de óleo</li>
<li>Verificar pastilhas de freio</li>
<li>Limpar filtro de ar</li>
</ul>

<h3>A Cada 6.000km ou 6 Meses</h3>
<ul>
<li>Verificar velas de ignição</li>
<li>Trocar fluido de freio</li>
<li>Verificar rolamentos de direção</li>
<li>Revisar sistema elétrico completo</li>
</ul>

<h3>A Cada 10.000km ou 1 Ano</h3>
<ul>
<li>Trocar filtro de ar</li>
<li>Verificar válvulas (motos 4T)</li>
<li>Trocar corrente e coroas</li>
<li>Revisão geral na concessionária</li>
</ul>

<h2>Sinais de Alerta</h2>
<h3>🚨 Pare Imediatamente Se:</h3>
<ul>
<li>Luz vermelha acender no painel</li>
<li>Ruído estranho no motor</li>
<li>Freios "esponjosos" ou sem resposta</li>
<li>Vazamento visível de óleo</li>
<li>Superaquecimento do motor</li>
</ul>

<h2>Kit de Ferramentas Básico</h2>
<ul>
<li>Chaves Phillips e fenda (várias medidas)</li>
<li>Chaves Allen (4, 5, 6, 8mm)</li>
<li>Chave inglesa pequena</li>
<li>Alicate comum</li>
<li>Calibrador de pneus</li>
<li>Óleo para corrente</li>
<li>Pano limpo</li>
</ul>

<h2>Produtos de Manutenção Essenciais</h2>
<ul>
<li><strong>Óleo de motor:</strong> Conforme especificação do manual</li>
<li><strong>Lubrificante de corrente:</strong> Específico para motos</li>
<li><strong>Limpa contatos:</strong> Para sistema elétrico</li>
<li><strong>Cera protetora:</strong> Protege pintura e plásticos</li>
<li><strong>Detergente neutro:</strong> Para lavagem</li>
</ul>

<h2>Economia com Manutenção Preventiva</h2>
<p>Investir em manutenção preventiva pode economizar até 70% em reparos. Um motor bem cuidado dura facilmente o dobro do tempo e mantém melhor valor de revenda.</p>

<p><strong>Custo médio mensal de manutenção:</strong> R$ 80 - R$ 150 (muito menos que um reparo maior!)</p>

<p><strong>Dica Final:</strong> Mantenha sempre um caderno de manutenção anotando datas, quilometragem e serviços realizados. Isso ajuda a acompanhar a evolução da moto e facilita a revenda.</p>

<p><strong>Na Grupo Awamotos</strong> você encontra todos os produtos e acessórios para manutenção da sua moto. Temos também parcerias com oficinas especializadas para serviços mais complexos!</p>
        ',
        'featured_image' => 'blog/manutencao-moto-checklist.jpg',
        'tags' => 'manutenção,revisão,cuidados,preventiva,iniciantes'
    ]
];

// Criar categoria de blog se não existir
$categoryData = [
    'title' => 'Guias e Dicas',
    'identifier' => 'guias-dicas',
    'meta_keywords' => 'guias,dicas,motociclismo,segurança',
    'meta_description' => 'Guias completos e dicas essenciais para motociclistas',
    'content' => 'Categoria com guias completos, dicas de segurança e informações essenciais para motociclistas de todos os níveis.',
    'is_active' => 1,
    'position' => 1
];

// Verificar se categoria já existe
$categoryExists = $connection->fetchOne(
    "SELECT category_id FROM rokanthemes_blog_category WHERE identifier = ?",
    [$categoryData['identifier']]
);

if (!$categoryExists) {
    $connection->insert('rokanthemes_blog_category', [
        'title' => $categoryData['title'],
        'identifier' => $categoryData['identifier'],
        'meta_keywords' => $categoryData['meta_keywords'],
        'meta_description' => $categoryData['meta_description'],
        'content' => $categoryData['content'],
        'is_active' => $categoryData['is_active'],
        'position' => $categoryData['position'],
        'path' => $categoryData['identifier']
    ]);
    $categoryId = $connection->lastInsertId();
    
    // Associar categoria com store
    $connection->insert('rokanthemes_blog_category_store', [
        'category_id' => $categoryId,
        'store_id' => $storeId
    ]);
    
    echo "✅ Categoria 'Guias e Dicas' criada com ID: $categoryId\n";
} else {
    $categoryId = $categoryExists;
    echo "✅ Categoria já existe com ID: $categoryId\n";
}

// Criar artigos
foreach ($articles as $index => $article) {
    // Verificar se artigo já existe
    $exists = $connection->fetchOne(
        "SELECT post_id FROM rokanthemes_blog_post WHERE identifier = ?",
        [$article['identifier']]
    );
    
    if ($exists) {
        echo "⚠️  Artigo '{$article['title']}' já existe (ID: $exists)\n";
        continue;
    }
    
    // Inserir artigo
    $postData = [
        'title' => $article['title'],
        'identifier' => $article['identifier'],
        'meta_keywords' => $article['meta_keywords'],
        'meta_description' => $article['meta_description'],
        'short_content' => $article['short_content'],
        'content' => $article['content'],
        'thumbnailimage' => $article['featured_image'],
        'is_active' => 1,
        'creation_time' => date('Y-m-d H:i:s', strtotime("-" . (count($articles) - $index) . " days")),
        'update_time' => date('Y-m-d H:i:s'),
        'publish_time' => date('Y-m-d H:i:s', strtotime("-" . (count($articles) - $index) . " days"))
    ];
    
    $connection->insert('rokanthemes_blog_post', $postData);
    $postId = $connection->lastInsertId();
    
    // Associar post com store
    $connection->insert('rokanthemes_blog_post_store', [
        'post_id' => $postId,
        'store_id' => $storeId
    ]);
    
    // Associar post com categoria
    $connection->insert('rokanthemes_blog_post_category', [
        'post_id' => $postId,
        'category_id' => $categoryId
    ]);
    
    echo "✅ Artigo '{$article['title']}' criado com ID: $postId\n";
}

echo "\n🎉 Blog configurado com sucesso!\n";
echo "📝 Total de artigos criados: " . count($articles) . "\n";
echo "🔗 Acesse: /blog para ver os artigos\n\n";

echo "📊 Próximos passos:\n";
echo "1. Configurar URLs amigáveis no admin\n";
echo "2. Adicionar imagens aos artigos\n";
echo "3. Configurar sitemap.xml para incluir blog\n";
echo "4. Criar mais artigos focados em long-tail keywords\n";