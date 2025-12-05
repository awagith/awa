<?php
namespace GrupoAwamotos\SocialProof\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class AddViewCountObserver implements ObserverInterface
{
    /**
     * Adiciona contador de visualizações ao produto
     * Simula visualizações entre 15-45 por dia
     * 
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        
        if (!$product || !$product->getId()) {
            return;
        }
        
        // Simular visualizações baseado no ID do produto (determinístico)
        $seed = $product->getId() + (int)date('Ymd');
        mt_srand($seed);
        $viewsToday = mt_rand(15, 45);
        
        $product->setData('views_today', $viewsToday);
        
        // Badge "MAIS VENDIDO" para produtos com qty vendida > 50
        // Isso requer dados reais de vendas, por ora vamos simular
        $isBestSeller = ($product->getId() % 5 === 0); // 20% dos produtos
        $product->setData('is_best_seller', $isBestSeller);
    }
}
