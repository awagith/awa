<?php
namespace Rokanthemes\Faq\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Rokanthemes\Faq\Model\RokanFaqFactory;
use Rokanthemes\Faq\Model\ResourceModel\RokanFaq\CollectionFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\File\Csv;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\Controller\Result\JsonFactory;

abstract class AbstractStore extends Action
{
    const ADMIN_RESOURCE = 'Rokanthemes_Faq::rokanfaq';

    public $resultPageFactory;
    protected $resultRawFactory;
    protected $resultJsonFactory;
    protected $registry; 
    public $jsonHelper;
    public $logger;
    protected $faqFactory;
	protected $collectionStoreFactory;
    protected $fileFactory;
    protected $fileUploaderFactory;
    protected $csvProcessor;
    protected $dateTime;
    protected $filter;
    protected $componentRegistrar;
    protected $readFactory;
    protected $fileSystem;
    protected $imageAdapter;
    protected $codeCollection;
	protected $_directoryList;
	protected $_urlInterface;

    public function __construct(
        RokanFaqFactory $faqFactory,
		CollectionFactory $collectionFactory,
        Context $context,
        PageFactory $resultPageFactory,
        RawFactory $resultRawFactory,
        LoggerInterface $logger,
        Data $jsonHelper,
        FileFactory $fileFactory,
        UploaderFactory $fileUploaderFactory,
        Csv $csvProcessor,
        Registry $registry,
        DateTime $dateTime,
        Filter $filter,
        ComponentRegistrar $componentRegistrar,
        ReadFactory $readFactory,
        Filesystem $fileSystem,
		\Magento\Framework\App\Filesystem\DirectoryList $directoryList,
		\Magento\Framework\UrlInterface $urlInterface,    
        AdapterFactory $imageAdapter,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRawFactory = $resultRawFactory;
		$this->faqFactory = $faqFactory;
		$this->collectionStoreFactory = $collectionFactory;
        $this->logger = $logger;
        $this->jsonHelper = $jsonHelper;
        $this->fileFactory = $fileFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->csvProcessor = $csvProcessor;
        $this->registry = $registry;
        $this->dateTime = $dateTime;
        $this->filter = $filter;
        $this->componentRegistrar = $componentRegistrar;
        $this->readFactory = $readFactory;
        $this->fileSystem = $fileSystem;
        $this->imageAdapter = $imageAdapter;
        $this->resultJsonFactory = $resultJsonFactory;
		$this->_directoryList = $directoryList; 
		$this->_urlInterface = $urlInterface;
    }
}
