<?php
/**
 * Carrier Method - Seleção de Transportadora
 */
declare(strict_types=1);

namespace GrupoAwamotos\CarrierSelect\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use GrupoAwamotos\CarrierSelect\Model\ResourceModel\Carrier\CollectionFactory;

class CarrierSelect extends AbstractCarrier implements CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'carrierselect';

    /**
     * @var bool
     */
    protected $_isFixed = true;

    /**
     * @var ResultFactory
     */
    private ResultFactory $rateResultFactory;

    /**
     * @var MethodFactory
     */
    private MethodFactory $rateMethodFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $carrierCollectionFactory;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param CollectionFactory $carrierCollectionFactory
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        ResultFactory $rateResultFactory,
        MethodFactory $rateMethodFactory,
        CollectionFactory $carrierCollectionFactory,
        array $data = []
    ) {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->carrierCollectionFactory = $carrierCollectionFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();
        
        // Buscar transportadoras ativas do banco
        $carriers = $this->carrierCollectionFactory->create()
            ->addActiveFilter()
            ->addSortOrder();

        if ($carriers->getSize() > 0) {
            // Criar um método de envio para cada transportadora
            foreach ($carriers as $carrier) {
                $method = $this->rateMethodFactory->create();
                
                $method->setCarrier($this->_code);
                $method->setCarrierTitle($this->getConfigData('title'));
                
                // Código único para cada transportadora
                $method->setMethod($carrier->getCode());
                $method->setMethodTitle($carrier->getName());
                
                // Preço zero (a combinar)
                $shippingPrice = 0;
                $method->setPrice($shippingPrice);
                $method->setCost($shippingPrice);
                
                $result->append($method);
            }
        } else {
            // Se não houver transportadoras, mostra método genérico
            $method = $this->rateMethodFactory->create();
            
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod('acombinar');
            $method->setMethodTitle(__('Frete a combinar'));
            
            $shippingPrice = $this->getConfigData('price');
            $method->setPrice($shippingPrice);
            $method->setCost($shippingPrice);
            
            $result->append($method);
        }

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        $methods = [];
        
        $carriers = $this->carrierCollectionFactory->create()
            ->addActiveFilter()
            ->addSortOrder();

        foreach ($carriers as $carrier) {
            $methods[$carrier->getCode()] = $carrier->getName();
        }

        if (empty($methods)) {
            $methods['acombinar'] = __('Frete a combinar');
        }

        return $methods;
    }
}
