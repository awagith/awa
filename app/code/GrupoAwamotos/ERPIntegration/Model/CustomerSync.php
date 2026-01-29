<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\CustomerSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CustomerSync implements CustomerSyncInterface
{
    private ConnectionInterface $connection;
    private Helper $helper;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerInterfaceFactory $customerFactory;
    private StoreManagerInterface $storeManager;
    private SyncLogResource $syncLogResource;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        StoreManagerInterface $storeManager,
        SyncLogResource $syncLogResource,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->storeManager = $storeManager;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
    }

    public function getErpCustomerByTaxvat(string $taxvat): ?array
    {
        $cleanTaxvat = preg_replace('/[^0-9]/', '', $taxvat);

        try {
            $sql = "SELECT f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CPF,
                           f.ENDERECO, f.NUMERO, f.BAIRRO, f.CIDADE, f.CEP, f.UF,
                           f.CONDPAGTO, f.FATORPRECO, f.CKPESSOA,
                           c.EMAIL, c.FONE1, c.FONECEL, c.WHATSAPP, c.NOME AS CONTATO_NOME
                    FROM FN_FORNECEDORES f
                    LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                    WHERE f.CKCLIENTE = 'S'
                      AND (REPLACE(REPLACE(REPLACE(f.CGC, '.', ''), '/', ''), '-', '') = :taxvat
                           OR REPLACE(REPLACE(f.CPF, '.', ''), '-', '') = :taxvat2)";

            return $this->connection->fetchOne($sql, [
                ':taxvat' => $cleanTaxvat,
                ':taxvat2' => $cleanTaxvat,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Customer lookup error: ' . $e->getMessage());
            return null;
        }
    }

    public function getErpCustomerByCode(int $code): ?array
    {
        try {
            $sql = "SELECT f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CPF,
                           f.ENDERECO, f.NUMERO, f.BAIRRO, f.CIDADE, f.CEP, f.UF,
                           f.CONDPAGTO, f.FATORPRECO, f.CKPESSOA,
                           c.EMAIL, c.FONE1, c.FONECEL, c.WHATSAPP
                    FROM FN_FORNECEDORES f
                    LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                    WHERE f.CODIGO = :code AND f.CKCLIENTE = 'S'";

            return $this->connection->fetchOne($sql, [':code' => $code]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Customer code lookup error: ' . $e->getMessage());
            return null;
        }
    }

    public function syncAll(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'errors' => 0, 'skipped' => 0];

        try {
            $sql = "SELECT f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CPF,
                           f.ENDERECO, f.NUMERO, f.BAIRRO, f.CIDADE, f.CEP, f.UF,
                           f.CKPESSOA,
                           c.EMAIL, c.FONE1, c.FONECEL, c.WHATSAPP
                    FROM FN_FORNECEDORES f
                    LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                    WHERE f.CKCLIENTE = 'S' AND f.ATCLIENTE = 'S'
                      AND c.EMAIL IS NOT NULL AND c.EMAIL <> ''";

            $rows = $this->connection->query($sql);

            foreach ($rows as $row) {
                try {
                    $email = trim(strtolower($row['EMAIL'] ?? ''));
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $result['skipped']++;
                        continue;
                    }

                    $erpCode = (string) $row['CODIGO'];

                    // Map ERP entity
                    $this->syncLogResource->setEntityMap(
                        'customer',
                        $erpCode,
                        0, // Will be updated if customer exists in Magento
                        md5(json_encode($row))
                    );

                    $result['updated']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->logger->error('[ERP] Customer sync error: ' . $e->getMessage());
                }
            }

            $this->syncLogResource->addLog(
                'customer',
                'import',
                $result['errors'] > 0 ? 'error' : 'success',
                sprintf('Mapeados: %d, Erros: %d, Ignorados: %d', $result['updated'], $result['errors'], $result['skipped']),
                null,
                null,
                $result['updated']
            );
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Customer sync failed: ' . $e->getMessage());
            $this->syncLogResource->addLog('customer', 'import', 'error', $e->getMessage());
        }

        return $result;
    }
}
