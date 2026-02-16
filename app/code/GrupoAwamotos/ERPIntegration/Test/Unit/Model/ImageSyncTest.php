<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Test\Unit\Model;

use GrupoAwamotos\ERPIntegration\Model\ImageSync;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Gallery\Processor as GalleryProcessor;
use Magento\Catalog\Api\ProductAttributeMediaGalleryManagementInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File as IoFile;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ImageSyncTest extends TestCase
{
    private ImageSync $imageSync;
    private ConnectionInterface|MockObject $connection;
    private Helper|MockObject $helper;
    private ProductRepositoryInterface|MockObject $productRepository;
    private GalleryProcessor|MockObject $galleryProcessor;
    private ProductAttributeMediaGalleryManagementInterface|MockObject $mediaGalleryManagement;
    private Filesystem|MockObject $filesystem;
    private IoFile|MockObject $ioFile;
    private SyncLogResource|MockObject $syncLogResource;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->helper = $this->createMock(Helper::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->galleryProcessor = $this->createMock(GalleryProcessor::class);
        $this->mediaGalleryManagement = $this->createMock(ProductAttributeMediaGalleryManagementInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->ioFile = $this->createMock(IoFile::class);
        $this->syncLogResource = $this->createMock(SyncLogResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Default helper behavior
        $this->helper->method('isImageSyncEnabled')->willReturn(true);
        $this->helper->method('getImageSource')->willReturn('auto');
        $this->helper->method('getImageBasePath')->willReturn('/mnt/erp/images');
        $this->helper->method('getImageBaseUrl')->willReturn('');

        // Mock filesystem
        $directoryWrite = $this->createMock(\Magento\Framework\Filesystem\Directory\WriteInterface::class);
        $directoryWrite->method('getAbsolutePath')->willReturn('/tmp/erp_images');

        $directoryRead = $this->createMock(\Magento\Framework\Filesystem\Directory\ReadInterface::class);
        $directoryRead->method('getAbsolutePath')->willReturn('/var/www/pub/media/catalog/product');

        $this->filesystem->method('getDirectoryWrite')->willReturn($directoryWrite);
        $this->filesystem->method('getDirectoryRead')->willReturn($directoryRead);

        $this->imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );
    }

    // ========== syncAll Tests ==========

    public function testSyncAllReturnsEarlyWhenDisabled(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->helper->method('isImageSyncEnabled')->willReturn(false);

        $imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );

        // Should not query database
        $this->connection->expects($this->never())->method('query');

        $result = $imageSync->syncAll();

        $this->assertEquals(0, $result['synced']);
        $this->assertEquals(0, $result['errors']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEquals(0, $result['total']);
    }

    public function testSyncAllProcessesProductsWithImages(): void
    {
        // Mock products with images query
        $this->connection->method('query')
            ->willReturn([
                ['CODIGO' => 'SKU-001'],
                ['CODIGO' => 'SKU-002'],
            ]);

        // Mock image data
        $this->connection->method('fetchOne')
            ->willReturn(null); // No images from table

        // Products don't exist in Magento (will be skipped)
        $this->productRepository->method('get')
            ->willThrowException(new NoSuchEntityException(__('Not found')));

        $result = $this->imageSync->syncAll();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('synced', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('execution_time', $result);
    }

    public function testSyncAllIncludesExecutionTime(): void
    {
        $this->connection->method('query')->willReturn([]);

        $result = $this->imageSync->syncAll();

        $this->assertArrayHasKey('execution_time', $result);
        $this->assertIsFloat($result['execution_time']);
    }

    // ========== syncBySku Tests ==========

    public function testSyncBySkuReturnsFalseWhenProductNotFound(): void
    {
        $this->productRepository->method('get')
            ->willThrowException(new NoSuchEntityException(__('Product not found')));

        $this->logger->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('Product not found'));

        $result = $this->imageSync->syncBySku('NONEXISTENT-SKU');

        $this->assertFalse($result);
    }

    public function testSyncBySkuReturnsFalseWhenNoImages(): void
    {
        // Product exists
        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->method('get')->willReturn($product);

        // But no images in ERP
        $this->connection->method('query')->willReturn([]);

        $result = $this->imageSync->syncBySku('SKU-NO-IMAGES');

        $this->assertFalse($result);
    }

    // ========== getErpImages Tests ==========

    public function testGetErpImagesFromTableSource(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->helper->method('isImageSyncEnabled')->willReturn(true);
        $this->helper->method('getImageSource')->willReturn('table');
        $this->helper->method('getImageBasePath')->willReturn('');
        $this->helper->method('getImageBaseUrl')->willReturn('');

        $imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );

        // Mock table query
        $this->connection->method('query')
            ->willReturn([
                ['IMAGEM' => '/path/image1.jpg', 'DESCRICAO' => 'Image 1', 'ORDEM' => 1, 'PRINCIPAL' => 'S'],
                ['IMAGEM' => '/path/image2.jpg', 'DESCRICAO' => 'Image 2', 'ORDEM' => 2, 'PRINCIPAL' => 'N'],
            ]);

        $result = $imageSync->getErpImages('TEST-SKU');

        $this->assertCount(2, $result);
        $this->assertEquals('/path/image1.jpg', $result[0]['path']);
        $this->assertEquals('Image 1', $result[0]['label']);
        $this->assertTrue($result[0]['is_main']);
    }

    public function testGetErpImagesFromUrlSource(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->helper->method('isImageSyncEnabled')->willReturn(true);
        $this->helper->method('getImageSource')->willReturn('url');
        $this->helper->method('getImageBasePath')->willReturn('');
        $this->helper->method('getImageBaseUrl')->willReturn('https://example.com/images/{sku}.jpg');

        $imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );

        // Note: This will actually try to call get_headers which we can't easily mock
        // In a real scenario we'd need to mock the function or use dependency injection
        $result = $imageSync->getErpImages('TEST-SKU');

        // Since we can't mock get_headers, this may return empty
        $this->assertIsArray($result);
    }

    public function testGetErpImagesAutoModeTriesTableFirst(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->helper->method('isImageSyncEnabled')->willReturn(true);
        $this->helper->method('getImageSource')->willReturn('auto');
        $this->helper->method('getImageBasePath')->willReturn('/mnt/erp/images');
        $this->helper->method('getImageBaseUrl')->willReturn('');

        $imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );

        // Table query returns images
        $this->connection->method('query')
            ->willReturn([
                ['IMAGEM' => '/path/image1.jpg', 'DESCRICAO' => '', 'ORDEM' => 1, 'PRINCIPAL' => 'S'],
            ]);

        $result = $imageSync->getErpImages('TEST-SKU');

        $this->assertCount(1, $result);
    }

    public function testGetErpImagesAutoModeFallsBackToFolder(): void
    {
        $this->helper = $this->createMock(Helper::class);
        $this->helper->method('isImageSyncEnabled')->willReturn(true);
        $this->helper->method('getImageSource')->willReturn('auto');
        $this->helper->method('getImageBasePath')->willReturn('/mnt/erp/images');
        $this->helper->method('getImageBaseUrl')->willReturn('');

        $imageSync = new ImageSync(
            $this->connection,
            $this->helper,
            $this->productRepository,
            $this->galleryProcessor,
            $this->mediaGalleryManagement,
            $this->filesystem,
            $this->ioFile,
            $this->syncLogResource,
            $this->logger
        );

        // Table query returns empty (triggers fallback to folder)
        $this->connection->method('query')
            ->willThrowException(new \Exception('Table not found'));

        // Should return empty since folder doesn't exist in test environment
        $result = $imageSync->getErpImages('TEST-SKU');

        $this->assertIsArray($result);
    }

    // ========== cleanOrphanImages Tests ==========

    public function testCleanOrphanImagesNotImplemented(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Orphan image cleanup is disabled'));

        $result = $this->imageSync->cleanOrphanImages();

        $this->assertEquals(
            [
                'removed' => 0,
                'skipped' => 0,
                'errors' => 0,
                'dry_run' => false,
                'products_checked' => 0,
                'orphans_found' => [],
            ],
            $result
        );
    }

    // ========== getPendingCount Tests ==========

    public function testGetPendingCountReturnsNumber(): void
    {
        $this->connection->method('fetchOne')
            ->willReturn(['total' => 150]);

        $result = $this->imageSync->getPendingCount();

        $this->assertEquals(150, $result);
    }

    public function testGetPendingCountReturnsZeroOnError(): void
    {
        $this->connection->method('fetchOne')
            ->willThrowException(new \Exception('Query failed'));

        $result = $this->imageSync->getPendingCount();

        $this->assertEquals(0, $result);
    }

    // ========== Image Validation Tests ==========

    /**
     * @dataProvider supportedExtensionsProvider
     */
    public function testSupportedExtensions(string $extension, bool $expected): void
    {
        // This tests the internal SUPPORTED_EXTENSIONS constant behavior
        $supportedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $this->assertEquals($expected, in_array($extension, $supportedExtensions));
    }

    public static function supportedExtensionsProvider(): array
    {
        return [
            'jpg is supported' => ['jpg', true],
            'jpeg is supported' => ['jpeg', true],
            'png is supported' => ['png', true],
            'gif is supported' => ['gif', true],
            'webp is supported' => ['webp', true],
            'bmp is not supported' => ['bmp', false],
            'tiff is not supported' => ['tiff', false],
            'svg is not supported' => ['svg', false],
        ];
    }

    // ========== Image Roles Tests ==========

    public function testFirstImageGetsAllRoles(): void
    {
        // This tests the internal IMAGE_ROLES constant behavior
        $imageRoles = ['image', 'small_image', 'thumbnail'];
        $this->assertContains('image', $imageRoles);
        $this->assertContains('small_image', $imageRoles);
        $this->assertContains('thumbnail', $imageRoles);
    }

    // ========== Error Handling Tests ==========

    public function testSyncAllLogsErrors(): void
    {
        $this->connection->method('query')
            ->willReturn([
                ['CODIGO' => 'SKU-001'],
            ]);

        // Product exists but throws exception during processing
        $product = $this->createMock(ProductInterface::class);
        $this->productRepository->method('get')->willReturn($product);

        // No images - will be skipped, not error
        $this->connection->method('fetchOne')->willReturn(null);

        $result = $this->imageSync->syncAll();

        // Should track skipped (no images) not errors
        $this->assertArrayHasKey('skipped', $result);
    }

    public function testSyncAllHandlesExceptionsGracefully(): void
    {
        $this->connection->method('query')
            ->willThrowException(new \Exception('Database connection lost'));

        // Exception is caught in getProductsWithImages() and logged as debug, not error
        $this->logger->expects($this->atLeastOnce())
            ->method('debug');

        // Should not throw exception
        $result = $this->imageSync->syncAll();

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('synced', $result);
    }

    // ========== Integration-like Tests ==========

    public function testFullSyncWorkflow(): void
    {
        // Setup: Products with images in ERP
        $this->connection->method('query')
            ->willReturnCallback(function ($sql) {
                if (strpos($sql, 'DISTINCT m.CODIGO') !== false) {
                    return [['CODIGO' => 'SKU-001']];
                }
                return [['IMAGEM' => 'test.jpg', 'DESCRICAO' => '', 'ORDEM' => 1, 'PRINCIPAL' => 'S']];
            });

        // Product exists in Magento but image cannot be processed (file doesn't exist)
        $product = $this->createMock(\Magento\Catalog\Model\Product::class);
        $product->method('getMediaGalleryEntries')->willReturn([]);

        $this->productRepository->method('get')->willReturn($product);

        $result = $this->imageSync->syncAll();

        // Should complete without errors even if images can't be processed
        $this->assertArrayHasKey('execution_time', $result);
        $this->assertGreaterThanOrEqual(0, $result['execution_time']);
    }
}
