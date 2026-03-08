<?php

declare(strict_types=1);

namespace GrupoAwamotos\Theme\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class HomeMerchandising implements ArgumentInterface
{
    public function __construct(
        private readonly UrlInterface $urlBuilder
    ) {
    }

    /**
     * @return array<int, array{id:int, icon:string, name:string}>
     */
    public function getCategoryCarouselCategories(): array
    {
        return [
            [
                'id' => 45,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="14" y="22" width="36" height="24" rx="4"/><rect x="14" y="22" width="36" height="8" rx="4" fill="currentColor" opacity="0.12"/><path d="M26 22v-4a6 6 0 0112 0v4"/><rect x="27" y="32" width="10" height="8" rx="2"/><path d="M32 40v3"/><path d="M22 46v4"/><path d="M42 46v4"/><path d="M18 50h28" stroke-width="2.5"/></svg>',
                'name' => 'Bauletos',
            ],
            [
                'id' => 44,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 30c5-8 13-12 24-12s19 4 24 12"/><circle cx="8" cy="30" r="4" fill="currentColor" opacity="0.15"/><circle cx="56" cy="30" r="4" fill="currentColor" opacity="0.15"/><rect x="27" y="22" width="10" height="16" rx="3"/><path d="M27 30h-10"/><path d="M37 30h10"/><path d="M30 38v6"/><path d="M34 38v6"/></svg>',
                'name' => 'Guidoes',
            ],
            [
                'id' => 41,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="26" cy="26" rx="15" ry="17"/><ellipse cx="26" cy="26" rx="10" ry="12" fill="currentColor" opacity="0.1"/><circle cx="26" cy="26" r="3" fill="currentColor" opacity="0.2"/><path d="M38 36l14 14" stroke-width="2.5"/><path d="M52 50l-5 0 0-5"/></svg>',
                'name' => 'Retrovisores',
            ],
            [
                'id' => 38,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 14h28"/><path d="M18 14c-2 10-4 24-4 36h36c0-12-2-26-4-36"/><path d="M14 50h36" stroke-width="2.5"/><path d="M22 28h20"/><ellipse cx="32" cy="38" rx="7" ry="5" fill="currentColor" opacity="0.12"/><path d="M28 14v-4"/><path d="M36 14v-4"/></svg>',
                'name' => 'Carcacas',
            ],
            [
                'id' => 88,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="14" y="28" width="36" height="10" rx="3" fill="currentColor" opacity="0.1"/><rect x="14" y="28" width="36" height="10" rx="3"/><path d="M18 28v-8l14-7 14 7v8"/><path d="M22 33h20"/><path d="M14 38l-4 14"/><path d="M50 38l4 14"/><circle cx="10" cy="54" r="2.5" fill="currentColor" opacity="0.2"/><circle cx="54" cy="54" r="2.5" fill="currentColor" opacity="0.2"/></svg>',
                'name' => 'Pedaleiras',
            ],
            [
                'id' => 65,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="32" cy="32" r="20"/><circle cx="32" cy="32" r="14" fill="currentColor" opacity="0.08"/><circle cx="32" cy="32" r="8"/><circle cx="32" cy="32" r="3" fill="currentColor" opacity="0.2"/><path d="M32 12v-2"/><path d="M32 54v-2"/><path d="M12 32h-2"/><path d="M54 32h-2"/></svg>',
                'name' => 'Lentes de Freio',
            ],
            [
                'id' => 85,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 32h52" stroke-width="2.5"/><rect x="26" y="26" width="12" height="12" rx="3" fill="currentColor" opacity="0.12"/><rect x="26" y="26" width="12" height="12" rx="3"/><path d="M18 32v-10a4 4 0 014-4h4"/><path d="M46 32v-10a4 4 0 00-4-4h-4"/><circle cx="6" cy="32" r="3.5" fill="currentColor" opacity="0.15"/><circle cx="58" cy="32" r="3.5" fill="currentColor" opacity="0.15"/></svg>',
                'name' => 'Barras de Guidao',
            ],
            [
                'id' => 54,
                'icon' => '<svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="16" y="16" width="32" height="34" rx="3"/><path d="M16 16l8-6h16l8 6"/><rect x="16" y="16" width="32" height="10" rx="3" fill="currentColor" opacity="0.1"/><circle cx="24" cy="32" r="3"/><circle cx="40" cy="32" r="3"/><circle cx="24" cy="42" r="3"/><circle cx="40" cy="42" r="3"/><path d="M12 50h40" stroke-width="2.5"/></svg>',
                'name' => 'Suportes',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getHomeShelves(): array
    {
        return [
            [
                'key' => 'bestsellers',
                'type' => 'bestseller',
                'section_class' => 'top-home-content--bestsellers',
                'aria_label' => 'Mais vendidos',
                'label' => 'Alta rotacao',
                'title' => 'Mais vendidos',
                'block_data' => [
                    'dom_id' => 'awa-home-bestsellers',
                    'carousel_variant' => 'awa-home-merch-shelf--bestseller',
                    'qty' => 8,
                    'row_show' => 1,
                    'default' => 4,
                    'desktop' => 4,
                    'desktop_small' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                    'next_back' => 1,
                    'nav_ctrl' => 0,
                ],
            ],
            [
                'key' => 'bauletos',
                'type' => 'category',
                'section_class' => 'top-home-content--category-shelf top-home-content--category-shelf-bauletos',
                'aria_label' => 'Bauletos',
                'label' => 'Transporte e viagem',
                'title' => 'Bauletos',
                'block_data' => [
                    'dom_id' => 'awa-home-bauletos',
                    'identify' => 'awa-home-bauletos',
                    'category_id' => '45',
                    'limit_qty' => 8,
                    'slide_row' => 1,
                    'slide_limit' => 4,
                    'default' => 4,
                    'desktop' => 4,
                    'desktop_small' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                    'show_pager' => 0,
                    'template' => 'GrupoAwamotos_Theme::home/category-shelf.phtml',
                ],
            ],
            [
                'key' => 'guidoes',
                'type' => 'category',
                'section_class' => 'top-home-content--category-shelf top-home-content--category-shelf-guidoes',
                'aria_label' => 'Guidoes',
                'label' => 'Controle e pilotagem',
                'title' => 'Guidoes',
                'block_data' => [
                    'dom_id' => 'awa-home-guidoes',
                    'identify' => 'awa-home-guidoes',
                    'category_id' => '44',
                    'limit_qty' => 8,
                    'slide_row' => 1,
                    'slide_limit' => 4,
                    'default' => 4,
                    'desktop' => 4,
                    'desktop_small' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                    'show_pager' => 0,
                    'template' => 'GrupoAwamotos_Theme::home/category-shelf.phtml',
                ],
            ],
            [
                'key' => 'launches',
                'type' => 'newproduct',
                'section_class' => 'top-home-content--launches',
                'aria_label' => 'Lancamentos',
                'label' => 'Novidades',
                'title' => 'Lancamentos',
                'block_data' => [
                    'dom_id' => 'awa-home-launches',
                    'carousel_variant' => 'awa-home-merch-shelf--launches',
                    'qty' => 8,
                    'items_show' => 1,
                    'default' => 4,
                    'desktop' => 4,
                    'desktop_small' => 3,
                    'tablet' => 2,
                    'mobile' => 1,
                    'next_back' => 1,
                    'nav_ctrl' => 0,
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{title:string, segment:string, description:string, query:string, url:string}>
     */
    public function getApplicationShortcuts(): array
    {
        $shortcuts = [
            [
                'title' => 'CG 160',
                'segment' => 'Street',
                'description' => 'Reposicao rapida para a linha Honda com maior giro no varejo.',
                'query' => 'CG 160',
            ],
            [
                'title' => 'Biz 125',
                'segment' => 'Motoneta',
                'description' => 'Itens de uso urbano com alta recorrencia de manutencao.',
                'query' => 'Biz 125',
            ],
            [
                'title' => 'Bros 160',
                'segment' => 'Trail',
                'description' => 'Aplicacoes para uso misto, rua e estrada de terra.',
                'query' => 'Bros 160',
            ],
            [
                'title' => 'Pop 110i',
                'segment' => 'Mobilidade',
                'description' => 'Pecas de giro para motos de entrada e uso diario.',
                'query' => 'Pop 110i',
            ],
            [
                'title' => 'Fazer 250',
                'segment' => 'Street premium',
                'description' => 'Acessorios e reposicao para media cilindrada mais buscada.',
                'query' => 'Fazer 250',
            ],
            [
                'title' => 'Lander 250',
                'segment' => 'Trail premium',
                'description' => 'Busca rapida para modelos trail de maior procura.',
                'query' => 'Lander 250',
            ],
        ];

        return array_map(
            fn (array $shortcut): array => $shortcut + [
                'url' => $this->urlBuilder->getUrl('catalogsearch/result', ['_query' => ['q' => $shortcut['query']]]),
            ],
            $shortcuts
        );
    }
}
