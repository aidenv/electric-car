<?php

namespace Yilinker\Bundle\BackendBundle\Controller\Api;

use Exception;
use DateTime;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Entity\Language;

class TradingApiController extends Controller
{
    /**
     * Returns the list of supplier products
     *
     * @ApiDoc(
     *  description="Returns the list of supplier products",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="limit", "dataType"="integer", "required"="false", "description"="Result per page limit"},
     *      {"name"="page", "dataType"="integer", "required"="false", "description"="Current page"},
     *      {"name"="b_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="Start date filter"},
     *      {"name"="e_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="End date filter"},
     *  },
     *  views={"trading"},
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when request is successful",
     *     400="Returned when there are Invalid parameters"
     *  }
     * )
     */
	public function getProductsAction(Request $request)
	{
		$limit = $request->get("limit", 10);
		$page = $request->get("page", 1);
		$beginDate = $request->get("b_date", null);
		$endDate = $request->get("e_date", null);

		$dateError = new JsonResponse(array(
			"isSuccessful"	=> false,
			"message"		=> "Invalid Date Please use Y-m-d H:i:s format",
			"data"			=> array(),
		), 400);

        if(!is_null($beginDate)){
            try{
                $beginDate = Carbon::createFromFormat("Y-m-d H:i:s", $beginDate)->format(DateTime::ATOM);
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

		if(!is_null($endDate)){
			try{
				$endDate = Carbon::createFromFormat("Y-m-d H:i:s", $endDate)->format(DateTime::ATOM);
			}
			catch(Exception $e){
				return $dateError;	
			}
		}

		$tradingService = $this->get("yilinker_core.import_export.yilinker.trading");
		$productCollection = $tradingService->getProducts($page, $limit, $beginDate, $endDate);

		return new JsonResponse(array(
			"isSuccessful"	=> true,
			"totalPage"		=> $productCollection["totalPage"],
			"nowPage"		=> $page,
			"message"		=> "Product Listing.",
			"data"			=> $productCollection["products"]
		), 200);
	}

    /**
     * Returns the sales list
     *
     * @ApiDoc(
     *  description="Returns the sales list",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="limit", "dataType"="integer", "required"="false", "description"="Result per page limit"},
     *      {"name"="page", "dataType"="integer", "required"="false", "description"="Current page"},
     *      {"name"="b_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="Start date filter"},
     *      {"name"="e_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="End date filter"},
     *  },
     *  views={"trading"},
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when request is successful",
     *     400="Returned when there are Invalid parameters"
     *  }
     * )
     */
	public function getOrdersAction(Request $request)
	{
		$limit = $request->get("limit", 10);
		$page = $request->get("page", 1);
		$beginDate = $request->get("b_date", null);
		$endDate = $request->get("e_date", null);

		$dateError = new JsonResponse(array(
			"isSuccessful"	=> false,
			"message"		=> "Invalid Date Please use Y-m-d H:i:s format",
			"data"			=> array()
		), 400);

        if(!is_null($beginDate)){
            try{
                $beginDate = Carbon::createFromFormat("Y-m-d H:i:s", $beginDate)->toDateTimeString();
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        if(!is_null($endDate)){
            try{
                $endDate = Carbon::createFromFormat("Y-m-d H:i:s", $endDate)->toDateTimeString();
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

		$tradingService = $this->get("yilinker_core.import_export.yilinker.trading");
		$orderCollection = $tradingService->getOrders($page, $limit, $beginDate, $endDate);

		return new JsonResponse(array(
			"isSuccessful"	=> true,
			"totalPage"		=> $orderCollection["totalPage"],
			"nowPage"		=> $page,
			"message"		=> "Order Listing.",
			"data"          => $orderCollection["orders"]
		), 200);
	}

    /**
     * Returns the sales detail list
     *
     * @ApiDoc(
     *  description="Returns the sales list",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="limit", "dataType"="integer", "required"="false", "description"="Result per page limit"},
     *      {"name"="page", "dataType"="integer", "required"="false", "description"="Current page"},
     *      {"name"="b_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="Start date filter"},
     *      {"name"="e_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="End date filter"},
     *  },
     *  views={"trading"},
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when request is successful",
     *  }
     * )
     */
    public function getOrderProductsAction(Request $request)
    {
        $limit = $request->get("limit", 10);
		$page = $request->get("page", 1);
		$beginDate = $request->get("b_date", null);
		$endDate = $request->get("e_date", null);

		$dateError = new JsonResponse(array(
			"isSuccessful"	=> false,
			"message"		=> "Invalid Date Please use Y-m-d H:i:s format",
			"data"			=> array()
		), 400);

        if(!is_null($beginDate)){
            try{
                $beginDate = Carbon::createFromFormat("Y-m-d H:i:s", $beginDate)->toDateTimeString();
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        if(!is_null($endDate)){
            try{
                $endDate = Carbon::createFromFormat("Y-m-d H:i:s", $endDate)->toDateTimeString();
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

		$tradingService = $this->get("yilinker_core.import_export.yilinker.trading");
		$orderProductCollection = $tradingService->getOrderProducts($page, $limit, $beginDate, $endDate);

		return new JsonResponse(array(
			"isSuccessful"	=> true,
			"totalPage"		=> $orderProductCollection["totalPage"],
			"nowPage"		=> $page,
			"message"		=> "Order Product Listing.",
			"data"          => $orderProductCollection["orderProducts"]
		), 200);
    }

    /**
     * Returns quantity of a certain sku
     *
     * @ApiDoc(
     *  description="Returns the sales list",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="sku_no", "dataType"="string", "required"="true", "description"="The product SKU number"},
     *      {"name"="supplier_id", "dataType"="integer", "required"="true", "description"="The supplier ID"},
     *  },
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when SKU is found",
     *     404="Returned when the SKU is not found"
     *  },
     *  views={"trading"},
     * )
     */
    public function getProductUnitAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
    	$sku = $request->get("sku_no", null);
    	$manufacturerReferenceNumber = $request->get("supplier_id", null);

    	$manufacturerProductUnitRepository = $em->getRepository('YilinkerCoreBundle:ManufacturerProductUnit');
    	$productUnit = $manufacturerProductUnitRepository->getProductUnitBySkuManufacturer($sku, $manufacturerReferenceNumber);

    	if(is_null($productUnit)){
    		return new JsonResponse(array(
    			"isSuccessful" => false,
    			"message" => "Manufacturer product unit not found",
    			"data" => array()
			), 404);
    	}

    	return new JsonResponse(array(
    		"isSuccessful" => true,
    		"message" => "Manufacturer Product Unit",
    		"data" => array(
    			"qty" => (int)$productUnit->getQuantity(),
    			"price" => floatval($productUnit->getPrice())
			)
		), 200);
    }
    
    /**
     * Retrieves the supplier balance
     *
     * @ApiDoc(
     *  description="Retrieves the supplier available balance",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="supplier_id", "dataType"="string", "required"="true", "description"="Supplier ID"},
     *  },
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when Supplier is found",
     *     404="Returned when the supplier is not found"
     *  },
     *  views={"trading"},
     *  tags={"wallet", "development"}
     * )
     */
    public function getSupplierBalanceAction(Request $request)
    {
        /**
         * TODO: Access Yilinker Wallet API
         */      
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Supplier not found',
            'data'         => array(),
        );
        $statusCode = 404;

        $supplierId = $request->get('supplier_id');
        $em = $this->getDoctrine()->getManager();
        $supplier = $em->getRepository('YilinkerCoreBundle:Manufacturer')
                       ->findOneBy(array(
                           'referenceId' => $supplierId,
                       ));
        if($supplier){
            $response['isSuccessful'] = true;
            $currencyCode = $this->container->getParameter('default_currency_code');
            $currency = $em->getRepository('YilinkerCoreBundle:Currency')
                           ->findOneBy(array(
                               'code' => $currencyCode,
                           ));
            $response['message'] = "Account Balance successfully retrieved";
            $response['data'] = array(
                'available_balance' => "1,000.00",
                'currency_name'     => $currency->getName(),
                'currency_code'     => $currency->getCode(),
                'currency_symbol'   => $currency->getSymbol(),
            );
            $statusCode = 200;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Supplier transfer
     *
     * @ApiDoc(
     *  description="Request supplier transfer",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="supplier_id", "dataType"="string", "required"="true", "description"="Supplier ID"},
     *      {"name"="cash_amount", "dataType"="string", "required"="true", "description"="Amount", "format"="1000.00" },
     *  },
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when Supplier is found",
     *     404="Returned when the supplier is not found"
     *  },
     *  views={"trading"},
     *  tags={"wallet", "development"}
     * )
     */
    public function requestSupplierBalanceTransferAction(Request $request)
    {
        /**
         * TODO: Access Yilinker Wallet API
         */        
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Supplier not found',
            'data'         => array(),
        );
        $statusCode = 404;

        $supplierId = $request->get('supplier_id');
        $amount = $request->get('cash_amount', null);
        $em = $this->getDoctrine()->getManager();
        $supplier = $em->getRepository('YilinkerCoreBundle:Manufacturer')
                       ->findOneBy(array(
                           'referenceId' => $supplierId,
                       ));
        if($supplier){
            $statusCode = 200;            
            $availableBalance = "1000.00";
            $currencyCode = $this->container->getParameter('default_currency_code');
            $currency = $em->getRepository('YilinkerCoreBundle:Currency')
                           ->findOneBy(array(
                               'code' => $currencyCode,
                           ));
            
            if($amount !== null && is_numeric($amount)){
                if(bccomp($availableBalance, $amount) > 0){
                    $response['isSuccessful'] = true;
                    $response['data'] = array(
                        'transactionNumber' => time(),
                        'amount'            => number_format($amount, 2, '.', ','),
                        'currency_name'     => $currency->getName(),
                        'currency_code'     => $currency->getCode(),
                        'currency_symbol'   => $currency->getSymbol(),
                    );
                    $response['message'] = "Amount successfully transferred to YLT Wallet account";
                }
                else{
                    $response['message'] = "Supplier balance is less than requested amount";
                    $response['data'] = array(
                        'available_balance' => number_format($availableBalance, 2, '.', ','),
                    );
                }
            }
            else{
                $response['message'] = "Amount is required.";
            }
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Supplier Collection
     *
     * @ApiDoc(
     *  description="Supplier Collection",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="supplier_id", "dataType"="string", "required"="true", "description"="Supplier ID"},
     *      {"name"="b_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="Start date filter"},
     *      {"name"="e_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="End date filter"},
     *      {"name"="limit", "dataType"="integer", "required"="false", "description"="Result per page limit"},
     *      {"name"="page", "dataType"="integer", "required"="false", "description"="Current page"},
     *  },
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when Supplier is found",
     *     404="Returned when the supplier is not found",
     *     400="Returned when there are Invalid parameters"
     *  },
     *  views={"trading"},
     *  tags={"wallet", "express", "development"}
     * )
     */
    public function getSupplierCollectionAction(Request $request)
    {
        /**
         * TODO: Access Yilinker Wallet API
         */        
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Supplier not found',
            'data'         => array(),
        );
        $statusCode = 404;

        $supplierId = $request->get('supplier_id');
        $limit = $request->get("limit", 10);
		$page = $request->get("page", 1);
		$beginDate = $request->get("b_date", null);
		$endDate = $request->get("e_date", null);

		$dateError = new JsonResponse(array(
			"isSuccessful"	=> false,
			"message"		=> "Invalid Date Please use Y-m-d H:i:s format",
			"data"			=> array()
		), 400);

		if(!is_null($beginDate)){
            try{
                $beginDate = Carbon::createFromFormat("Y-m-d H:i:s", $beginDate)->format(DateTime::ATOM);
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        if(!is_null($endDate)){
            try{
                $endDate = Carbon::createFromFormat("Y-m-d H:i:s", $endDate)->format(DateTime::ATOM);
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        $em = $this->getDoctrine()->getManager();
        $supplier = $em->getRepository('YilinkerCoreBundle:Manufacturer')
                       ->findOneBy(array(
                           'referenceId' => $supplierId,
                       ));
        if($supplier){
            $statusCode = 200;
            $currencyCode = $this->container->getParameter('default_currency_code');
            $currency = $em->getRepository('YilinkerCoreBundle:Currency')
                           ->findOneBy(array(
                               'code' => $currencyCode,
                           ));
            $response['isSuccessful'] = true;
            $response['message'] = "Results retrieved";
            $response['data'] = array(
                'total_delivery_non_settlement' => '1000.00',
                'total_settlement_not_paid' => '1000.00',
                'currency_name'     => $currency->getName(),
                'currency_code'     => $currency->getCode(),
                'currency_symbol'   => $currency->getSymbol(),
                'collections'       => array(
                    array(
                        "po_no" => "PO-YLT-200001",
                        "type"  => "buyout",
                        "unsettled_amount" => "1000.00",
                        "unpaid_amount"    => "1000.00" ,
                    ),
                ),
             );
        }

        return new JsonResponse($response, $statusCode);        
    }
    
    /**
     * Category List
     *
     * @ApiDoc(
     *  description="Category List",
     *  resource=true,
     *  parameters={
     *      {"name"="access_token", "dataType"="string", "required"="true", "description"="Oauth2 Access Token"},
     *      {"name"="b_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="Start date filter"},
     *      {"name"="e_date", "dataType"="string", "format"="YYYY-MM-DD H:i:s", "required"="false", "description"="End date filter"},
     *      {"name"="limit", "dataType"="integer", "required"="false", "description"="Result per page limit"},
     *      {"name"="page", "dataType"="integer", "required"="false", "description"="Current page"},
     *      {"name"="parent_id", "dataType"="integer", "required"="false", "description"="Category parent id"},
     *  },
     *  statusCodes={
     *     403="Oauth2 Returned when the access token is invalid",
     *     200="Returned when Supplier is found",
     *     404="Returned when the supplier is not found",
     *     400="Returned when there are Invalid parameters"
     *  },
     *  views={"trading"},
     * )
     */
    public function getCategoriesAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Supplier not found',
            'data'         => array(),
        );
        $statusCode = 404;
        
        $parentId = $request->get('parent_id', null);
        $limit = $request->get("limit", 10);
        $page = $request->get("page", 1);
        $offset = ($page - 1) * $limit;
        $beginDate = $request->get("b_date", null);
        $endDate = $request->get("e_date", null);

        $dateError = new JsonResponse(array(
            "isSuccessful"	=> false,
            "message"		=> "Invalid Date Please use Y-m-d H:i:s format",
            "data"			=> array()
        ), 400);

        if(!is_null($beginDate)){
            try{
                $beginDate = Carbon::createFromFormat("Y-m-d H:i:s", $beginDate);
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        if(!is_null($endDate)){
            try{
                $endDate = Carbon::createFromFormat("Y-m-d H:i:s", $endDate);
            }
            catch(Exception $e){
                return $dateError;	
            }
        }

        $em = $this->getDoctrine()->getManager();
        $chineseLanguage = $em->getReference('YilinkerCoreBundle:Language', Language::CHINESE);
        $productCategories = $em->getRepository('YilinkerCoreBundle:ProductCategory')
                                ->searchCategory(
                                    $parentId, $limit, $offset, 
                                    null, $beginDate, $endDate,
                                    true, true, $chineseLanguage
                                );

        $resultCount = $em->getRepository('YilinkerCoreBundle:ProductCategory')
                          ->getCategoryCountBy(
                              $parentId, null, $beginDate, $endDate, $chineseLanguage
                          );
        
        $response['total'] = ceil($resultCount/$limit);
        $response['nowPage'] = $page;
        $statusCode = 200;
        $response['isSuccessful'] = true;
        $response['message'] = "Results retrieved";
        $response['data'] = array();
        foreach($productCategories as $productCategory){                
            $chineseTranslation = $productCategory->getProductCategoryTranslation($chineseLanguage);
            $nestedSet = $productCategory->getCategoryNestedSet();
            $response['data'][] = array(
                "system_category_id"              => $productCategory->getReferenceNumber(),
                "system_category_parent_id"       => $productCategory->getParent() ? $productCategory->getParent()->getReferenceNumber() : null,
                "system_category_name_US"         => $productCategory->getName(),
                "system_category_description_US"  => $productCategory->getDescription(),
                "system_category_name_CN"         => $chineseTranslation ? $chineseTranslation->getName() : '',
                "system_category_description_CN"  => $chineseTranslation ? $chineseTranslation->getDescription() : '',
                "nested_set_right"                => $nestedSet ? $nestedSet->getRight() : null,
                "nested_set_left"                 => $nestedSet ? $nestedSet->getLeft() : null,
            );
        }
        
        return new JsonResponse($response, $statusCode);
    }

    
}
