<?php
/**
 * Helper para validação de CNPJ via API ReceitaWS
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\HTTP\Client\Curl;

class CnpjValidator extends AbstractHelper
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * URL da API ReceitaWS
     */
    const API_URL = 'https://receitaws.com.br/v1/cnpj/';

    public function __construct(
        Context $context,
        Curl $curl
    ) {
        $this->curl = $curl;
        parent::__construct($context);
    }

    /**
     * Validar CNPJ localmente (algoritmo)
     *
     * @param string $cnpj
     * @return bool
     */
    public function validateLocal(string $cnpj): bool
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        // Verifica se tem 14 dígitos
        if (strlen($cnpj) !== 14) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        
        // Validação do primeiro dígito verificador
        $soma = 0;
        $multiplicadores1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $multiplicadores1[$i];
        }
        
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;
        
        if ((int) $cnpj[12] !== $digito1) {
            return false;
        }
        
        // Validação do segundo dígito verificador
        $soma = 0;
        $multiplicadores2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $multiplicadores2[$i];
        }
        
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;
        
        return (int) $cnpj[13] === $digito2;
    }

    /**
     * Validar CNPJ via API ReceitaWS
     *
     * @param string $cnpj
     * @return array|null Dados da empresa ou null se inválido
     */
    public function validateApi(string $cnpj): ?array
    {
        // Primeiro valida localmente
        if (!$this->validateLocal($cnpj)) {
            return null;
        }
        
        $cnpjClean = preg_replace('/[^0-9]/', '', $cnpj);
        
        try {
            $this->curl->setOption(CURLOPT_TIMEOUT, 10);
            $this->curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
            $this->curl->addHeader('Accept', 'application/json');
            
            $this->curl->get(self::API_URL . $cnpjClean);
            
            $response = $this->curl->getBody();
            $data = json_decode($response, true);
            
            if (!$data || isset($data['status']) && $data['status'] === 'ERROR') {
                return null;
            }
            
            // Verifica se a empresa está ativa
            if (isset($data['situacao']) && strtoupper($data['situacao']) !== 'ATIVA') {
                return [
                    'valid' => false,
                    'message' => __('CNPJ com situação: %1', $data['situacao']),
                    'data' => $data
                ];
            }
            
            return [
                'valid' => true,
                'razao_social' => $data['nome'] ?? '',
                'nome_fantasia' => $data['fantasia'] ?? '',
                'cnpj' => $data['cnpj'] ?? $cnpj,
                'situacao' => $data['situacao'] ?? '',
                'tipo' => $data['tipo'] ?? '',
                'porte' => $data['porte'] ?? '',
                'natureza_juridica' => $data['natureza_juridica'] ?? '',
                'atividade_principal' => $data['atividade_principal'][0]['text'] ?? '',
                'logradouro' => $data['logradouro'] ?? '',
                'numero' => $data['numero'] ?? '',
                'complemento' => $data['complemento'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'municipio' => $data['municipio'] ?? '',
                'uf' => $data['uf'] ?? '',
                'cep' => $data['cep'] ?? '',
                'telefone' => $data['telefone'] ?? '',
                'email' => $data['email'] ?? '',
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            $this->_logger->error('Erro ao validar CNPJ via API: ' . $e->getMessage());
            
            // Se API falhar, retorna validação local
            return [
                'valid' => true,
                'api_error' => true,
                'message' => __('Validação via API indisponível. CNPJ validado localmente.')
            ];
        }
    }

    /**
     * Formatar CNPJ para exibição
     *
     * @param string $cnpj
     * @return string
     */
    public function format(string $cnpj): string
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) !== 14) {
            return $cnpj;
        }
        
        return sprintf(
            '%s.%s.%s/%s-%s',
            substr($cnpj, 0, 2),
            substr($cnpj, 2, 3),
            substr($cnpj, 5, 3),
            substr($cnpj, 8, 4),
            substr($cnpj, 12, 2)
        );
    }

    /**
     * Limpar CNPJ (remover formatação)
     *
     * @param string $cnpj
     * @return string
     */
    public function clean(string $cnpj): string
    {
        return preg_replace('/[^0-9]/', '', $cnpj);
    }
}
