<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;
use Doctrine\Common\Collections\Criteria;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProductImage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\Debug;

/**
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_PRODUCT_SPECIALIST') or has_role('ROLE_EXPRESS_OPERATIONS')")
 */
class InhouseController extends Controller
{
	const LIMIT = 30;

    public function listAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
    	$page = $request->get("page", 1);
        $status = $request->get("status", null);
        $sortType = $request->get("sortType", ProductRepository::ALPHABETICAL);
        $brand = $request->get("brand", null);
        $category = $request->get("category", null);
        $queryString = $request->get("q", null);
        $priceFrom = $request->get("priceFrom", null);
        $priceTo = $request->get("priceTo", null);
        $manufacturer = $request->get('manufacturer', null);

        $brand = (is_null($brand) || $brand === "")? array() : array($brand);
        $nestedSetRepository = $em->getRepository('YilinkerCoreBundle:CategoryNestedSet');
        $categories = $em->getRepository('YilinkerCoreBundle:ProductCategory')
                         ->getMainCategories();

        $filteredCategories = array();
        if($category !== null){
            $criteria = Criteria::create()->where(Criteria::expr()->in('productCategoryId', array($category)));
            if($categories instanceof ArrayCollection === false){
                $categories =  new ArrayCollection($categories);
            }
            $filteredCategories = $categories->matching($criteria);            
        }        
        $categoryIdsFilter = array();
        foreach($filteredCategories as $filterCategory){
            $categoryId = $filterCategory->getProductCategoryId();
            $childrenIds = $nestedSetRepository->getChildrenCategoryIds($categoryId);
            $categoryIdsFilter[] = $categoryId;
            $categoryIdsFilter = array_merge($categoryIdsFilter, $childrenIds);
        }

        $limit = self::LIMIT;

        $statuses = array(
                        Product::ACTIVE, 
                        Product::INACTIVE
                    );

        if($status == Product::ACTIVE && $status !== "" && !is_null($status)){
            $statuses = array(Product::ACTIVE);
        }
        elseif($status == Product::INACTIVE && $status !== "" && !is_null($status)){
            $statuses = array(Product::INACTIVE);
        }

        $productSearchService = $this->get("yilinker_core.service.search.product");
        $aggregations = $productSearchService->getAggregations();
        $brands = $em->getRepository('YilinkerCoreBundle:Brand')->getBrandsByIds($aggregations["brandIds"]);

        $productsData = $productSearchService->searchProductsWithElastic(
                            $queryString,
                            $priceFrom,
                            $priceTo,
                            empty($categoryIdsFilter) ? null : $categoryIdsFilter,
                            null,
                            empty($brand)? null : $brand,
                            null,
                            $sortType,
                            null,
                            array(),
                            $page, 
                            self::LIMIT,
                            true,
                            true,
                            array(),
                            null,
                            null,
                            $statuses,
                            true,
                            null,
                            null,
                            null,
                            array(),
                            true,
                            null,
                            $manufacturer ? $manufacturer: array()
                        );
        
        if ($manufacturer) {
            $tbManufacturer = $em->getRepository('YilinkerCoreBundle:Manufacturer');
            $manufacturer = $tbManufacturer->find($manufacturer);
        }

        return $this->render('YilinkerBackendBundle:InHouse:inhouse_base.html.twig', compact(
            "productsData", 
            "limit",
            "brands",
            "categories",
            "manufacturer"
        ));
    }

    public function formAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbInhouseProduct = $em->getRepository('YilinkerCoreBundle:InhouseProduct');
        $inhouseProductId = $request->get('inhouseProductId', null);
        $inhouseProduct = $inhouseProductId ? $tbInhouseProduct->find($inhouseProductId): null;
//        Debug::dump($inhouseProduct);EXIT;
        $form = $this->createForm('inhouse_product', $inhouseProduct);
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em->flush();
            }

            $photoImages = $form['photoImages']->getData();

            $photoImages = explode(',', $photoImages);
            $inhouseProduct = $form->getData();
            $manufacturerProductService = $this->get('yilinker_core.service.manufacturer_product.manufacturer_product_service');
            $manufacturerProductService->syncImages($inhouseProduct, $photoImages);
        }
        $form = $form->createView();
        $data = compact('form');

        return $this->render('YilinkerBackendBundle:InHouse:inhouse_form.html.twig', $data);
    }

    public function updateManufacturerUnitsAction(Request $request)
    {
        $units = json_decode($request->get("units", "[]"), true);

        $unitIds = array_keys($units);
        $em = $this->getDoctrine()->getManager();
        $manufacturerProductUnits = $em->getRepository('YilinkerCoreBundle:ManufacturerProductUnit')
                                       ->getManufacturerProductUnitsIn($unitIds);

        $manufacturerProductService = $this->get("yilinker_core.service.manufacturer_product.manufacturer_product_service");

        $manufacturerProduct = null;

        $errors = array();

        if(!empty($manufacturerProductUnits)){

            $em->beginTransaction();

            try{
                foreach($manufacturerProductUnits as $manufacturerProductUnit){

                    $manufacturerProductUnitId = $manufacturerProductUnit->getManufacturerProductUnitId();

                    if(array_key_exists($manufacturerProductUnitId, $units)){
                        $shippingFee = floatval($units[$manufacturerProductUnitId]["shippingFee"]);
                        $manufacturerProductUnit->setShippingFee(number_format($shippingFee, 2, '.', ''));

                        if(
                            $units[$manufacturerProductUnitId]["retailPrice"] != ""  || 
                            floatval($units[$manufacturerProductUnitId]["retailPrice"]) > 1
                        ){
                            $retailPrice = floatval($units[$manufacturerProductUnitId]["retailPrice"]);
                            $manufacturerProductUnit->setRetailPrice(number_format($retailPrice, 2, '.', ''));
                        }
                        else{
                            array_push($errors, "Retail Price for SKU:".$manufacturerProductUnit->getSku()." is invalid.");
                            $manufacturerProductUnit->setRetailPrice(null);
                        }

                        if($units[$manufacturerProductUnitId]["commission"] != ""){
                            $commission = floatval($units[$manufacturerProductUnitId]["commission"]);
                            $manufacturerProductUnit->setCommission(number_format($commission, 2, '.', ''));
                        }
                        else{
                            $manufacturerProductUnit->setCommission(null);
                        }
                    }
                }

                if(!empty($manufacturerProductUnits) && $manufacturerProductUnits[0]){
                    $manufacturerProduct = $manufacturerProductService->constructManufacturerProduct($manufacturerProductUnits[0]->getManufacturerProduct());
                }

                if(!empty($errors)){
                    throw new Exception("Error Processing Request", 1);
                }

                $em->flush();
                $em->commit();

                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Shipping fee updated",
                    "data" => array(
                        "manufacturerProduct" => $manufacturerProduct
                    )
                ), 200);
            }
            catch(Exception $e){
                $em->rollback();
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Shipping fee update failed",
                    "data" => array(
                        "errors" => $errors
                    )
                ), 400);
            }
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "No units found",
                "data" => array(
                    "errors" => array("No units found")
                )
            ), 400);
        }
    }

    private function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }
}
