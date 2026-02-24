<?php

declare(strict_types=1);

namespace GrupoAwamotos\StoreSetup\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Popula conteúdo inicial para módulos Rokanthemes que dependem de registros no BD:
 *
 * 1. Slider Homepage (rokanthemes_slidebanner) — slider + 3 slides placeholder
 * 2. Testimonials (rokanthemes_testimonials) — 6 depoimentos seed
 * 3. FAQ (rokanthemes_faq) — 20 perguntas frequentes sobre peças para motos
 *
 * Os dados são inseridos diretamente via SQL para evitar dependência da
 * compilação de DI dos módulos Rokanthemes (que pode não estar disponível
 * durante setup:upgrade).
 *
 * @see docs/AUDITORIA_TEMA_AYO.md — seções 7, 10, 23
 */
class AyoSeedContent implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly LoggerInterface $logger
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();

        $this->seedSlider($connection);
        $this->seedTestimonials($connection);
        $this->seedFaq($connection);

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [
            AyoContentSetup::class,
        ];
    }

    public function getAliases(): array
    {
        return [];
    }

    // ========================================================================
    // SLIDER HOMEPAGE
    // ========================================================================

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function seedSlider($connection): void
    {
        $sliderTable = $this->moduleDataSetup->getTable('rokanthemes_slidebanner_slider');
        $slideTable = $this->moduleDataSetup->getTable('rokanthemes_slidebanner_slide');

        // Verificar se as tabelas existem
        if (!$connection->isTableExists($sliderTable) || !$connection->isTableExists($slideTable)) {
            $this->logger->warning('[AyoSeedContent] Tabelas do SlideBanner não encontradas — pulando slider seed.');
            return;
        }

        // Verificar se slider já existe
        $existingSlider = $connection->fetchOne(
            $connection->select()
                ->from($sliderTable, ['slider_id'])
                ->where('identifier = ?', 'homepageslider')
        );

        if ($existingSlider) {
            $this->logger->info('[AyoSeedContent] Slider "homepageslider" já existe — pulando.');
            return;
        }

        try {
            // Criar o slider principal
            $connection->insert($sliderTable, [
                'name'             => 'Homepage Slider — AWA Motos',
                'identifier'       => 'homepageslider',
                'status'           => 1,
                'store_id'         => '0',
                'autoplay'         => 1,
                'autoplay_timeout' => 5000,
                'navigation'       => 1,
                'stop_on_hover'    => 1,
                'pagination'       => 1,
                'items'            => 1,
                'rewind_speed'     => 1000,
                'slide_speed'      => 500,
            ]);

            $sliderId = $connection->lastInsertId($sliderTable);

            // Criar 3 slides placeholder com conteúdo real
            $slides = $this->getSlideDefinitions((int) $sliderId);

            foreach ($slides as $slide) {
                $connection->insert($slideTable, $slide);
            }

            $this->logger->info(
                sprintf('[AyoSeedContent] Slider "homepageslider" criado com %d slides.', count($slides))
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('[AyoSeedContent] Erro ao criar slider: %s', $e->getMessage())
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getSlideDefinitions(int $sliderId): array
    {
        return [
            [
                'slider_id'  => $sliderId,
                'name'       => 'Slide 1 — Peças e Acessórios',
                'status'     => 1,
                'sort_order' => 1,
                'content'    => '<div class="slide-content slide-1"><div class="slide-text-wrap"><h2 class="slide-title">Peças e Acessórios para Motos</h2><p class="slide-desc">Bagageiros, baús, retrovisores e mais — tudo para sua moto</p><a href="/catalogsearch/result/?q=bagageiro" class="slide-btn btn btn-primary">Ver Produtos</a></div></div>',
            ],
            [
                'slider_id'  => $sliderId,
                'name'       => 'Slide 2 — B2B Atacado',
                'status'     => 1,
                'sort_order' => 2,
                'content'    => '<div class="slide-content slide-2"><div class="slide-text-wrap"><h2 class="slide-title">Atacado para Lojistas e Oficinas</h2><p class="slide-desc">Cadastre-se no programa B2B e tenha preços especiais</p><a href="/b2b/account/register" class="slide-btn btn btn-primary">Cadastro B2B</a></div></div>',
            ],
            [
                'slider_id'  => $sliderId,
                'name'       => 'Slide 3 — Frete Grátis',
                'status'     => 1,
                'sort_order' => 3,
                'content'    => '<div class="slide-content slide-3"><div class="slide-text-wrap"><h2 class="slide-title">Frete Grátis Acima de R$ 299</h2><p class="slide-desc">Entrega rápida e segura para todo o Brasil</p><a href="/ofertas" class="slide-btn btn btn-primary">Comprar Agora</a></div></div>',
            ],
        ];
    }

    // ========================================================================
    // TESTIMONIALS
    // ========================================================================

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function seedTestimonials($connection): void
    {
        $table = $this->moduleDataSetup->getTable('rokanthemes_testimonials');

        if (!$connection->isTableExists($table)) {
            $this->logger->warning('[AyoSeedContent] Tabela de testimonials não encontrada — pulando.');
            return;
        }

        // Verificar se já existem depoimentos
        $existingCount = (int) $connection->fetchOne(
            $connection->select()->from($table, ['COUNT(*)'])
        );

        if ($existingCount > 0) {
            $this->logger->info(
                sprintf('[AyoSeedContent] Já existem %d depoimentos — pulando seed.', $existingCount)
            );
            return;
        }

        try {
            $testimonials = $this->getTestimonialDefinitions();

            foreach ($testimonials as $testimonial) {
                $connection->insert($table, $testimonial);
            }

            $this->logger->info(
                sprintf('[AyoSeedContent] %d depoimentos criados.', count($testimonials))
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('[AyoSeedContent] Erro ao criar depoimentos: %s', $e->getMessage())
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTestimonialDefinitions(): array
    {
        return [
            [
                'name'        => 'Carlos Silva',
                'email'       => 'carlos.silva@email.com',
                'content'     => 'Excelente loja! Comprei um bagageiro para minha CG 160 e chegou rápido, bem embalado. Qualidade top! Já é minha segunda compra e recomendo.',
                'rating'      => 5,
                'position'    => 'Motoboy — São Paulo',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2025-11-15 10:30:00',
            ],
            [
                'name'        => 'Ana Pereira',
                'email'       => 'ana.pereira@email.com',
                'content'     => 'Atendimento incrível pelo WhatsApp! Tive dúvida sobre compatibilidade do retrovisor com a minha Fazer 250 e me ajudaram na hora. Produto chegou certinho.',
                'rating'      => 5,
                'position'    => 'Motociclista — Campinas',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2025-12-02 14:15:00',
            ],
            [
                'name'        => 'Roberto Mendes',
                'email'       => 'roberto.mendes@email.com',
                'content'     => 'Sou dono de oficina e compro no atacado da AWA. Preços muito competitivos e entrega pontual. O programa B2B facilita demais.',
                'rating'      => 5,
                'position'    => 'Proprietário de Oficina — Ribeirão Preto',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2026-01-10 09:45:00',
            ],
            [
                'name'        => 'Fernanda Costa',
                'email'       => 'fernanda.costa@email.com',
                'content'     => 'Comprei o baú 45L para minha Bros 160 e superou as expectativas. Acabamento de qualidade, instalação simples. Frete grátis foi a cereja do bolo!',
                'rating'      => 5,
                'position'    => 'Viajante — Belo Horizonte',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2026-01-22 16:20:00',
            ],
            [
                'name'        => 'Marcos Oliveira',
                'email'       => 'marcos.oliveira@email.com',
                'content'     => 'Melhor loja de peças para motos que já comprei online. Site fácil de navegar, busca por modelo funciona muito bem. Vou voltar com certeza.',
                'rating'      => 4,
                'position'    => 'Entregador — Araraquara',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2026-02-05 11:00:00',
            ],
            [
                'name'        => 'Luciana Ramos',
                'email'       => 'luciana.ramos@email.com',
                'content'     => 'Precisava de peças para minha XRE 300 com urgência. Fiz o pedido e em 3 dias úteis já tinha tudo em casa. Ótimo custo-benefício. Nota 10!',
                'rating'      => 5,
                'position'    => 'Motociclista — Curitiba',
                'status'      => 1,
                'store_id'    => '0',
                'created_at'  => '2026-02-18 08:30:00',
            ],
        ];
    }

    // ========================================================================
    // FAQ
    // ========================================================================

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function seedFaq($connection): void
    {
        $table = $this->moduleDataSetup->getTable('rokanthemes_faq');

        if (!$connection->isTableExists($table)) {
            $this->logger->warning('[AyoSeedContent] Tabela de FAQ não encontrada — pulando.');
            return;
        }

        // Verificar se já existem FAQs
        $existingCount = (int) $connection->fetchOne(
            $connection->select()->from($table, ['COUNT(*)'])
        );

        if ($existingCount > 0) {
            $this->logger->info(
                sprintf('[AyoSeedContent] Já existem %d FAQs — pulando seed.', $existingCount)
            );
            return;
        }

        try {
            $faqs = $this->getFaqDefinitions();

            foreach ($faqs as $faq) {
                $connection->insert($table, $faq);
            }

            $this->logger->info(
                sprintf('[AyoSeedContent] %d perguntas FAQ criadas.', count($faqs))
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('[AyoSeedContent] Erro ao criar FAQs: %s', $e->getMessage())
            );
        }
    }

    /**
     * 20 perguntas frequentes cobrindo todos os cenários de uma loja de peças para motos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getFaqDefinitions(): array
    {
        $sortOrder = 0;

        return [
            // === ENVIO E ENTREGA ===
            [
                'title'      => 'Qual o prazo de entrega?',
                'content'    => 'O prazo de entrega varia de acordo com a região e a transportadora escolhida. Após a aprovação do pagamento, o pedido é preparado em até 2 dias úteis. O prazo de entrega da transportadora é calculado automaticamente no checkout conforme seu CEP.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Vocês oferecem frete grátis?',
                'content'    => 'Sim! Oferecemos frete grátis para compras acima de R$ 299,00 para todo o Brasil. Para pedidos abaixo desse valor, o frete é calculado conforme CEP de destino.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Como rastreio meu pedido?',
                'content'    => 'Após o envio, você receberá o código de rastreamento por e-mail. Também é possível acompanhar o status em "Minha Conta" > "Meus Pedidos". O rastreamento é atualizado automaticamente pela transportadora.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Entregam em todo o Brasil?',
                'content'    => 'Sim, entregamos para todas as regiões do Brasil através dos Correios e transportadoras parceiras. Quanto mais próximo de Araraquara-SP, mais rápido o envio.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === PAGAMENTO ===
            [
                'title'      => 'Quais formas de pagamento são aceitas?',
                'content'    => 'Aceitamos PIX (aprovação instantânea), boleto bancário, cartões de crédito (Visa, MasterCard, Elo, American Express, Hipercard) com parcelamento em até 12x sem juros, e débito online.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Posso parcelar minha compra?',
                'content'    => 'Sim! Parcelamos em até 12x sem juros no cartão de crédito. O valor mínimo de cada parcela é de R$ 30,00. Para pagamento via PIX ou boleto, o valor é à vista.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'O pagamento por PIX é seguro?',
                'content'    => 'Totalmente seguro! O PIX é regulamentado pelo Banco Central e funciona 24h por dia. Ao escolher PIX, o pedido é aprovado em segundos após a confirmação do pagamento.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === PRODUTOS ===
            [
                'title'      => 'Como sei se a peça serve na minha moto?',
                'content'    => 'Cada produto possui uma tabela de compatibilidade com os modelos de moto aceitos. Use a busca por aplicação informando marca, modelo e ano da sua moto. Se tiver dúvida, entre em contato pelo WhatsApp (16) 99736-7588 que confirmamos a compatibilidade.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Os produtos possuem garantia?',
                'content'    => 'Sim! Todos os nossos produtos possuem garantia do fabricante. O prazo varia de acordo com cada produto e fabricante. Consulte as condições de garantia na página do produto ou entre em contato conosco.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Vocês vendem peças originais ou paralelas?',
                'content'    => 'Trabalhamos com peças de qualidade comprovada de marcas reconhecidas no mercado. Cada produto tem sua marca claramente identificada na descrição. Não vendemos peças genéricas sem procedência.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === TROCAS E DEVOLUÇÕES ===
            [
                'title'      => 'Como faço para trocar ou devolver um produto?',
                'content'    => 'Você tem até 7 dias corridos após o recebimento para solicitar troca ou devolução (conforme CDC). Acesse "Minha Conta" > "Meus Pedidos" ou entre em contato pelo WhatsApp. O produto deve estar na embalagem original, sem sinais de uso.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Se a peça não servir, posso devolver?',
                'content'    => 'Sim! Se a peça não for compatível com sua moto, entre em contato em até 7 dias após o recebimento. Verificaremos a compatibilidade e, se confirmada a incompatibilidade, providenciaremos a troca ou reembolso. O frete de devolução será por nossa conta nesse caso.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Em quanto tempo recebo o reembolso?',
                'content'    => 'Após recebermos o produto devolvido e confirmarmos as condições, o reembolso é processado em até 5 dias úteis. Para cartão de crédito, o estorno pode levar até 2 faturas. Para PIX e boleto, o valor é devolvido via transferência bancária.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === CONTA E CADASTRO ===
            [
                'title'      => 'Preciso criar conta para comprar?',
                'content'    => 'Para compras no varejo, não é obrigatório — você pode finalizar como visitante. Porém, criar uma conta permite acompanhar pedidos, salvar endereços, manter lista de desejos e aproveitar promoções exclusivas.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'O que é o programa B2B?',
                'content'    => 'O cadastro B2B é destinado a lojistas, oficinas e revendedores que compram no atacado. Após aprovação do CNPJ, você tem acesso a preços diferenciados, condições especiais de pagamento e atendimento dedicado.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Como me cadastro no programa B2B?',
                'content'    => 'Acesse a página de cadastro B2B em nosso site, preencha os dados da empresa (CNPJ, razão social, etc.) e envie para análise. A aprovação leva até 24h úteis. Após aprovação, você já pode comprar com preços de atacado.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === SEGURANÇA ===
            [
                'title'      => 'A loja é confiável?',
                'content'    => 'Sim! Somos o Grupo Awamotos, distribuidora estabelecida em Araraquara-SP. Nosso site possui certificado SSL, pagamentos processados por gateways seguros (PagSeguro/Mercado Pago) e todas as transações são criptografadas.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Meus dados estão protegidos?',
                'content'    => 'Absolutamente. Seguimos a Lei Geral de Proteção de Dados (LGPD). Seus dados pessoais são criptografados e utilizados exclusivamente para processamento de pedidos e comunicação. Consulte nossa Política de Privacidade para mais detalhes.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            // === CONTATO ===
            [
                'title'      => 'Como entro em contato com vocês?',
                'content'    => 'Você pode nos contatar por:\n• <strong>WhatsApp:</strong> (16) 99736-7588\n• <strong>Telefone:</strong> (16) 3322-0000\n• <strong>E-mail:</strong> contato@awamotos.com.br\n• <strong>Formulário:</strong> Página de Atendimento ao Cliente\n\nAtendimento de segunda a sexta, das 8h às 18h, e sábados das 8h às 12h.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
            [
                'title'      => 'Posso retirar na loja física?',
                'content'    => 'Sim! Você pode optar por retirada na loja em Araraquara-SP. Basta selecionar "Retirada na Loja" durante o checkout. Seu pedido ficará disponível para retirada após confirmação do pagamento. Endereço: Rua Castro Alves, 1234 — Centro, Araraquara-SP.',
                'status'     => 1,
                'store_id'   => '0',
                'sort_order' => ++$sortOrder,
            ],
        ];
    }
}
