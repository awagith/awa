<?php
declare(strict_types=1);

namespace GrupoAwamotos\MarketingIntelligence\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class CnaeProfile extends Column
{
    /**
     * @var array<string, array{label: string, bg: string, color: string}>
     */
    private const PROFILE_MAP = [
        'direct' => ['label' => 'Direto', 'bg' => '#dcfce7', 'color' => '#166534'],
        'adjacent' => ['label' => 'Adjacente', 'bg' => '#fef3c7', 'color' => '#92400e'],
        'off_profile' => ['label' => 'Fora do Perfil', 'bg' => '#e2e8f0', 'color' => '#334155'],
    ];

    private Escaper $escaper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param array<string, mixed> $components
     * @param array<string, mixed> $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array<string, mixed> $dataSource
     * @return array<string, mixed>
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items']) || !is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = (string) $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $value = (string) ($item[$fieldName] ?? '');
            $item[$fieldName] = $this->renderBadge($value);
        }
        unset($item);

        return $dataSource;
    }

    private function renderBadge(string $value): string
    {
        $normalized = strtolower(trim($value));
        $badge = self::PROFILE_MAP[$normalized] ?? null;

        if ($badge === null) {
            $label = $value !== '' ? $this->escaper->escapeHtml($value) : '-';
            return sprintf(
                '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:%s;color:%s">%s</span>',
                '#e2e8f0',
                '#334155',
                $label
            );
        }

        return sprintf(
            '<span style="display:inline-block;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:%s;color:%s">%s</span>',
            $badge['bg'],
            $badge['color'],
            (string) __($badge['label'])
        );
    }
}
