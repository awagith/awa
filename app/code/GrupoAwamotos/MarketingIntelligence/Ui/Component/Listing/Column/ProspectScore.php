<?php
declare(strict_types=1);

namespace GrupoAwamotos\MarketingIntelligence\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ProspectScore extends Column
{
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
            if (!isset($item[$fieldName]) || $item[$fieldName] === '') {
                $item[$fieldName] = '-';
                continue;
            }

            $score = (float) $item[$fieldName];
            $color = $this->resolveColor($score);

            $item[$fieldName] = sprintf(
                '<span style="font-weight:700;color:%s">%s</span>',
                $color,
                number_format($score, 0, ',', '.')
            );
        }
        unset($item);

        return $dataSource;
    }

    private function resolveColor(float $score): string
    {
        if ($score >= 80.0) {
            return '#166534';
        }

        if ($score >= 60.0) {
            return '#0f766e';
        }

        if ($score >= 40.0) {
            return '#b45309';
        }

        return '#991b1b';
    }
}
