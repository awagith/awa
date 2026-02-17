<?php
namespace GrupoAwamotos\RexisML\Model;

use Magento\Framework\Model\AbstractModel;

class DatasetRecomendacao extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\GrupoAwamotos\RexisML\Model\ResourceModel\DatasetRecomendacao::class);
    }

    public function getIdentificadorCliente()
    {
        return $this->getData('identificador_cliente');
    }

    public function getIdentificadorProduto()
    {
        return $this->getData('identificador_produto');
    }

    public function getClassificacaoProduto()
    {
        return $this->getData('classificacao_produto');
    }

    public function getPred()
    {
        return $this->getData('pred');
    }

    public function getProbabilidadeCompra()
    {
        return $this->getData('probabilidade_compra');
    }

    public function getPrevisaoGastoRoundUp()
    {
        return $this->getData('previsao_gasto_round_up');
    }

    public function getRecencia()
    {
        return $this->getData('recencia');
    }

    public function getFrequencia()
    {
        return $this->getData('frequencia');
    }

    public function getValorMonetario()
    {
        return $this->getData('valor_monetario');
    }
}
