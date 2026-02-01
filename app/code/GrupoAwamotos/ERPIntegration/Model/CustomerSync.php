<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\CustomerSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\RegionInterfaceFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CustomerSync implements CustomerSyncInterface
{
    private const BATCH_SIZE = 100;
    private const CUSTOMER_GROUP_B2B = 4; // Grupo B2B (Revendedor)

    private ConnectionInterface $connection;
    private Helper $helper;
    private CustomerRepositoryInterface $customerRepository;
    private CustomerInterfaceFactory $customerFactory;
    private AddressInterfaceFactory $addressFactory;
    private AddressRepositoryInterface $addressRepository;
    private RegionInterfaceFactory $regionFactory;
    private RegionFactory $regionModelFactory;
    private StoreManagerInterface $storeManager;
    private SyncLogResource $syncLogResource;
    private LoggerInterface $logger;
    private Random $random;
    private EncryptorInterface $encryptor;

    private array $regionCache = [];
    private array $erpMappingCache = [];

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        CustomerRepositoryInterface $customerRepository,
        CustomerInterfaceFactory $customerFactory,
        AddressInterfaceFactory $addressFactory,
        AddressRepositoryInterface $addressRepository,
        RegionInterfaceFactory $regionFactory,
        RegionFactory $regionModelFactory,
        StoreManagerInterface $storeManager,
        SyncLogResource $syncLogResource,
        LoggerInterface $logger,
        Random $random,
        EncryptorInterface $encryptor
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->customerRepository = $customerRepository;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionFactory = $regionFactory;
        $this->regionModelFactory = $regionModelFactory;
        $this->storeManager = $storeManager;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
        $this->random = $random;
        $this->encryptor = $encryptor;
    }

    public function getErpCustomerByTaxvat(string $taxvat): ?array
    {
        $cleanTaxvat = preg_replace('/[^0-9]/', '', $taxvat);

        try {
            $sql = "SELECT f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CPF,
                           f.ENDERECO, f.NUMERO, f.BAIRRO, f.CIDADE, f.CEP, f.UF,
                           f.COMPLEMENTO, f.CONDPAGTO, f.FATORPRECO, f.CKPESSOA,
                           f.INSCEST, f.INSCMUN, f.DATACADASTRO,
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
                           f.COMPLEMENTO, f.CONDPAGTO, f.FATORPRECO, f.CKPESSOA,
                           f.INSCEST, f.INSCMUN, f.DATACADASTRO,
                           c.EMAIL, c.FONE1, c.FONECEL, c.WHATSAPP, c.NOME AS CONTATO_NOME
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

        if (!$this->helper->isCustomerSyncEnabled()) {
            $this->logger->info('[ERP] Customer sync is disabled');
            return $result;
        }

        try {
            $totalCount = $this->getErpCustomerCount();
            $this->logger->info(sprintf('[ERP] Starting customer sync. Total in ERP: %d', $totalCount));

            $offset = 0;
            while ($offset < $totalCount) {
                $customers = $this->getErpCustomersBatch($offset, self::BATCH_SIZE);

                foreach ($customers as $row) {
                    try {
                        $syncResult = $this->processSingleCustomer($row);

                        switch ($syncResult) {
                            case 'created':
                                $result['created']++;
                                break;
                            case 'updated':
                                $result['updated']++;
                                break;
                            case 'skipped':
                                $result['skipped']++;
                                break;
                        }
                    } catch (\Exception $e) {
                        $result['errors']++;
                        $this->logger->error(sprintf(
                            '[ERP] Customer sync error for code %s: %s',
                            $row['CODIGO'] ?? 'unknown',
                            $e->getMessage()
                        ));
                    }
                }

                $offset += self::BATCH_SIZE;
                $this->logger->info(sprintf('[ERP] Customer sync progress: %d/%d', min($offset, $totalCount), $totalCount));

                // Garbage collection
                if ($offset % 500 === 0) {
                    gc_collect_cycles();
                }
            }

            $this->syncLogResource->addLog(
                'customer',
                'import',
                $result['errors'] > 0 ? 'partial' : 'success',
                sprintf(
                    'Criados: %d, Atualizados: %d, Ignorados: %d, Erros: %d',
                    $result['created'],
                    $result['updated'],
                    $result['skipped'],
                    $result['errors']
                ),
                null,
                null,
                $result['created'] + $result['updated']
            );
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Customer sync failed: ' . $e->getMessage());
            $this->syncLogResource->addLog('customer', 'import', 'error', $e->getMessage());
        }

        return $result;
    }

    public function createOrUpdateCustomer(array $erpData, bool $createIfNotExists = true): ?CustomerInterface
    {
        $email = $this->normalizeEmail($erpData['EMAIL'] ?? '');
        if (empty($email)) {
            $this->logger->warning('[ERP] Cannot create customer without email');
            return null;
        }

        $erpCode = (int)($erpData['CODIGO'] ?? 0);
        if ($erpCode === 0) {
            $this->logger->warning('[ERP] Cannot create customer without ERP code');
            return null;
        }

        try {
            $websiteId = $this->storeManager->getDefaultStoreView()->getWebsiteId();

            // Tenta encontrar cliente existente
            $existingCustomer = $this->findExistingCustomer($email, $erpCode, $websiteId);

            if ($existingCustomer) {
                return $this->updateExistingCustomer($existingCustomer, $erpData);
            }

            if (!$createIfNotExists) {
                return null;
            }

            return $this->createNewCustomer($erpData, $websiteId);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Create/update customer failed: ' . $e->getMessage());
            return null;
        }
    }

    public function syncCustomerAddresses(int $customerId, int $erpCode): bool
    {
        try {
            // Busca endereços do ERP
            $addresses = $this->getErpAddresses($erpCode);

            if (empty($addresses)) {
                return true; // Sem endereços não é erro
            }

            foreach ($addresses as $addressData) {
                $this->createOrUpdateAddress($customerId, $addressData);
            }

            $this->logger->info(sprintf('[ERP] Synced %d addresses for customer %d', count($addresses), $customerId));
            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[ERP] Address sync failed for customer %d: %s', $customerId, $e->getMessage()));
            return false;
        }
    }

    public function linkMagentoToErp(int $customerId, int $erpCode): bool
    {
        try {
            // Verifica se cliente existe no Magento
            $customer = $this->customerRepository->getById($customerId);

            // Verifica se código ERP é válido
            $erpCustomer = $this->getErpCustomerByCode($erpCode);
            if (!$erpCustomer) {
                throw new LocalizedException(__('Código ERP %1 não encontrado', $erpCode));
            }

            // Salva mapeamento
            $this->syncLogResource->setEntityMap(
                'customer',
                (string)$erpCode,
                $customerId,
                md5(json_encode($erpCustomer))
            );

            // Atualiza atributo custom no cliente
            $customer->setCustomAttribute('erp_code', $erpCode);
            $this->customerRepository->save($customer);

            $this->logger->info(sprintf('[ERP] Linked Magento customer %d to ERP code %d', $customerId, $erpCode));
            return true;
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[ERP] Failed to link customer %d to ERP: %s', $customerId, $e->getMessage()));
            return false;
        }
    }

    public function getErpCodeByCustomerId(int $customerId): ?int
    {
        // Tenta cache primeiro
        if (isset($this->erpMappingCache[$customerId])) {
            return $this->erpMappingCache[$customerId];
        }

        try {
            // Busca no mapeamento
            $erpCode = $this->syncLogResource->getErpCodeByMagentoId('customer', $customerId);

            if (!$erpCode) {
                // Tenta pelo atributo custom
                $customer = $this->customerRepository->getById($customerId);
                $erpCodeAttr = $customer->getCustomAttribute('erp_code');
                if ($erpCodeAttr) {
                    $erpCode = (int)$erpCodeAttr->getValue();
                }
            }

            if ($erpCode) {
                $this->erpMappingCache[$customerId] = (int)$erpCode;
                return (int)$erpCode;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function syncByTaxvat(string $taxvat): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'customer_id' => null,
            'erp_code' => null,
        ];

        $erpData = $this->getErpCustomerByTaxvat($taxvat);

        if (!$erpData) {
            $result['message'] = 'Cliente não encontrado no ERP com este CPF/CNPJ';
            return $result;
        }

        $customer = $this->createOrUpdateCustomer($erpData);

        if ($customer) {
            $result['success'] = true;
            $result['message'] = 'Cliente sincronizado com sucesso';
            $result['customer_id'] = (int)$customer->getId();
            $result['erp_code'] = (int)$erpData['CODIGO'];

            // Sincroniza endereços
            $this->syncCustomerAddresses((int)$customer->getId(), (int)$erpData['CODIGO']);
        } else {
            $result['message'] = 'Falha ao sincronizar cliente';
        }

        return $result;
    }

    /**
     * Get customer credit information from ERP
     *
     * @param string $erpCode
     * @return array|null
     */
    public function getCustomerCreditFromErp(string $erpCode): ?array
    {
        try {
            $sql = "SELECT f.CODIGO, f.LIMITE_CREDITO, f.SALDO_DEVEDOR, f.DIAS_ATRASO,
                           f.BLOQUEADO, f.MOTIVO_BLOQUEIO, f.CONDPAGTO
                    FROM FN_FORNECEDORES f
                    WHERE f.CODIGO = :code AND f.CKCLIENTE = 'S'";

            return $this->connection->fetchOne($sql, [':code' => (int)$erpCode]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Credit lookup error: ' . $e->getMessage());
            return null;
        }
    }

    // ==================== Private Methods ====================

    private function getErpCustomerCount(): int
    {
        $sql = "SELECT COUNT(*) as total
                FROM FN_FORNECEDORES f
                LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                WHERE f.CKCLIENTE = 'S' AND f.ATCLIENTE = 'S'
                  AND c.EMAIL IS NOT NULL AND c.EMAIL <> ''
                  AND c.EMAIL LIKE '%@%.%'";

        $result = $this->connection->fetchOne($sql);
        return (int)($result['total'] ?? 0);
    }

    private function getErpCustomersBatch(int $offset, int $limit): array
    {
        $sql = "SELECT f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CPF,
                       f.ENDERECO, f.NUMERO, f.BAIRRO, f.CIDADE, f.CEP, f.UF,
                       f.COMPLEMENTO, f.CKPESSOA, f.INSCEST, f.INSCMUN,
                       f.CONDPAGTO, f.FATORPRECO, f.DATACADASTRO,
                       c.EMAIL, c.FONE1, c.FONECEL, c.WHATSAPP, c.NOME AS CONTATO_NOME
                FROM FN_FORNECEDORES f
                LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = f.CODIGO AND c.PRINCIPAL = 'S'
                WHERE f.CKCLIENTE = 'S' AND f.ATCLIENTE = 'S'
                  AND c.EMAIL IS NOT NULL AND c.EMAIL <> ''
                  AND c.EMAIL LIKE '%@%.%'
                ORDER BY f.CODIGO
                OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

        return $this->connection->query($sql, [':offset' => $offset, ':limit' => $limit]);
    }

    private function processSingleCustomer(array $row): string
    {
        $email = $this->normalizeEmail($row['EMAIL'] ?? '');
        if (empty($email)) {
            return 'skipped';
        }

        $erpCode = (int)$row['CODIGO'];
        $dataHash = md5(json_encode($row));

        // Verifica se dados mudaram desde último sync
        $existingHash = $this->syncLogResource->getEntityMapHash('customer', (string)$erpCode);
        if ($existingHash === $dataHash) {
            return 'skipped'; // Sem mudanças
        }

        $customer = $this->createOrUpdateCustomer($row);

        if ($customer) {
            // Sincroniza endereço principal
            $this->syncCustomerAddresses((int)$customer->getId(), $erpCode);

            // Atualiza hash
            $this->syncLogResource->setEntityMap('customer', (string)$erpCode, (int)$customer->getId(), $dataHash);

            return $existingHash ? 'updated' : 'created';
        }

        return 'skipped';
    }

    private function findExistingCustomer(string $email, int $erpCode, int $websiteId): ?CustomerInterface
    {
        // Primeiro tenta pelo mapeamento ERP
        $mappedId = $this->syncLogResource->getEntityMap('customer', (string)$erpCode);
        if ($mappedId) {
            try {
                return $this->customerRepository->getById($mappedId);
            } catch (NoSuchEntityException $e) {
                // Mapeamento antigo, limpar
            }
        }

        // Depois tenta pelo email
        try {
            return $this->customerRepository->get($email, $websiteId);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    private function createNewCustomer(array $erpData, int $websiteId): ?CustomerInterface
    {
        $email = $this->normalizeEmail($erpData['EMAIL']);
        $isPJ = ($erpData['CKPESSOA'] ?? 'F') === 'J';

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->setEmail($email);
        $customer->setStoreId($this->storeManager->getDefaultStoreView()->getId());

        // Nome
        $names = $this->parseCustomerName($erpData);
        $customer->setFirstname($names['firstname']);
        $customer->setLastname($names['lastname']);

        // Grupo de cliente (B2B se for pessoa jurídica)
        if ($isPJ) {
            $customer->setGroupId(self::CUSTOMER_GROUP_B2B);
        }

        // CPF/CNPJ
        $taxvat = $isPJ ? ($erpData['CGC'] ?? '') : ($erpData['CPF'] ?? '');
        if ($taxvat) {
            $customer->setTaxvat($this->cleanTaxvat($taxvat));
        }

        // Atributos custom
        $customer->setCustomAttribute('erp_code', (int)$erpData['CODIGO']);

        if (!empty($erpData['INSCEST'])) {
            $customer->setCustomAttribute('inscricao_estadual', $erpData['INSCEST']);
        }

        if (!empty($erpData['FONECEL']) || !empty($erpData['WHATSAPP'])) {
            $phone = $erpData['WHATSAPP'] ?: $erpData['FONECEL'];
            $customer->setCustomAttribute('celular', $this->formatPhone($phone));
        }

        // Salva cliente
        $savedCustomer = $this->customerRepository->save($customer);

        // Cria endereço principal
        $this->createPrimaryAddress((int)$savedCustomer->getId(), $erpData);

        $this->logger->info(sprintf(
            '[ERP] Created customer: %s (Magento ID: %d, ERP: %d)',
            $email,
            $savedCustomer->getId(),
            $erpData['CODIGO']
        ));

        return $savedCustomer;
    }

    private function updateExistingCustomer(CustomerInterface $customer, array $erpData): CustomerInterface
    {
        $updated = false;

        // Atualiza telefone se mudou
        $phone = $erpData['WHATSAPP'] ?? $erpData['FONECEL'] ?? '';
        if ($phone) {
            $formattedPhone = $this->formatPhone($phone);
            $existingPhone = $customer->getCustomAttribute('celular');
            if (!$existingPhone || $existingPhone->getValue() !== $formattedPhone) {
                $customer->setCustomAttribute('celular', $formattedPhone);
                $updated = true;
            }
        }

        // Atualiza código ERP se não tiver
        $existingErpCode = $customer->getCustomAttribute('erp_code');
        if (!$existingErpCode || !$existingErpCode->getValue()) {
            $customer->setCustomAttribute('erp_code', (int)$erpData['CODIGO']);
            $updated = true;
        }

        // Atualiza inscrição estadual
        if (!empty($erpData['INSCEST'])) {
            $existingIE = $customer->getCustomAttribute('inscricao_estadual');
            if (!$existingIE || $existingIE->getValue() !== $erpData['INSCEST']) {
                $customer->setCustomAttribute('inscricao_estadual', $erpData['INSCEST']);
                $updated = true;
            }
        }

        if ($updated) {
            return $this->customerRepository->save($customer);
        }

        return $customer;
    }

    private function createPrimaryAddress(int $customerId, array $erpData): void
    {
        if (empty($erpData['ENDERECO']) || empty($erpData['CIDADE'])) {
            return;
        }

        try {
            $address = $this->addressFactory->create();
            $address->setCustomerId($customerId);

            $names = $this->parseCustomerName($erpData);
            $address->setFirstname($names['firstname']);
            $address->setLastname($names['lastname']);

            // Monta endereço completo
            $street = [
                trim($erpData['ENDERECO']),
                trim($erpData['NUMERO'] ?? 'S/N'),
                trim($erpData['COMPLEMENTO'] ?? ''),
                trim($erpData['BAIRRO'] ?? ''),
            ];
            $address->setStreet(array_filter($street));

            $address->setCity(trim($erpData['CIDADE']));
            $address->setPostcode($this->formatCep($erpData['CEP'] ?? ''));
            $address->setCountryId('BR');

            // Region
            $regionCode = strtoupper(trim($erpData['UF'] ?? 'SP'));
            $region = $this->getRegionByCode($regionCode);
            if ($region) {
                $address->setRegionId($region->getRegionId());
                $address->setRegion($region);
            }

            // Telefone
            $phone = $erpData['FONE1'] ?? $erpData['FONECEL'] ?? '';
            $address->setTelephone($this->formatPhone($phone) ?: '(00) 0000-0000');

            // Define como padrão
            $address->setIsDefaultBilling(true);
            $address->setIsDefaultShipping(true);

            $this->addressRepository->save($address);
        } catch (\Exception $e) {
            $this->logger->warning(sprintf(
                '[ERP] Failed to create address for customer %d: %s',
                $customerId,
                $e->getMessage()
            ));
        }
    }

    private function createOrUpdateAddress(int $customerId, array $addressData): void
    {
        // Similar à createPrimaryAddress mas para endereços adicionais
        $this->createPrimaryAddress($customerId, $addressData);
    }

    private function getErpAddresses(int $erpCode): array
    {
        // Busca endereços de entrega do ERP
        try {
            $sql = "SELECT e.ENDERECO, e.NUMERO, e.COMPLEMENTO, e.BAIRRO, e.CIDADE, e.CEP, e.UF,
                           e.TIPO, c.FONE1
                    FROM FN_ENDERECO e
                    LEFT JOIN FN_CONTATO c ON c.FORNECEDOR = e.FORNECEDOR AND c.PRINCIPAL = 'S'
                    WHERE e.FORNECEDOR = :code AND e.ATIVO = 'S'
                    ORDER BY e.TIPO";

            return $this->connection->query($sql, [':code' => $erpCode]);
        } catch (\Exception $e) {
            // Tabela pode não existir
            return [];
        }
    }

    private function parseCustomerName(array $erpData): array
    {
        $isPJ = ($erpData['CKPESSOA'] ?? 'F') === 'J';

        if ($isPJ) {
            // Pessoa Jurídica: usa FANTASIA ou RAZAO
            $name = trim($erpData['FANTASIA'] ?? '') ?: trim($erpData['RAZAO'] ?? 'Cliente');
        } else {
            // Pessoa Física: usa nome do contato ou RAZAO
            $name = trim($erpData['CONTATO_NOME'] ?? '') ?: trim($erpData['RAZAO'] ?? 'Cliente');
        }

        $parts = explode(' ', $name, 2);

        return [
            'firstname' => $parts[0] ?: 'Cliente',
            'lastname' => $parts[1] ?? 'ERP',
        ];
    }

    private function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));

        // Validação básica
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return $email;
    }

    private function cleanTaxvat(string $taxvat): string
    {
        return preg_replace('/[^0-9]/', '', $taxvat);
    }

    private function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($clean) === 11) {
            return sprintf('(%s) %s-%s', substr($clean, 0, 2), substr($clean, 2, 5), substr($clean, 7));
        } elseif (strlen($clean) === 10) {
            return sprintf('(%s) %s-%s', substr($clean, 0, 2), substr($clean, 2, 4), substr($clean, 6));
        }

        return $phone;
    }

    private function formatCep(string $cep): string
    {
        $clean = preg_replace('/[^0-9]/', '', $cep);

        if (strlen($clean) === 8) {
            return sprintf('%s-%s', substr($clean, 0, 5), substr($clean, 5));
        }

        return $clean ?: '00000-000';
    }

    private function getRegionByCode(string $code): ?\Magento\Customer\Api\Data\RegionInterface
    {
        if (isset($this->regionCache[$code])) {
            return $this->regionCache[$code];
        }

        try {
            $region = $this->regionModelFactory->create();
            $region->loadByCode($code, 'BR');

            if ($region->getId()) {
                $regionInterface = $this->regionFactory->create();
                $regionInterface->setRegionId($region->getId());
                $regionInterface->setRegionCode($code);
                $regionInterface->setRegion($region->getName());

                $this->regionCache[$code] = $regionInterface;
                return $regionInterface;
            }
        } catch (\Exception $e) {
            // Região não encontrada
        }

        return null;
    }
}
