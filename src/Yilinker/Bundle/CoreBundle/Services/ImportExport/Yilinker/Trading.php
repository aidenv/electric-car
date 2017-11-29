<?php

namespace Yilinker\Bundle\CoreBundle\Services\ImportExport\Yilinker;

use Yilinker\Bundle\CoreBundle\Services\Search\ManufacturerProductUnitSearchService;
use Yilinker\Bundle\CoreBundle\Services\Location\LocationService;
use Yilinker\Bundle\CoreBundle\Services\Transaction\TransactionService;
use Yilinker\Bundle\CoreBundle\Entity\ApiAccessLog;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\Currency;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\MerchantBundle\Services\FileUpload\ProductFileUploader;
use Yilinker\Bundle\CoreBundle\Entity;
use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Adapter\AwsS3;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Yaml\Parser;
use DomDocument;
use DateTime;
use Symfony\Component\Filesystem\Filesystem as fs;
use \Exception;

/**
 * Class Trading
 */
class Trading
{

    /**
     * Requested by trading team to detect language to use
     */
    const TRADING_LANGUAGE = "US";
    /**
     * Requested by trading team to detect language to use
     */
//    const TRADING_LANGUAGE = "CN";

    /**
     * Requested by trading team to detect source of the request
     */
    const TRADING_SOURCE_ID = 1;
       
    /**
     * API Timeout in seconds
     *
     * @var int
     */
    const API_TIMEOUT_SEC = 10;

    /**
     * Result per request
     *
     * @var int
     */
    const RESULT_PER_REQUEST = 50;

    /**
     * Field type
     *
     * @var string
     */
    const FIELD_TYPE_IMAGE = 'image';

    /**
     * Field type
     *
     * @var string
     */
    const FIELD_TYPE_HTML = 'html';

    const ANNOTATION_FIXED_REFERENCE = '@fixed';

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var  TransactionService
     */
    private $transactionService;

    /**
     * @var  LocationService
     */
    private $locationService;

    /**
     * @var mixed $configuration
     */
    private $configuration;

    /**
     * @var mixed $mapping
     */
    private $mapping;

    /**
     * Currency 
     *
     * @var Yilinker\Bundle\CoreBundle\Entity\Currency
     */
    private $currency;

    /**
     * Elastic search wrapper service for ManufacturerProductUnit
     *
     * @var Yilinker\Bundle\CoreBundle\Services\Search\ManufacturerProductUnitSearchService
     */
    private $supplierProductSearchService;

    private $imageManipulator;

    private $fixedReferences = array();

    /**
     * @var Gaufrette\Filesystem
     */
    private $photoFilesystem;

    /**
     * @var string
     */
    private $kernelRootDirectory;

    /**
     * @var array
     */
    private $ignoreFields;

    /**
     * @var Yilinker\Bundle\CoreBundle\Services\Predis\PredisService
     */
    private $predisService;
    
    /**
     * @param EntityManager $entityManager
     * @param TransactionService $transactionService
     * @param LocationService $locationService
     * @param ManufacturerProductUnitSearchService $supplierSearchService
     * @param string $currencyCode
     * @param string $mappingPath 
     * @param ImageManipulation $imageManipulator
     * @param Gaufrette\Filesystem $photoFilesystem
     * @param Yilinker\Bundle\CoreBundle\Services\Predis\PredisService $predisService
     * @param string $kernelRootDirectory
     */
    public function __construct (
        EntityManager $entityManager, 
        TransactionService $transactionService,
        LocationService $locationService,
        ManufacturerProductUnitSearchService $supplierProductSearchService,
        $currencyCode = Currency::CURRENCY_PH_PESO,
        $mappingPath = null,
        $imageManipulator,
        $photoFilesystem,
        $predisService,
        $kernelRootDirectory
    )
    {
        $this->em = $entityManager;
        $this->transactionService = $transactionService;
        $this->locationService = $locationService;
        $this->supplierProductSearchService = $supplierProductSearchService;
        $this->currency = $entityManager->getRepository('YilinkerCoreBundle:Currency')
                                        ->findOneBy(array('code' => $currencyCode));
        $this->setApiMapping($mappingPath);
        $this->imageManipulator = $imageManipulator;
        $this->photoFilesystem = $photoFilesystem;
        $this->kernelRootDirectory = $kernelRootDirectory;
        $this->predisService = $predisService;
        $this->ignoreFields = array();
    }
    
    /**
     * Set the API configuration
     *
     * @param mixed $config
     */
    public function setConfig($config)
    {
        $this->configuration = $config;
    }

    /**
     * Retrieve the API configuration
     *
     * @param return mixed
     */
    public function getConfig()
    {
        return $this->configuration;
    }

    /**
     * Set the mapping configuration
     *
     * @param $string $path
     */
    public function setApiMapping($mappingConfigPath = null)
    {
        $yaml = new Parser;
        $path = $mappingConfigPath ? $mappingConfigPath : '/../../../Resources/config/trading_mapping.yml';
        $config = $yaml->parse(file_get_contents(__DIR__.$path));
        $this->mapping = new ParameterBag($config);
    }

    /**
     * Retrieves the API mapping
     *
     * @return ParameterBag
     */
    public function getApiMapping()
    {
        return $this->mapping;
    }

    /**
     * Retrieve manufacturer product
     *
     * @param int $page
     * @param int $limit
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @return mixed
     */
    public function getProducts($page = 1, $limit = 10, $beginDate = null, $endDate = null)
    {
        $searchResults = $this->supplierProductSearchService->searchWithElastic(
            $beginDate, $endDate, array(ManufacturerProductUnit::MANUFACTURER_PRODUCT_STATUS_ACTIVE),
            $page, $limit
        );

        $productUnits = array();
        foreach($searchResults['manufacturerProductUnits'] as $manufacturerProductUnit){
            $productUnits[] = array(
                'product_reference_no' => $manufacturerProductUnit->getReferenceNumber(),
                'product_sku'          => $manufacturerProductUnit->getSku(),
                'session'              => $manufacturerProductUnit->getViewCount(),
                'favorite'             => $manufacturerProductUnit->getWishlistCount(),
                "rating"               => $manufacturerProductUnit->getAverageRating(),
                "agent"                => $manufacturerProductUnit->getStoreCount(),
                "last_update"          => $manufacturerProductUnit->getDateLastModified()->format("Y-m-d H:i:s"),
                "inventory"            => $manufacturerProductUnit->getQuantity(),
                "comment"              => $manufacturerProductUnit->getReviewCount(),
                "crt"                  => '100', //TODO
            );
        }

        return array(
            "products" => $productUnits,
            "totalPage" => (int) $searchResults['totalPage'],
        );
    }

    /**
     * Retrieve orders in YLO database
     *
     * @param int $page 
     * @param int $limit
     * @param DateTime $beginDate
     * @param DateTime $endDate
     */
    public function getOrders($page = 1, $limit = 10, $beginDate = null, $endDate = null)
    {
        $offset = $this->getOffset($limit, $page);

        $statuses = $this->transactionService->getOrderStatusesValid();

        $orderStatusRepository = $this->em->getRepository('YilinkerCoreBundle:OrderStatus');
        $statuses = $orderStatusRepository->getOrderStatusIn($statuses);

        $orderRepository = $this->em->getRepository('YilinkerCoreBundle:UserOrder');
        $countryRepository = $this->em->getRepository('YilinkerCoreBundle:Country');
        $orderCollection = $orderRepository->getUserOrders($offset, $limit, $beginDate, $endDate, $statuses, true);

        $orders = array();

        foreach ($orderCollection["orders"] as $order){
            $consigneeLocation = $order->getConsigneeLocation();

            $country = null;
            if(!is_null($consigneeLocation)){
                $this->locationService->constructLocationHierarchy($consigneeLocation);
                $countryName = $this->locationService->getLocationData()["country"];
                $country = $countryRepository->findOneBy(array('name' => $countryName));
            }
           
            $resellerOrderProducts = $order->getResellerOrderProducts();           
            $amount = "0.0000";
            foreach($resellerOrderProducts as $orderProduct){
                $amount = bcadd($amount, $orderProduct->getQuantifiedUnitPrice(), 4);
            }

            array_push($orders, array(
                "sales_id"          => $order->getOrderId(),
                "transaction_no"    => $order->getInvoiceNumber(),
                "transaction_date"  => $order->getDateAdded()? $order->getDateAdded()->format("Y-m-d H:i:s") : null,
                "buyer_name"        => $order->getBuyer()->getFullName(),
                "status"            => $order->getOrderStatus()->getName(),
                "order_status_id"   => $order->getOrderStatus()->getOrderStatusId(),
                "amount"            => number_format(floatval($amount), 2, ".", ","),
                "currency"          => $this->currency->getCode(),
                "currency_symbol"   => $this->currency->getSymbol(),
                "currency_name"     => $this->currency->getName(),
                "country_id"        => $country ? $country->getReferenceNumber() : null,
                "country_name"      => $country ? $country->getName() : "",
                "country_code"      => $country ? $country->getCode() : "",
            ));
        }

        return array(
            "orders" => $orders,
            "totalResults" => $orderCollection["totalResults"],
            "totalPage" => $orderCollection["totalPage"]
        );
    }

    /**
     * Retrieve order products in the YLO database
     *
     * @param int $page
     * @param int $limit
     * @param DateTime $beginDate
     * @param DateTime $endDate
     * @return mixed
     */
    public function getOrderProducts($page = 1, $limit = 10, $beginDate = null, $endDate = null)
    {
        $offset = $this->getOffset($limit, $page);

        $statuses = $this->transactionService->getOrderStatusesValid();

        $orderStatusRepository = $this->em->getRepository('YilinkerCoreBundle:OrderStatus');
        $statuses = $orderStatusRepository->getOrderStatusIn($statuses);

        $orderProductRepository = $this->em->getRepository('YilinkerCoreBundle:OrderProduct');
        $orderProductCollection = $orderProductRepository->getResellerOrderProducts($offset, $limit, $beginDate, $endDate, $statuses);

        $orderProducts = array();

        $itemReceivedStatus = $this->em->getReference(
            'YilinkerCoreBundle:OrderProductStatus', OrderProductStatus::STATUS_ITEM_RECEIVED_BY_BUYER
        );
        foreach ($orderProductCollection["orderProducts"] as $orderProduct){

            
            $buyerReceivedHistory = $orderProduct->getHistoryDate($itemReceivedStatus);
            $orderProductData =  array(
                "sales_detail_id"         => $orderProduct->getOrderProductId(),
                "sales"                   => $orderProduct->getOrder()->getOrderId(),
                "product_sku"             => $orderProduct->getSku(),
                "product_reference_no"    => $orderProduct->getManufacturerProductReference(),
                "quantity"                => $orderProduct->getQuantity(),
                "total_price"             => $orderProduct->getQuantifiedUnitPrice(),
                "unit_price"              => $orderProduct->getUnitPrice(),
                "currency"                => $this->currency->getCode(),
                "currency_symbol"         => $this->currency->getSymbol(),
                "currency_name"           => $this->currency->getName(),
                "product_name"            => $orderProduct->getProductName(),
                "status"                  => $orderProduct->getOrderProductStatus() ?
                                             $orderProduct->getOrderProductStatus()->getName() : "Waiting for payment",
                "order_product_status_id" => $orderProduct->getOrderProductStatus() ?
                                             $orderProduct->getOrderProductStatus()->getOrderProductStatusId() : null,
                "delivery_date"           => $buyerReceivedHistory,                
            );

            /**
             * Overwrite status if order product is available for payout
             */
            if($buyerReceivedHistory){
                $currentDate = Carbon::now();
                $dateReceived = Carbon::instance($buyerReceivedHistory);
                if($currentDate->diffInDays($dateReceived) >= TransactionService::PAYOUT_DAYS_ELAPSED){
                    $orderProductData['status'] = "Available for Payout";
                    $orderProductData['order_product_status_id'] = OrderProductStatus::CODE_AVAILABLE_FOR_PAYOUT;
                }    
            }
            
            array_push($orderProducts, $orderProductData);
        }

        return array(
            "orderProducts" => $orderProducts,
            "totalResults" => $orderProductCollection["totalResults"],
            "totalPage" => $orderProductCollection["totalPage"]
        );
    }

    /**
     * Set the ignore fields for use with setEntityFields
     */
    public function setIgnoredFields($ignoreFields = array())
    {
        $this->ignoreFields = $ignoreFields;
    }
    
    /**
     * Synchronize brands
     *
     * @param string $mappingKey
     * @param int $perPage
     * @param int $page
     * @param boolean $ignoreAccessLog
     * @param DateTime $dateFrom (has precedence over ignoreAccessLog)
     * @param DateTime $dateTo
     * @param string $queryString
     * @param array $skus
     * @return mixed
     */
    public function synchronizeApiData(
        $mappingKey,
        $perPage = self::RESULT_PER_REQUEST,
        $page = 1,
        $ignoreAccessLog = false,
        DateTime $dateFrom = null,
        DateTime $dateTo = null,
        $queryString = null,
        $skus = array()
    )
    {
        $response = array(
            'message'      => 'Mapping key is undefined',
            'isSuccessful' => false,
            'data'         => array(),  
        );

        $mapping = $this->mapping->get('tables');
        if(isset($mapping[$mappingKey])){
            $mappingData = $mapping[$mappingKey];

            $currentDatetime = new \DateTime('now');
            $lastAccessLog = $this->em->getRepository('YilinkerCoreBundle:ApiAccessLog')
                                  ->getLastAccessLogByType(constant($mappingData['api_type']));
            $additionalParameters = isset($mappingData['parameters']) ? $mappingData['parameters'] : array();
            $request = new FormRequest(
                FormRequest::METHOD_GET, 
                "/".$this->configuration['routes'][$mappingData['route_name']]."/api_key/".$this->configuration['api_key'], 
                $this->configuration['baseurl']
            );

            $parameters = array(
                'lan'        => self::TRADING_LANGUAGE,
                'source_id'  => self::TRADING_SOURCE_ID,
                'dateTo'     => $currentDatetime->format('Y-m-d H:i:s'),
                'perPage'    => $perPage,
                'page'       => $page,
                'statusFlag' => 1, //default status flag to 1 for trading to identify the one accessing their api
            );

            /**
             * Append additional parameters
             */
            foreach($additionalParameters as $key => $param){
                if(isset($param['value'])){
                    $parameters[$key] = $param['value'];
                }
            }

            if($dateFrom){
                $parameters['dateFrom'] = $dateFrom->format('Y-m-d H:i:s');
            }
            else if($lastAccessLog && !$ignoreAccessLog){
                $parameters['dateFrom'] = $lastAccessLog->getDateAdded()->format('Y-m-d H:i:s');
            }

            if($dateTo){
                $parameters['dateTo'] = $dateTo->format('Y-m-d H:i:s');
            }

            if($queryString && isset($mappingData['searchField']) && $mappingData['searchField']){
                $parameters[$mappingData['searchField']] = $queryString;
            }

            if ($skus) {
                $parameters['sku'] = $skus;
            }

            try{
                $buzzResponse = new Response();
                $request->setFields($parameters);
                $client = new Curl();
                $client->setTimeout(self::API_TIMEOUT_SEC);
                $client->send($request, $buzzResponse);

                if ($buzzResponse->isSuccessful()){
                    $apiResponse = json_decode($buzzResponse->getContent(), true);

                    $indexedData = array();
                    $syncCounter = 0;
                    $createDateField = $mappingData['dateField']['create'];
                    $updateDateField = $mappingData['dateField']['update'];
                    $tableKey = $mappingData['key']['tableKey'];
                    $apiKey = $mappingData['key']['apiKey'];
                    $currentDatetime = new \DateTime();
                    $filters = isset($mappingData['filter']) ? $mappingData['filter'] : array();
                    if(isset($mappingData['detail_api'])){
                        $detailApiMapping = $mappingData['detail_api'];
                        $detailRequest = new FormRequest(
                            FormRequest::METHOD_GET, 
                            "/".$this->configuration['routes'][$detailApiMapping['route_name']]."/api_key/".$this->configuration['api_key'], 
                            $this->configuration['baseurl']
                        );

                        foreach($apiResponse['data'] as $apiRow){

                            $isValid = true;
                            foreach($filters as $key => $filter){
                                if(!isset($apiRow[$key]) || $apiRow[$key] != $filter){
                                    $isValid = false;
                                    break;
                                }
                            }
                            if($isValid === false){
                                continue;
                            }

                            $detailBuzzResponse = new Response();
                            $detailRequest->setFields(array(
                                $detailApiMapping['parameterKey'] => $apiRow[$apiKey],
                                'lan'        => self::TRADING_LANGUAGE,
                                'source_id'  => self::TRADING_SOURCE_ID,
                            ));
                            $detailClient = new Curl();
                            $detailClient->setTimeout(self::API_TIMEOUT_SEC);
                            $detailClient->send($detailRequest, $detailBuzzResponse);
                            if($detailBuzzResponse->isSuccessful()){
                                $detailApiResponse = json_decode($detailBuzzResponse->getContent(), true);
                                $mergedData = array_merge($detailApiResponse['data'], $apiRow);
                                $indexedData[$apiRow[$apiKey]] = $mergedData;
                            }
                        }
                    }
                    else{
                        foreach($apiResponse['data'] as $apiRow){
                            $isValid = true;
                            foreach($filters as $key => $filter){
                                if(!isset($apiRow[$key]) || $apiRow[$key] != $filter){
                                    $isValid = false;
                                    break;
                                }
                            }
                            if($isValid === false){
                                continue;
                            }

                            $indexedData[$apiRow[$apiKey]] = $apiRow;
                        }
                    }
                    
                    if($mappingKey === "manufacturerProduct"){

                        /**
                         * Format manufacturer product attribute data to handle attribute name and attribute value mapping
                         */
                        foreach($indexedData as $mainkey => $data){

                            /**
                             * Make each attribute value key unique
                             */
                            $groupedAttributesByValue = array();                             
                            foreach($data['productAttributeInfo'] as $unitkey => $productUnitData){
                                foreach($productUnitData['attributeValueIds'] as $key => $attributeValue){
                                    /**
                                     * Create identifier value. All attribute values that fail a regex for this will be deleted
                                     */
                                    $identifier = $attributeValue."[".$productUnitData['sku']."]";
                                    $productUnitData['attributeValueIds'][$key] = $identifier;
                                    $groupedAttributesByValue[$attributeValue][] = $identifier;                                    
                                }
                                $data['productAttributeInfo'][$unitkey] = $productUnitData;
                            }

                            /**
                             * Create multiple attribute values, one for each product unit
                             */
                            foreach($data['attributes'] as $attributekey => $attribute){
                                $attributeValuesTemp = $attribute['values'];
                                $attribute['values'] = array();
                                foreach($attributeValuesTemp as $key => $attributeValue){
                                    if(isset($groupedAttributesByValue[$attributeValue['id']])){
                                        foreach($groupedAttributesByValue[$attributeValue['id']] as $identifier){
                                            $attribute['values'][] = array(
                                                'id'    => $identifier,
                                                'value' => $attributeValue['value'],
                                            );
                                        }
                                    }
                                }

                                $data['attributes'][$attributekey] = $attribute;
                            }
                            
                            $indexedData[$mainkey] = $data;                            
                        }
                    }

                    $qb = $this->em->getRepository('YilinkerCoreBundle:'.ucfirst($mappingKey))
                               ->createQueryBuilder('x');                    

                    $entities = array();
                    if(count($indexedData)){
                        $qb->where($qb->expr()->in('x.'.$tableKey, array_keys($indexedData) ));
                        $entities = $qb->getQuery()->getResult();
                    }

                    /**
                     * Update existing entities
                     */
                    foreach($entities as $entity){                                               
                        $referenceNumber = call_user_func(array($entity, 'get'.ucfirst($tableKey)) );

                        $updatedData = $indexedData[$referenceNumber];
                        $this->setEntityFields($entity, $updatedData, $mappingData);
                        call_user_func(array($entity, 'set'.ucfirst($updateDateField)),  $currentDatetime); 
                        $syncCounter++;                        
                        unset($indexedData[$referenceNumber]);

                        if($mappingKey === "manufacturerProduct" && $entity instanceof ManufacturerProduct){
                            $this->cleanupOldAttributes($entity);
                        }
                    }
                    
                    /** 
                     * Create new entities
                     */
                    $entityInfo = $this->em->getClassMetadata("YilinkerCoreBundle:".ucfirst($mappingKey));

                    foreach($indexedData as $newData){
                        $newEntity = new $entityInfo->name;
                        call_user_func(array($newEntity, 'set'.ucfirst($tableKey)), $newData[$apiKey]);
                        call_user_func(array($newEntity, 'set'.ucfirst($createDateField)),  $currentDatetime);
                        call_user_func(array($newEntity, 'set'.ucfirst($updateDateField)),  $currentDatetime);
                        $this->em->persist($newEntity);
                        $this->setEntityFields($newEntity, $newData, $mappingData);
                        if(isset($mappingData['flushOnCreate']) && $mappingData['flushOnCreate'] === true){
                            $this->em->flush();
                        }
                        $syncCounter++;
                    }
                    
                    /**
                     * Create access log
                     */ 
                    if($syncCounter > 0){
                        $response['message'] = $syncCounter." ".$mappingKey."s successfully synched";
                        $this->createApiAccessLog(constant($mappingData['api_type']), $apiResponse['message']);
                        $this->em->flush();
                        $response['isSuccessful'] = true;
                    }
                    else{
                        $response['message'] = "Received ".$mappingKey." data but no synchable data found";
                    }
                }
                else{                    
                    $response['message'] = "API Endpoint is currently unavailable";
                }
            }
            catch(RequestException $e){
                $response['message'] = "Trading API has timed-out";
            }
        }

        return $response;
    }

    /**
     * Create an API Access Log Entry
     */
    private function createApiAccessLog($type, $data)
    {
        $dateNow = new \DateTime();
        $newApiAccessLog = new ApiAccessLog();
        $newApiAccessLog->setDateAdded($dateNow);
        $newApiAccessLog->setApiType($type);
        $newApiAccessLog->setData("Received data");
        $this->em->persist($newApiAccessLog);

        return $newApiAccessLog;
    }
    
    /**
     * Determine the sql offset
     *
     * @param int $limit
     * @param int $page
     * @param return int
     */
    private function getOffset($limit = 10, $page = 0)
    {
        if((int)$page > 1){
            return (int)$limit * ((int)$page-1);
        }

        return 0;
    }

    /**
     * Set the entity fields
     * 
     * @param Entity $entity
     * @param mixed $fieldData
     * @param mixed $mappingData
     */
    private function setEntityFields($entity, $fieldData, $mappingData)
    {                
        if(isset($mappingData['fields']) && is_array($mappingData['fields'])){
            foreach($mappingData['fields'] as $key => $field){

                if(in_array($key, $this->ignoreFields)){
                    continue;
                }
                
                $options = isset($field['options']) ? $field['options'] : array();
                $type = isset($field['type']) ? $field['type'] : null;
                $fieldValue = isset($field['apiField']) && isset($fieldData[$field['apiField']]) ? $fieldData[$field['apiField']] : null;
                
                if($fieldValue === null && isset($field['default'])){
                    $fieldValue = $field['default'];
                }

                if(isset($field['relation'])){
                    if(strpos($field['apiField'], self::ANNOTATION_FIXED_REFERENCE) !== false){
                        $fieldValue = $this->fixedReferences[$field['apiField']];
                    }
                    else{
                        $fieldValue = $this->em->getRepository('YilinkerCoreBundle:'.ucfirst($field['relation']['entity']))
                                           ->findOneBy(array($field['relation']['field'] => $fieldValue));
                        if($fieldValue === null && isset($field['default'])){
                            $fieldValue = $this->em->getRepository('YilinkerCoreBundle:'.ucfirst($field['relation']['entity']))
                                               ->findOneBy(array($field['relation']['field'] => $field['default']));
                        }
                    }
                }

                if($type === self::FIELD_TYPE_HTML){
                    $htmlString = html_entity_decode($fieldValue);
                    if($htmlString){
                        $dom = new DomDocument;
                        @$dom->loadHTML($htmlString);
                        $images = $dom->getElementsByTagName('img');
                        foreach ($images as $image){
                            $src = $image->getAttribute('src');
                            $url = parse_url($src);
                            if(isset($url['host']) === false){                            
                                $image->setAttribute('src', $this->configuration[$options['imageUrl']].$url['path']);
                            }
                        }
                        $fieldValue = $dom->saveHTML();
                    }
                }
                else if($type === self::FIELD_TYPE_IMAGE){
                    $imagebaseurl = $this->configuration['imageurl'];
                    $parent = call_user_func(array($entity, 'get'.ucfirst($mappingData['joinOn'])));
                    $parentId = call_user_func(array($parent, 'get'.ucfirst($mappingData['joinOn']).'Id')); 
                    if(!$parentId){
                        /**
                         * Persist to database to generate primary key
                         */
                        $this->em->flush();
                        $parentId = call_user_func(array($parent, 'get'.ucfirst($mappingData['joinOn']).'Id')); 
                    }
                    
                    $uploadDir = $options['webpath'].DIRECTORY_SEPARATOR.$parentId;
                    $absoluteDir = $this->kernelRootDirectory."/../../".$uploadDir;
                    if (!file_exists($absoluteDir)) {
                        mkdir($absoluteDir, 0777, true);
                    }

                    $content = file_get_contents($imagebaseurl.DIRECTORY_SEPARATOR.$fieldValue);
                    $filename = $fieldValue;

                    if(isset($options['filename_segment_number'])){
                        $explodedImagepath = explode("/",$fieldValue);
                        $filename = $explodedImagepath[$options['filename_segment_number']];
                        $fieldValue = $filename;
                    }
                    $fullpath = $absoluteDir.DIRECTORY_SEPARATOR.$filename;
                    file_put_contents($fullpath, $content);
                    echo "\nImage File uploaded: ".$fullpath;
                    if(isset($options['resize']) && $options['resize']){
                        $imageManipulator = $this->imageManipulator;
                        $imagesize = getimagesize($fullpath);

                        $explodedDirectory = explode("/", $uploadDir);
                        array_shift($explodedDirectory);
                        $assetDir = implode("/",$explodedDirectory);
                        $width = (int)$imagesize[0];
                        $width = $width > ProductFileUploader::SIZE_LARGE_WIDTH ? ProductFileUploader::SIZE_LARGE_WIDTH : $width;
                        $height = (int)$imagesize[1];
                        $height = $height > ProductFileUploader::SIZE_LARGE_HEIGHT ? ProductFileUploader::SIZE_LARGE_HEIGHT : $height;
                        $imageManipulator->writeThumbnail(
                            $assetDir.DIRECTORY_SEPARATOR.$filename,
                            $assetDir.DIRECTORY_SEPARATOR.$filename, 
                            array(
                                "filters" => array(
                                    "thumbnail" => array(
                                        "size" => array($width, $height)
                                    ),
                                ),
                            )
                        );
                    }

                    /**
                     * Upload file to the cloud
                     */
                    $adapter = $this->photoFilesystem->getAdapter();
                    if ($adapter instanceof AwsS3) {
                        $file = new File($fullpath);
                        $adapter->setMetadata($assetDir.DIRECTORY_SEPARATOR.$filename, array('contentType' => $file->getMimeType()));
                        $adapter->write($assetDir.DIRECTORY_SEPARATOR.$filename, file_get_contents($file->getPathname()));

                        /**
                         * Remove file from local file system
                         */
                        $fs = new fs();
                        $fs->remove(array($file));                
                    }
                }                
                call_user_func(array($entity, 'set'.ucfirst($key)), $fieldValue);
            }
        }       

        if(isset($mappingData['subTables']) && is_array($mappingData['subTables'])){
            foreach($mappingData['subTables'] as $subtable => $subtableData){

                if(in_array($subtable, $this->ignoreFields)){
                    continue;
                }
                
                $subEntityName = "Yilinker\\Bundle\\CoreBundle\\Entity\\".ucfirst($subtable);
                
                $isArray = isset($subtableData['isArray']['value']) && $subtableData['isArray']['value'];
                if($isArray){
                    $iterableFields = $fieldData[$subtableData['isArray']['apiField']];
                    if(is_array($iterableFields)){
                        foreach($iterableFields as $iterableField){                            
                            $subEntity = null;
                            if(isset($subtableData['key'])){
                                $qb = $this->em->getRepository($subEntityName)
                                           ->createQueryBuilder('x');
                                $searchKey = is_array($iterableField) ? $iterableField[$subtableData['key']['apiField']] : $iterableField;
                                $qb->where($qb->expr()->eq('x.'.$subtableData['key']['tableKey'], ":searchkey"));
                                $qb->setParameter("searchkey", $searchKey);
                                $subEntity = $qb->getQuery()->getOneOrNullResult();

                                if($subEntity === null && isset($subtableData['skipNull']) && $subtableData['skipNull']){
                                    continue;
                                }
                            }

                            if($subEntity === null){
                                $subEntity = new $subEntityName;
                            }
                            else{
                                /**
                                 * If entity already exists and isUpdateable is false, skip
                                 */
                                $isUpdateable = true;
                                if(isset($subtableData['isUpdateable'])){
                                    $isUpdateable = $subtableData['isUpdateable'];
                                }
                                
                                if($isUpdateable === false){                                    
                                    continue;
                                }
                            }
                            
                            call_user_func(array($subEntity, 'set'.ucfirst($subtableData['joinOn'])), $entity);
                            
                            $this->setEntityFields($subEntity, $iterableField, $subtableData); 
                            $this->em->persist($subEntity);
                            if(isset($subtableData['flushOnCreate']) && $subtableData['flushOnCreate'] === true){
                                $this->em->flush();
                            }
                        }
                    }
                }
                else{                    

                    $subEntity = null;
                    if(isset($subtableData['key'])){
                        $qb = $this->em->getRepository($subEntityName)
                                   ->createQueryBuilder('x');

                        if(isset($subtableData['key']['apiField'])){
                            $qb->where($qb->expr()->eq('x.'.$subtableData['key']['tableKey'], $fieldData[$subtableData['key']['apiField']]));
                            $subEntity = $qb->getQuery()->getOneOrNullResult();
                        }
                        else if(isset($subtableData['key']['useParent']) && $subtableData['key']['useParent']){
                            $meta = $this->em->getClassMetadata(get_class($entity));  
                            $primaryKey =$meta->getSingleIdentifierFieldName();
                            $primaryKeyValue = call_user_func(array($entity, 'get'.ucfirst($primaryKey)));
                            if($primaryKeyValue !== null){
                                $qb->where($qb->expr()->eq('x.'.$subtableData['joinOn'], $primaryKeyValue));
                                $qb->setMaxResults(1);
                                $subEntity = $qb->getQuery()->getOneOrNullResult();
                            }
                            else{
                                $subEntity = null;
                            }
                        }
                        else{
                            throw new Exception('Table key for subtable join is not set');
                        }

                    }

                    if($subEntity === null){
                        $subEntity = new $subEntityName;
                    }
                    else{
                        $isUpdateable = true;
                        if(isset($subtableData['isUpdateable'])){
                            $isUpdateable = $subtableData['isUpdateable'];
                        }
                        
                        if($isUpdateable === false){
                            continue;
                        }
                    }

                    call_user_func(array($subEntity, 'set'.ucfirst($subtableData['joinOn'])), $entity);                    
                    $this->setEntityFields($subEntity, $fieldData, $subtableData);
                    $this->em->persist($subEntity);
                    if(isset($subtableData['fixedReference'])){
                        $this->fixedReferences[$subtableData['fixedReference']['name']] = $subEntity;
                    }
                    if(isset($subtableData['flushOnCreate']) && $subtableData['flushOnCreate'] === true){
                        $this->em->flush();
                    }
                }
            }
        }
    }

    /**
     * Clean old attributes
     *
     * @param ManufacturerProduct $manufacturerProduct
     */
    function cleanupOldAttributes(ManufacturerProduct $manufacturerProduct)
    {
        $pattern = '/^[\w]+\[[\w-]+\]$/';
        $attributeNames = $manufacturerProduct->getManufacturerProductAttributeNames();
        foreach($attributeNames as $attributeName){
            $attributeValues = $attributeName->getManufacturerProductAttributeValues();
            foreach($attributeValues as $key => $attributeValue){
                $referenceId = $attributeValue->getReferenceId();
                if(preg_match($pattern, $referenceId) === 0){
                    
                    $this->em->remove($attributeValue);
                    unset($attributeValues[$key]);
                }
            }            
            $this->em->flush();
            if(count($attributeValues) === 0 && ($attributeName->getReferenceId() === null || trim($attributeName->getReferenceId()) === "")){
                $this->em->remove($attributeName);
            }            
            $this->em->flush();            
        }
    }

}
