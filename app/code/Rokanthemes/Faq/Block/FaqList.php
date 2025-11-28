<?php 
namespace Rokanthemes\Faq\Block;

use \Magento\Framework\Json\Helper\Data as DataHelper;
use Magento\Framework\DataObjectFactory;
use Magento\Store\Api\StoreRepositoryInterface;

class FaqList extends \Magento\Framework\View\Element\Template
{
    
    private $dataHelper;
	private $_jsonEncoder;
	protected $customerSession;
	protected $dataObjectFactory;
	protected $_storeManager;
	protected $_storeRepository;
	protected $objectManager;
	protected $_resource;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
		\Magento\Framework\Json\EncoderInterface $jsonEncoder,
		DataObjectFactory $dataObjectFactory,
		StoreRepositoryInterface $storeRepository,
		\Magento\Store\Model\StoreManagerInterface $storeManager, 
		\Magento\Framework\ObjectManagerInterface $objectManager,
		\Magento\Customer\Model\Session $customerSession,
		\Magento\Framework\App\ResourceConnection $resource,
		DataHelper $dataHelper,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
		$this->customerSession = $customerSession;
		$this->_storeManager = $storeManager; 
		$this->objectManager = $objectManager;
		$this->_storeRepository = $storeRepository;
		$this->dataObjectFactory = $dataObjectFactory;
		$this->_jsonEncoder = $jsonEncoder;
		$this->_resource = $resource;
        parent::__construct($context, $data);
    } 
	
	public function getHtmlDataFaq()
	{
		$html = '';
		$adapter  = $this->_resource->getConnection();
		$tableName = $this->_resource->getTableName("rokan_faq");
        $sql = "SELECT * FROM ".$tableName." WHERE status='1' AND parent_id=''";
        $data_query = $adapter->fetchAll($sql);
		if($data_query){
			$html .= '<ul class="level1 list-unstyled">';
			foreach ($data_query as $item) {
				$html .= '<li class="faq-item">';
					$html .= '<h4 class="question"><a><i class="toogle lnr lnr-chevron-down"></i>'.$item['question'].'</a></h4>';
					if($this->checkDataFaqById($item['entity_id'])){
						$html .= $this->htmlDataFaqLevel($item['entity_id'],2);
					}else{
						$html .= '<div class="answer">'.$item['answer'].'</div>';
					}
				$html .= '</li>';
			}
			$html .= '</ul>';
		}
		
		return $html;
	}
	
	public function checkDataFaqById($parent_id)
	{
		$adapter  = $this->_resource->getConnection();
		$tableName = $this->_resource->getTableName("rokan_faq");
        $sql = "SELECT * FROM ".$tableName." WHERE status='1' AND parent_id='$parent_id'";
        $data_query = $adapter->fetchAll($sql);
		if(count($data_query) > 0){
			return true;
		}
		return false;
	}
	
	public function htmlDataFaqLevel($parent_id,$level)
	{
		$html = '';
		$adapter  = $this->_resource->getConnection();
		$tableName = $this->_resource->getTableName("rokan_faq");
        $sql = "SELECT * FROM ".$tableName." WHERE status='1' AND parent_id='$parent_id'";
        $data_query = $adapter->fetchAll($sql);
		if($data_query){
			$html .= '<div class="sub-questions"><ul class="level'.$level.'">';
				foreach ($data_query as $item) {
				$html .= '<li class="faq-item">';
					$html .= '<a class="question">'.$item['question'].'</a>';
					if($this->checkDataFaqById($item['entity_id'])){
						$html .= $this->htmlDataFaqLevel($item['entity_id'],$level+1);
					}else{
						$html .= '<div class="answer">'.$item['answer'].'</div>';
					}
				$html .= '</li>';
			}
			$html .= '</ul></div>';
		}
		return $html;
	}
}