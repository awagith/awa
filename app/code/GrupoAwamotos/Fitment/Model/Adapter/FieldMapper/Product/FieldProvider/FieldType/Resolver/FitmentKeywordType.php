<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\Resolver;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeAdapter;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ConverterInterface;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ResolverInterface;

/**
 * Resolves fitment attributes (marca_moto, modelo_moto, ano_moto) as keyword type
 * to enable filtering/aggregation in OpenSearch while maintaining searchability.
 *
 * Magento's default KeywordType resolver skips attributes that are both searchable AND filterable,
 * mapping them as text type. This causes OpenSearch BadRequest400Exception when aggregations
 * are attempted on text fields.
 */
class FitmentKeywordType implements ResolverInterface
{
    /**
     * Fitment attribute codes that need keyword mapping
     */
    private const FITMENT_ATTRIBUTES = [
        'marca_moto',
        'modelo_moto',
        'ano_moto',
    ];

    private ConverterInterface $fieldTypeConverter;

    /**
     * @param ConverterInterface $fieldTypeConverter
     */
    public function __construct(ConverterInterface $fieldTypeConverter)
    {
        $this->fieldTypeConverter = $fieldTypeConverter;
    }

    /**
     * Returns keyword type for fitment attributes regardless of searchable flag.
     *
     * @param AttributeAdapter $attribute
     * @return string|null
     */
    public function getFieldType(AttributeAdapter $attribute): ?string
    {
        if (in_array($attribute->getAttributeCode(), self::FITMENT_ATTRIBUTES, true)) {
            return $this->fieldTypeConverter->convert(ConverterInterface::INTERNAL_DATA_TYPE_KEYWORD);
        }

        return null;
    }
}
