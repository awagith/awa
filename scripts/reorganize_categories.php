<?php
/**
 * Script de Reorganização Automática de Categorias
 * 
 * Este script reorganiza todos os produtos do catálogo, corrigindo:
 * - Produtos sem categoria
 * - Produtos em categorias erradas
 * - Hierarquia confusa de categorias
 * 
 * @author GitHub Copilot
 * @date 2025-11-19
 */

require __DIR__ . '/../app/bootstrap.php';

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\State;

class CategoryReorganizer
{
    private $csvInputFile = '_csv/catalog_product.csv';
    private $csvOutputFile = '_csv/catalog_product_reorganized.csv';
    private $csvBackupFile;
    private $statistics = [];
    private $categoryMapping = [];
    
    public function __construct()
    {
        $this->csvBackupFile = '_csv/catalog_product_backup_' . date('Y-m-d_H-i-s') . '.csv';
        $this->initializeStatistics();
        $this->initializeCategoryMapping();
    }
    
    private function initializeStatistics()
    {
        $this->statistics = [
            'total_products' => 0,
            'products_without_category' => 0,
            'products_moved' => 0,
            'products_corrected' => 0,
            'categories_created' => [],
            'errors' => []
        ];
    }
    
    /**
     * Mapeamento de SKU patterns para categorias
     */
    private function initializeCategoryMapping()
    {
        $this->categoryMapping = [
            // RETROVISORES - Principal categoria
            'retrovisores' => [
                'pattern' => '/^(50[0-9]|5[1-9][0-9]|6[0-2][0-9]|63[0-9]|64[0-9]|65[0-9]|66[0-9]|67[0-9]|68[0-9])/',
                'category' => 'Categorias/Retrovisores',
                'subcategory_rules' => [
                    '/ARROW/' => 'Categorias/Retrovisores/Arrow',
                    '/MINI/' => 'Categorias/Retrovisores/Mini',
                    '/ CR\.| CROMADO| HC/' => 'Categorias/Retrovisores/Cromados',
                    '/HASTE CROMADA/' => 'Categorias/Retrovisores/Haste Cromada',
                    '/ORIGINAL|MOD\. ORIG/' => 'Categorias/Retrovisores/Originais',
                    '/SPORT/' => 'Categorias/Retrovisores/Esportivos',
                ]
            ],
            
            // RETROVISORES - Códigos especiais
            'retrovisores_especiais' => [
                'pattern' => '/^(2220|2246|221[0-9]|222[0-9]|223[0-9]|224[0-9])/',
                'category' => 'Categorias/Retrovisores',
                'subcategory_rules' => [
                    '/ CR\.| CROMADO/' => 'Categorias/Retrovisores/Cromados',
                ]
            ],
            
            // BAULETOS
            'bauletos_29l' => [
                'pattern' => '/^290/',
                'category' => 'Categorias/Bauletos/Bauletos 29 L'
            ],
            'bauletos_34l' => [
                'pattern' => '/^340/',
                'category' => 'Categorias/Bauletos/Bauletos 34 L'
            ],
            'bauletos_41l' => [
                'pattern' => '/^410/',
                'category' => 'Categorias/Bauletos/Bauletos 41 L'
            ],
            'bauletos_acessorios' => [
                'pattern' => '/^(420|430|440)/',
                'category' => 'Categorias/Bauletos/Acessórios Para Bau'
            ],
            
            // BAGAGEIROS
            'bagageiros' => [
                'pattern' => '/^30[0-9][0-9]/',
                'category' => 'Categorias/Bagageiros',
                'subcategory_rules' => [
                    '/CROMADO/' => 'Categorias/Bagageiros/Cromados',
                    '/PRETO/' => 'Categorias/Bagageiros/Pretos',
                ]
            ],
            
            // PISCAS
            'piscas' => [
                'pattern' => '/^10[0-9][0-9]/',
                'category' => 'Categorias/Piscas'
            ],
            
            // LENTES
            'lentes' => [
                'pattern' => '/^40[0-9][0-9]/',
                'category' => 'Categorias/Lentes',
                'subcategory_rules' => [
                    '/TRASEIRA|FREIO/' => 'Categorias/Lentes/Lentes De Freio',
                    '/PISCA/' => 'Categorias/Lentes/Lente Dos Piscas',
                ]
            ],
            
            // GUIDÕES
            'guidoes' => [
                'pattern' => '/^23[0-9][0-9]/',
                'category' => 'Categorias/Guidões',
                'subcategory_rules' => [
                    '/HONDA/' => 'Categorias/Guidões/Linha Honda',
                    '/YAMAHA|YAM\./' => 'Categorias/Guidões/Linha Yamaha',
                ]
            ],
            
            // BARRAS DE GUIDÃO
            'barras_guidao' => [
                'pattern' => '/^113[7-9]/',
                'category' => 'Categorias/Guidões/Barras De Guidão'
            ],
            
            // CARCAÇAS
            'carcacas_superior' => [
                'pattern' => '/^20[2-4][0-9]/',
                'name_pattern' => '/SUPERIOR/',
                'category' => 'Categorias/Carcaças/Carcaça Painel Superior'
            ],
            'carcacas_inferior' => [
                'pattern' => '/^20[1-3][0-9]/',
                'name_pattern' => '/INFERIOR/',
                'category' => 'Categorias/Carcaças/Carcaça Painel Inferior'
            ],
            'carcacas_interna' => [
                'pattern' => '/^20[2-5][0-9]/',
                'name_pattern' => '/INTERNA/',
                'category' => 'Categorias/Carcaças/Carcaça Painel Interna'
            ],
            'carcacas_farol' => [
                'pattern' => '/^20[1-2][0-9]/',
                'name_pattern' => '/FAROL/',
                'category' => 'Categorias/Carcaças/Carcaça Do Farol'
            ],
            
            // BLOCOS ÓTICOS
            'blocos_oticos' => [
                'pattern' => '/^1[12][0-9][0-9]/',
                'name_pattern' => '/BLOCO|FAROL COMPLETO|LENTE DO BLOCO/',
                'category' => 'Categorias/Blocos Oticos'
            ],
            
            // BORRACHAS E PEDALEIRAS
            'borrachas' => [
                'pattern' => '/^[3-7][0-9]$/',
                'name_pattern' => '/BORRACHA|BUCHA/',
                'category' => 'Categorias/Borrachas'
            ],
            'pedaleiras' => [
                'pattern' => '/^[3-4][0-9]$/',
                'name_pattern' => '/PEDALEIRA/',
                'category' => 'Categorias/Pedaleiras'
            ],
            
            // MANOPLAS
            'manoplas' => [
                'pattern' => '/^(79|8[0-9]|9[0-9]|0079|0096|0097|0098)/',
                'name_pattern' => '/MANOPLA/',
                'category' => 'Categorias/Manoplas'
            ],
            
            // ROLDANAS
            'roldanas' => [
                'pattern' => '/^(99|100|102)$/',
                'category' => 'Categorias/Roldanas'
            ],
            
            // PROTETORES
            'protetor_carenagem' => [
                'pattern' => '/^303[0-9]/',
                'category' => 'Categorias/Protetor De Carenagem'
            ],
            'protetor_carter' => [
                'pattern' => '/^304[0-9]/',
                'category' => 'Categorias/Protetores De Carter',
                'subcategory_rules' => [
                    '/CROMADO/' => 'Categorias/Protetores De Carter/Cromado',
                    '/PRETO|FOSCO/' => 'Categorias/Protetores De Carter/Preto Fosco',
                ]
            ],
            
            // MANETES
            'manetes' => [
                'pattern' => '/^21[0-9][0-9]/',
                'name_pattern' => '/MANETE/',
                'category' => 'Categorias/Manetes'
            ],
            
            // CAVALETES
            'cavaletes' => [
                'pattern' => '/^31[0-9][0-9]/',
                'name_pattern' => '/CAVALETE/',
                'category' => 'Categorias/Cavaletes'
            ],
            
            // ADAPTADORES
            'adaptadores' => [
                'pattern' => '/^(1122|112[8-9]|113[0-1]|116[0-9])/',
                'name_pattern' => '/ADAPTADOR|GUIA/',
                'category' => 'Categorias/Adaptadores'
            ],
            
            // ANTENAS
            'antenas' => [
                'pattern' => '/^112[3-5]/',
                'category' => 'Categorias/Antenas Anti-Cerol'
            ],
            
            // SUPORTES DE PLACA
            'suporte_placa' => [
                'pattern' => '/^(62|70|75)$/',
                'name_pattern' => '/SUPORTE.*PLACA/',
                'category' => 'Categorias/Suportes/Suporte De Placa'
            ],
            
            // CAPAS DE CORRENTE
            'capa_corrente' => [
                'pattern' => '/^0047/',
                'category' => 'Categorias/Capas De Corrente'
            ],
            
            // ESTRIBOS (casos especiais)
            'estribos' => [
                'pattern' => '/NEVER_MATCH_PATTERN/',
                'name_pattern' => '/ESTRIBO/',
                'category' => 'Categorias/Estribos'
            ],
            
            // OUTROS
            'outros' => [
                'pattern' => '/^(1118)/',
                'name_pattern' => '/PALLA/',
                'category' => 'Categorias/Outros'
            ],
        ];
    }
    
    /**
     * Determina a categoria correta para um produto
     */
    private function determineCategoryForProduct($sku, $name, $currentCategory)
    {
        $sku = trim($sku);
        $name = strtoupper(trim($name));
        
        // Verificar cada mapeamento
        foreach ($this->categoryMapping as $mappingName => $rules) {
            // Verificar pattern do SKU
            if (isset($rules['pattern']) && preg_match($rules['pattern'], $sku)) {
                // Se houver pattern de nome, verificar também
                if (isset($rules['name_pattern'])) {
                    if (preg_match($rules['name_pattern'], $name)) {
                        return $this->applySubcategoryRules($name, $rules);
                    }
                    continue; // Pattern de SKU bateu mas nome não
                }
                
                // Apenas pattern de SKU
                return $this->applySubcategoryRules($name, $rules);
            }
            
            // Verificar apenas pattern de nome (para casos especiais)
            if (!isset($rules['pattern']) || $rules['pattern'] === '/NEVER_MATCH_PATTERN/') {
                if (isset($rules['name_pattern']) && preg_match($rules['name_pattern'], $name)) {
                    return $rules['category'];
                }
            }
        }
        
        // Se não encontrou categoria, retornar a atual ou "Categorias/Outros"
        if (empty($currentCategory)) {
            return 'Categorias/Outros';
        }
        
        // Limpar categorias duplicadas ou mal formatadas
        return $this->cleanCategory($currentCategory);
    }
    
    /**
     * Aplica regras de subcategoria baseadas no nome do produto
     */
    private function applySubcategoryRules($name, $rules)
    {
        if (isset($rules['subcategory_rules'])) {
            foreach ($rules['subcategory_rules'] as $pattern => $subcategory) {
                if (preg_match($pattern, $name)) {
                    return $subcategory;
                }
            }
        }
        
        return $rules['category'];
    }
    
    /**
     * Limpa categorias mal formatadas
     */
    private function cleanCategory($category)
    {
        // Remover "Categorias/Categorias/" duplicado
        $category = preg_replace('/Categorias\/Categorias\//', 'Categorias/', $category);
        
        // Remover espaços extras
        $category = trim($category);
        
        return $category;
    }
    
    /**
     * Processa o arquivo CSV
     */
    public function processCSV()
    {
        echo "\n╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║   SCRIPT DE REORGANIZAÇÃO DE CATEGORIAS - AWAMOTOS          ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
        
        // Criar backup
        echo "📁 Criando backup do arquivo original...\n";
        if (!copy($this->csvInputFile, $this->csvBackupFile)) {
            die("❌ ERRO: Não foi possível criar backup!\n");
        }
        echo "✅ Backup criado: {$this->csvBackupFile}\n\n";
        
        // Abrir arquivos
        $inputHandle = fopen($this->csvInputFile, 'r');
        $outputHandle = fopen($this->csvOutputFile, 'w');
        
        if (!$inputHandle || !$outputHandle) {
            die("❌ ERRO: Não foi possível abrir os arquivos CSV!\n");
        }
        
        echo "📊 Processando produtos...\n\n";
        
        // Ler header
        $header = fgetcsv($inputHandle);
        fputcsv($outputHandle, $header);
        
        // Encontrar índices das colunas
        $skuIndex = array_search('sku', $header);
        $nameIndex = array_search('name', $header);
        $categoryIndex = array_search('categories', $header);
        
        if ($skuIndex === false || $nameIndex === false || $categoryIndex === false) {
            die("❌ ERRO: Colunas obrigatórias não encontradas no CSV!\n");
        }
        
        $lineNumber = 1;
        
        // Processar cada linha
        while (($row = fgetcsv($inputHandle)) !== false) {
            $lineNumber++;
            $this->statistics['total_products']++;
            
            $sku = $row[$skuIndex] ?? '';
            $name = $row[$nameIndex] ?? '';
            $currentCategory = $row[$categoryIndex] ?? '';
            
            // Determinar nova categoria
            $newCategory = $this->determineCategoryForProduct($sku, $name, $currentCategory);
            
            // Estatísticas
            if (empty($currentCategory)) {
                $this->statistics['products_without_category']++;
            }
            
            if ($newCategory !== $currentCategory) {
                $this->statistics['products_moved']++;
                
                if (!empty($currentCategory) && $currentCategory !== $newCategory) {
                    $this->statistics['products_corrected']++;
                }
                
                // Log de mudanças importantes (amostra)
                if ($this->statistics['products_moved'] <= 20) {
                    echo sprintf(
                        "  ↪ SKU: %-15s | %-40s\n    DE: %-50s\n    PARA: %s\n\n",
                        $sku,
                        substr($name, 0, 40),
                        substr($currentCategory ?: '[SEM CATEGORIA]', 0, 50),
                        $newCategory
                    );
                }
            }
            
            // Atualizar categoria na linha
            $row[$categoryIndex] = $newCategory;
            
            // Adicionar à lista de categorias criadas (únicas)
            if (!in_array($newCategory, $this->statistics['categories_created'])) {
                $this->statistics['categories_created'][] = $newCategory;
            }
            
            // Escrever linha atualizada
            fputcsv($outputHandle, $row);
        }
        
        fclose($inputHandle);
        fclose($outputHandle);
        
        echo "\n" . str_repeat("─", 65) . "\n";
        echo "✅ Processamento concluído!\n\n";
        
        $this->displayStatistics();
    }
    
    /**
     * Exibe estatísticas do processamento
     */
    private function displayStatistics()
    {
        echo "╔═══════════════════════════════════════════════════════════════╗\n";
        echo "║                     ESTATÍSTICAS FINAIS                       ║\n";
        echo "╚═══════════════════════════════════════════════════════════════╝\n\n";
        
        echo sprintf("📦 Total de produtos processados:     %d\n", $this->statistics['total_products']);
        echo sprintf("❌ Produtos sem categoria original:   %d (%.1f%%)\n", 
            $this->statistics['products_without_category'],
            ($this->statistics['products_without_category'] / $this->statistics['total_products']) * 100
        );
        echo sprintf("🔄 Produtos movidos de categoria:     %d\n", $this->statistics['products_moved']);
        echo sprintf("✅ Produtos corrigidos:                %d\n", $this->statistics['products_corrected']);
        echo sprintf("📁 Categorias únicas identificadas:   %d\n\n", count($this->statistics['categories_created']));
        
        echo "📋 CATEGORIAS IDENTIFICADAS:\n";
        echo str_repeat("─", 65) . "\n";
        sort($this->statistics['categories_created']);
        foreach ($this->statistics['categories_created'] as $index => $category) {
            echo sprintf("%3d. %s\n", $index + 1, $category);
        }
        
        echo "\n" . str_repeat("═", 65) . "\n";
        echo "📄 ARQUIVOS GERADOS:\n";
        echo str_repeat("─", 65) . "\n";
        echo "✅ Backup:  {$this->csvBackupFile}\n";
        echo "✅ Arquivo reorganizado: {$this->csvOutputFile}\n\n";
        
        echo "🚀 PRÓXIMOS PASSOS:\n";
        echo str_repeat("─", 65) . "\n";
        echo "1. Revisar o arquivo: {$this->csvOutputFile}\n";
        echo "2. Criar categorias faltantes no Magento Admin\n";
        echo "3. Importar o CSV reorganizado\n";
        echo "4. Reindexar catálogo: php bin/magento indexer:reindex\n";
        echo "5. Limpar cache: php bin/magento cache:flush\n\n";
    }
}

// Executar script
try {
    $reorganizer = new CategoryReorganizer();
    $reorganizer->processCSV();
    
    echo "✅ Script executado com sucesso!\n\n";
    exit(0);
    
} catch (Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}
