<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use DateTime;
use Exception;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\PromoInstance;
use Yilinker\Bundle\CoreBundle\Traits\SlugHandler;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;

/**
 * Class PromoManagementController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_MARKETING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class PromoManagementController extends Controller
{
    use SlugHandler;
    use FormHandler;

    public function renderPromoInstanceListAction(Request $request)
    {
        $dateFrom = $request->get("dateFrom", null);
        $dateTo = $request->get("dateTo", null);
        $keyword = $request->get("keyword", null);
        $page = (int)$request->get("page", 1);

        $dateFrom = $this->validateDate($dateFrom) && ($dateFrom != "" &&  !is_null($dateFrom))? $dateFrom : null;
        $dateTo = $this->validateDate($dateTo) && ($dateTo != "" &&  !is_null($dateTo))? $dateTo : null;

        $limit = 20;
        $offset = $this->getOffset($limit, $page);

        $em = $this->getDoctrine()->getManager();

        $promoTypeRepository        = $em->getRepository("YilinkerCoreBundle:PromoType");
        $promoInstanceRepository    = $em->getRepository("YilinkerCoreBundle:PromoInstance");
        $promoTypes                 = $promoTypeRepository->findAll();
        $promos                     = $promoInstanceRepository->loadPromoInstances($keyword, $dateFrom, $dateTo, $limit, $offset, true);

        $promoManager = $this->get("yilinker_core.service.promo_manager");
        $promos["instances"] = $promoManager->constructPromoInstances($promos["instances"]);

        $filters["dateFrom"]    = $dateFrom;
        $filters["dateTo"]      = $dateTo;
        $filters["keyword"]     = $keyword;

        return $this->render('YilinkerBackendBundle:PromoManagement:promo_list.html.twig', compact('promos', 'promoTypes', 'dateFrom', 'dateTo', 'keyword', 'limit'));
    }

    public function changePromoStatusAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $em = $this->getDoctrine()->getManager();
            $promoInstanceIds = $request->get("promoInstanceIds", "[]");
            $isEnabled = $request->get("isEnabled", false);

            $promoInstanceIds = json_decode($promoInstanceIds, true);

            if(empty($promoInstanceIds) || $promoInstanceIds == false){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Invalid promo.",
                    "data" => array(
                        "errors" => array("Invalid promo.")
                    )
                ), 400);
            }

            $promoManager = $this->get("yilinker_backend.promo_manager");
            $promoManager->changePromoInstanceStatus($promoInstanceIds, $isEnabled);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Success changing promo status.",
                "data" => array()
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 403);
        }
    }

    public function createPromoAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $products = $request->get("products", array());

            $em = $this->getDoctrine()->getManager();

            $postData = array(
                "title"         => $request->get("title", null),
                "promoType"     => $request->get("promoType", null),
                "advertisement" => $request->get("advertisement", null),
                "dateStart"     => $request->get("dateStart", null),
                "dateEnd"       => $request->get("dateEnd", null),
                "isEnabled"     => filter_var($request->get("isEnabled", false), FILTER_VALIDATE_BOOLEAN),
                "_token"        => $request->get("_token", null)
            );

            $form = $this->transactForm('promo', new PromoInstance(), $postData, array(
                        "promoType"         => $request->get("promoType", null),
                        "products"          => $request->get("products", array()),
                        "dateStart"         => $request->get("dateStart", null),
                        "dateEnd"           => $request->get("dateEnd", null),
                        "format"            => "m-d-Y H:i:s",
                        "excludedInstance"  => null
                    ));

            if($form->isValid()){

                $promoInstance = $form->getData();

                $promoInstance->setDateCreated(Carbon::now());
                $em->persist($promoInstance);

                $promoManager = $this->get("yilinker_backend.promo_manager");
                $promoManager->createProductPromoMaps(
                                    $promoInstance,
                                    $request->get("products", array())
                                );

                $em->flush();

                $objectPersister = $this->get("fos_elastica.object_persister.yilinker_online.product");

                $productPromoMaps = $promoInstance->getProductPromoMap();

                foreach($productPromoMaps as $productPromoMap){
                    $product = $productPromoMap->getProductUnit()->getProduct();
                    $objectPersister->insertOne($product);
                }

                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Success creating promo instance.",
                    "data" => array(
                        "promoInstance" => $promoInstance->toArray(),
                    )
                ), 200);
            }

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid inputs.",
                "data" => array(
                    "errors" => $this->getErrors($form)
                )
            ), 400);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 403);
        }
    }

    public function updatePromoAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $products = $request->get("products", array());
            $promoInstanceId = $request->get("promoInstanceId", 0);

            $em = $this->getDoctrine()->getManager();

            $promoInstanceRepository = $em->getRepository("YilinkerCoreBundle:PromoInstance");
            $promoInstance = $promoInstanceRepository->find($promoInstanceId);

            if(is_null($promoInstance)){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Unable to edit promo.",
                    "data" => array(
                        "errors" => array("Unable to edit promo.")
                    )
                ), 400);
            }

            $postData = array(
                "title"         => $request->get("title", null),
                "promoType"     => $request->get("promoType", null),
                "advertisement" => $request->get("advertisement", null),
                "dateStart"     => $request->get("dateStart", null),
                "dateEnd"       => $request->get("dateEnd", null),
                "isEnabled"     => filter_var($request->get("isEnabled", false), FILTER_VALIDATE_BOOLEAN),
                "_token"        => $request->get("_token", null)
            );

            $form = $this->transactForm('promo', $promoInstance, $postData, array(
                        "promoType"         => $request->get("promoType", null),
                        "products"          => $request->get("products", array()),
                        "dateStart"         => $request->get("dateStart", null),
                        "dateEnd"           => $request->get("dateEnd", null),
                        "format"            => "m-d-Y H:i:s",
                        "excludedInstance"  => $promoInstance
                    ));

            if($form->isValid()){

                $promoInstance = $form->getData();
                $em->flush();

                $promoManager = $this->get("yilinker_backend.promo_manager");
                $promoManager->updateProductPromoMaps($promoInstance, $products);

                $objectPersister = $this->get("fos_elastica.object_persister.yilinker_online.product");

                $productPromoMaps = $promoInstance->getProductPromoMap();

                foreach($productPromoMaps as $productPromoMap){
                    $product = $productPromoMap->getProductUnit()->getProduct();
                    $objectPersister->insertOne($product);
                }

                $promoInstance = $this->preProcessPromoInstance($promoInstance);

                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Success updating promo instance.",
                    "data" => array(
                        "promoInstance" => $promoInstance,
                    )
                ), 200);
            }

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Invalid inputs.",
                "data" => array(
                    "errors" => $this->getErrors($form)
                )
            ), 400);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 403);
        }
    }

    public function deletePromoAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $em = $this->getDoctrine()->getManager();
            $promoInstanceIds = $request->get("promoInstanceIds", "[]");

            $promoInstanceIds = json_decode($promoInstanceIds, true);

            if(empty($promoInstanceIds) || $promoInstanceIds == false){
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Invalid promo.",
                    "data" => array(
                        "errors" => array("Invalid promo.")
                    )
                ), 400);
            }

            $promoManager = $this->get("yilinker_backend.promo_manager");
            $promoManager->deletePromoInstance($promoInstanceIds);

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Success deleting promo status.",
                "data" => array()
            ), 200);
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 403);
        }
    }

    public function checkPromoProductsAction(Request $request)
    {
        $authorizationChecker = $this->get('security.authorization_checker');

        if ($authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            $em = $this->getDoctrine()->getManager();

            $productSlugs = explode("\n", $request->get("productSlugs"));

            $frontendHostName = $this->getParameter("frontend_hostname");

            $slugs = array();
            foreach($productSlugs as $slug){
                $slugs[] = $this->getLastSegment($slug);
            }
            $productSlugs = $slugs;

            $productRepository = $em->getRepository("YilinkerCoreBundle:Product");
            $products = $productRepository->getProductsBySlug($productSlugs);
            $productCollection = array();

            $translationService = $this->get("yilinker_core.translatable.listener");

            foreach($products as $product){

                $product->setLocale($product->getDefaultLocale());
                $em->refresh($product);

                $productUnits = $product->getUnits();

                foreach ($productUnits as $productUnit) {

                    $productUnit->setLocale($translationService->getCountry());
                    $em->refresh($productUnit);

                    array_push($productCollection, array(
                        "productId"         => $product->getProductId(),
                        "productUnitId"     => $productUnit->getProductUnitId(),
                        "name"              => $product->getName(),
                        "sku"               => $productUnit->getSku(),
                        "price"             => $productUnit->getPrice(),
                        "discountedPrice"   => $productUnit->getDiscountedPrice()
                    ));
                }

                $requestIndex = array_search($product->getSlug(), $productSlugs);
                unset($productSlugs[$requestIndex]);
            }

            $productSlugs = array_values($productSlugs);

            if(empty($productSlugs)){
                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Success.",
                    "data" => array(
                        "products" => $productCollection
                    )
                ), 200);
            }
            else{
                return new JsonResponse(array(
                    "isSuccessful" => false,
                    "message" => "Products doesnt exists.",
                    "data" => array(
                        "products" => $productSlugs
                    )
                ), 400);
            }
        }
        else{
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Not authorized.",
                "data" => array(
                    "errors" => array("Unauthorized.")
                )
            ), 403);
        }
    }

    private function validate($dateFrom, $dateTo, $promoInstance)
    {
        $errors = array();
        try{
            if(!is_null($dateFrom) && !is_null($dateTo)){
                $dateFrom = Carbon::createFromFormat('m-d-Y H:i:s', $dateFrom);
                $dateTo = Carbon::createFromFormat('m-d-Y H:i:s', $dateTo);

                if($dateFrom->lt($dateTo)){
                    $promoInstance->setDateStart($dateFrom)
                                  ->setDateEnd($dateTo);

                    $promoType = $promoInstance->getPromoType();

                    switch($promoType->getPromoTypeId()){
                        case 2:
                            is_null($promoInstance->getQuantityRequired())? array_push($errors, "Quantity required is invalid"):"";
                            break;
                        case 3:
                            is_null($promoInstance->getPercentPerHour())? array_push($errors, "Percent per hour is invalid"):"";
                            is_null($promoInstance->getMinimumPercentage())? array_push($errors, "Minimum percentage is invalid"):"";
                            break;
                    }
                }
            }
        }
        catch(Exception $e){
            array_push($errors, "Invalid dates.");
        }

        return $errors;
    }

    private function getOffset($limit, $page)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }

    private function checkInvalidProducts($productIds, $excludedInstance = null, $dateStart = null, $dateEnd = null)
    {
        $em = $this->getDoctrine()->getManager();
        $productUnitRepository = $em->getRepository("YilinkerCoreBundle:ProductUnit");

        $productUnits = $productUnitRepository->getPromoProductUnitsIn(
                            $productIds,
                            $excludedInstance,
                            $dateStart,
                            $dateEnd
                        );

        $invalidProducts = array();
        foreach($productUnits as $productUnit){
            $productPromoMap = $productUnit->getProductPromoMaps()->first();
            $promoInstance = $productPromoMap->getPromoInstance();
            array_push($invalidProducts, $productUnit->getProduct()->getName()." is already in a active promo (".$promoInstance->getTitle().").");
        }

        array_unique($invalidProducts);
        return $invalidProducts;
    }

    private function validateDate($date)
    {
        try{
            Carbon::createFromFormat('Y-m-d H:i:s', $date);
        }
        catch(Exception $e){
            return false;
        }

        return true;
    }

    private function getFixedDiscountPostData(Request $request)
    {
        return array(
            "_token"        => $request->get("_token", null),
            "title"         => $request->get("title", ""),
            "promoType"     => $request->get("promoType", 1),
            "advertisement" => $request->get("advertisement", ""),
            "maxPercentage" => floatval($request->get("maxPercentage", 0)),
            "isEnabled"         => filter_var($request->get("isEnabled", false), FILTER_VALIDATE_BOOLEAN),
        );
    }

    private function getBulkDiscountPostData(Request $request)
    {
        $isEnabled = $request->get("isEnabled", false) === "on"? true : false;
        return array(
            "_token"            => $request->get("_token", null),
            "title"             => $request->get("title", ""),
            "promoType"         => $request->get("promoType", 1),
            "advertisement"     => $request->get("advertisement", ""),
            "maxPercentage"     => floatval($request->get("maxPercentage", 0)),
            "isEnabled"         => filter_var($request->get("isEnabled", false), FILTER_VALIDATE_BOOLEAN),
            "quantityRequired"  => (int)$request->get("quantityRequired", 0),
        );
    }

    private function getCountdownDiscountPostData(Request $request)
    {
        return array(
            "_token"            => $request->get("_token", null),
            "title"             => $request->get("title", ""),
            "promoType"         => $request->get("promoType", 1),
            "advertisement"     => $request->get("advertisement", ""),
            "maxPercentage"     => floatval($request->get("maxPercentage", 0)),
            "isEnabled"         => filter_var($request->get("isEnabled", false), FILTER_VALIDATE_BOOLEAN),
            "minimumPercentage" => floatval($request->get("minimumPercentage", 0)),
            "percentPerHour"    => floatval($request->get("percentPerHour", 0)),
        );
    }

    private function preProcessPromoInstance($promoInstance)
    {
        $em = $this->get("doctrine.orm.entity_manager");

        $productUnits = array();
        $productPromoMaps = $promoInstance->getProductPromoMap();

        $translatableListener = $this->get("yilinker_core.translatable.listener");
        foreach($productPromoMaps as $productPromoMap){
            $productUnit = $productPromoMap->getProductUnit();
            if($productUnit){

                $productUnit->setLocale($translatableListener->getCountry());
                $em->refresh($productUnit);

                $product = $productUnit->getProduct();
                $productUnits[$productUnit->getProductUnitId()] = array(
                    "productId"         => $product->getProductId(),
                    "productUnitId"     => $productUnit->getProductUnitId(),
                    "name"              => $product->getName(),
                    "sku"               => $productUnit->getSku(),
                    "maxQuantity"       => $productPromoMap->getMaxQuantity(),
                    "price"             => $productUnit->getPrice(),
                    "discountedPrice"   => $productPromoMap->getDiscountedPrice(),
                    "minimumPercentage" => $productPromoMap->getMinimumPercentage(),
                    "maximumPercentage" => $productPromoMap->getMaximumPercentage(),
                    "percentPerHour"    => $productPromoMap->getPercentPerHour(),
                    "quantityRequired"  => $productPromoMap->getQuantityRequired(),
                );
            }
        }

        return array(
            "promoInstanceId"       => $promoInstance->getPromoInstanceId(),
            "dateStart"             => $promoInstance->getDateStart(),
            "dateEnd"               => $promoInstance->getDateEnd(),
            "title"                 => $promoInstance->getTitle(),
            "isEnabled"             => $promoInstance->getIsEnabled(),
            "promoType"             => $promoInstance->getPromoType()->toArray(),
            "dateCreated"           => $promoInstance->getDateCreated(),
            "advertisement"         => $promoInstance->getAdvertisement(),
            "isImageAdvertisement"  => $promoInstance->getIsImageAdvertisement(),
            "productUnits"          => $productUnits,
            "productUnitsCount"     => count($productUnits)
        );
    }
}
