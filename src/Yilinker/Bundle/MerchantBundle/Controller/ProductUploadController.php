<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\CustomBrand;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\ProductCountry;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\User;

class ProductUploadController extends YilinkerBaseController
{

    /**
     * Render Product Upload Page
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productUploadRenderAction ()
    {
        $userEntity = $this->getUser();
        if($userEntity){
            $em = $this->getDoctrine()->getManager();

            $productConditionRepository = $em->getRepository('YilinkerCoreBundle:ProductCondition');

            $messageCount = $em->getRepository('YilinkerCoreBundle:Message')
                               ->getCountUnonepenedMessagesByUser($userEntity);

            $productCategoryService = $this->get("yilinker_core.service.product_category");

            $rootCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory')->find(ProductCategory::ROOT_CATEGORY_ID);
            $categories = $productCategoryService->getChildren($rootCategory, false);

            $productUploadData = array (
                'conditionEntities' => $productConditionRepository->findAll(),
                'messageCount'      => $messageCount,
                'categories'        => $categories,
            );

            return $this->render('YilinkerMerchantBundle:Product:product_upload.html.twig', $productUploadData);
        }

        return $this->redirect($this->generateUrl('user_merchant_login'), 301);
    }

    /**
     * Product Upload
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productUploadDetailAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'message' => array('Login to continue'),
        );

        $entityManager = $this->getDoctrine()->getManager();

        if ($userEntity) {
            $response = array(
                'isSuccessful' => true,
                'message' => '',
            );
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
            $primaryImageId = $request->get('primaryImageId', 0);

            $formData = [
                'user'             => $userEntity->getUserId(),
                'brand'            => $request->get('brand', ''),
                'productCategory'  => $request->get('productCategory', ''),
                'name'             => $request->get('name', ''),
                'description'      => $request->get('description', ''),
                'shortDescription' => $request->get('shortDescription', ''),
                'condition'        => $request->get('productCondition', ''),
                'isFreeShipping'   => true,
                'youtubeVideoUrl'  => $request->get('youtubeUrl', ''),
                'shippingCategory' => $request->get('shippingCategory', ''),
                '_token'           => $request->get('_token')
            ];

            $form = $this->createForm('product_upload_detail', new Product());
            $form->submit($formData);

            $productProperties = json_decode($request->get('productProperties', '{}'), true);
            $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId());

            if (!$form->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'message' => $formErrorService->throwInvalidFields($form),
                );
            }
            else if ($propertyValidation['error'] === true) {
                $response = array(
                    'isSuccessful' => false,
                    'message' => $propertyValidation['message'],
                );
            }
            else if (sizeof($productProperties) === 0) {
                $formDataUnit = array(
                    'quantity'        => 0,
                    'sku'             => $request->get('sku', ''),
                    'price'           => $request->get('basePrice', '0.00'),
                    'discountedPrice' => $request->get('discountedPrice', '0.00'),
                    'weight'          => $request->get('weight', null),
                    'length'          => $request->get('length', null),
                    'width'           => $request->get('width', null),
                    'height'          => $request->get('height', null),
                    'status'          => ProductUnit::STATUS_ACTIVE
                );
                $formProductUnit = $this->createForm(
                    'product_upload_unit',
                    new ProductUnit(),
                    array (
                        'csrf_protection' => false,
                        'userId' => $userEntity->getUserId(),
                        'excludeProductUnitId' => null
                    )
                );
                $formProductUnit->submit($formDataUnit);

                if (!$formProductUnit->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => $formErrorService->throwInvalidFields($formProductUnit),
                    );
                }
            }
            else if ($primaryImageId == 0) {
                $response = array(
                    'isSuccessful' => false,
                    'message' => array('Kindly set primary image.'),
                );
            }

            $translationService = $this->get('yilinker_core.translatable.listener');
            $country = $translationService->getCountry();
            $country = $entityManager->getRepository("YilinkerCoreBundle:Country")
                                     ->findOneByCode($country);

            /**
             * Persist
             */
            if ($response['isSuccessful'] === true && $country) {

                /**
                 * Begin Transaction
                 */
                $entityManager->getConnection()->beginTransaction();
                $images = json_decode($request->get('productImages', '{}'), true);

                $language = $this->getUser()->getLanguage();
                $languageCode = $language && $language->getLanguageId() ? $language->getCode(): 'en';

                try {
                    $status = (int) $productUploadService->getProductStatus();
                    $product = $form->getData();
                    $product->setStatus($status);
                    $product->setDefaultLocale($languageCode);
                    $product->setLocale($languageCode);
                    $productEntity = $productUploadService->addProduct($product);
                    $productImageEntityContainer = array();

                    $productCountry = new ProductCountry();
                    $productCountry->setCountry($country)
                                   ->setProduct($product)
                                   ->setStatus($status);

                    $product->addProductCountry($productCountry);
                    $entityManager->persist($productCountry);

                    if (sizeof($images) > 0) {
                        foreach ($images as $image) {
                            $isPrimaryImage = $primaryImageId == $image['id'];
                            $uploadPath = $fileUploadService->moveToPermanentFolder($image['image'], $productEntity->getProductId());
                            $productImageEntity = $productUploadService->addProductImage($productEntity, $uploadPath, $isPrimaryImage);
                            $productImageEntityContainer[$image['image']] = $productImageEntity;
                        }
                    }
                    else {
                        throw new \Exception("There must be at least 1 image");
                    }

                    if ((int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                        $productUploadService->addCustomBrand ($productEntity, $request->get('customBrand'));
                    }

                    if ($productProperties) {

                        foreach ($productProperties as $productProperty) {
                            $productUnitEntity = $productUploadService->addProductUnit(
                                $productEntity,
                                0,
                                $productProperty['sku'],
                                $productProperty['price'],
                                $productProperty['discountedPrice'],
                                $productProperty['unitWeight'],
                                $productProperty['unitLength'],
                                $productProperty['unitWidth'],
                                $productProperty['unitHeight'],
                                ProductUnit::STATUS_ACTIVE,
                                'en'
                            );

                            foreach ($productProperty['images'] as $image) {
                                $productUploadService->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image['name']]);
                            }

                            foreach ($productProperty['attributes'] as $productAttribute) {
                                $productAttributeName = $productAttribute['name'];
                                $productAttributeValue = $productAttribute['value'];

                                if (isset($productAttributeContainer[$productAttributeName])) {
                                    $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                                }
                                else {
                                    $productAttributeEntity = $productUploadService
                                                          ->addProductAttributeName (
                                                              $productEntity,
                                                              $productAttributeName
                                                          );
                                    $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                                }

                                $productUploadService->addProductAttributeValue (
                                    $productAttributeEntity,
                                    $productUnitEntity,
                                    $productAttributeValue
                                );

                            }
                        }

                    }
                    else {
                        $productUploadService->addProductUnit (
                            $productEntity,
                            0,
                            $request->get('sku', ''),
                            $request->get('basePrice', '0.00'),
                            $request->get('discountedPrice', '0.00'),
                            $request->get('weight', null),
                            $request->get('length', null),
                            $request->get('width', null),
                            $request->get('height', null),
                            ProductUnit::STATUS_ACTIVE
                        );
                    }

                    $productUploadService->updateProductGroups($productEntity, explode(',', $request->get('productGroups', '')));

                    $entityManager->getConnection()->commit();
                    $response['slug'] = $productEntity->getSlug();
                }
                catch (\Exception $e) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => $e->getMessage(),
                    );
                    $entityManager->getConnection()->rollback();
                }
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Render Product Edit Page
     *
     * @param string $slug
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productUploadEditRenderAction (Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $productConditionRepository = $em->getRepository('YilinkerCoreBundle:ProductCondition');
        $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
        $user = $this->getUser();
        $product = $productRepository->findOneBy(array(
            'slug' => $slug,
            'user' => $user,
        ));

        if (!($product instanceof Product) || $product->getIsEditable() === false) {
            return $this->redirect($this->generateUrl('home_page'));
        }

        $messageCount = $em->getRepository('YilinkerCoreBundle:Message')
                           ->getCountUnonepenedMessagesByUser($user);

        $nonEditableFields = array();
        $resellerEditableFields = array();
        if ($product->getManufacturerProductMap()) {
            $nonEditableFields = $this->get('yilinker_merchant.service.reseller_uploader')
                                      ->getProductNonEditableFields();
            $resellerEditableFields = $this->get('yilinker_merchant.service.reseller_uploader')
                                           ->getProductEditableFields();
        }

        $productCategoryService = $this->get("yilinker_core.service.product_category");

        $rootCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory')->find(ProductCategory::ROOT_CATEGORY_ID);
        $categories = $productCategoryService->getChildren($rootCategory, false);
        $breadcrumbs = $productCategoryService->generateBreadcrumbs($product);
        $product->setLocale($request->getLocale());

        $productUploadData = array (
            'isUpdate'                => true,
            'productUploadDetail'     => $productUploadService->getProductUploadDetails($product),
            'conditionEntities'       => $productConditionRepository->findAll(),
            'nonEditableFields'       => $nonEditableFields,
            'resellerEditableFields'  => $resellerEditableFields,
            'messageCount'            => $messageCount,
            'categories'              => $categories,
            'breadcrumbs'             => $breadcrumbs,
            'productGroups'           => $product->getProductGroupsName()
        );

        return $this->render('YilinkerMerchantBundle:Product:product_upload.html.twig', $productUploadData);
    }

    /**
     * Product Edit Action
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function productUploadEditDetailAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'message' => array('Login to continue'),
        );

        if ($userEntity) {
            $response = array(
                'isSuccessful' => true,
                'message' => '',
            );
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
            $formErrorService = $this->get('yilinker_core.service.form.form_error');

            $em = $this->getDoctrine()->getManager();
            $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
            $productEntity = $productRepository->find($request->get('productId'));

            if ($productEntity->getIsEditable()) {
                $productPromoMapRepository = $em->getRepository('YilinkerCoreBundle:ProductPromoMap');

                $formData = array (
                    'user'             => $userEntity->getUserId(),
                    'brand'            => $request->get('brand'),
                    'productCategory'  => $request->get('productCategory'),
                    'name'             => $request->get('name', ''),
                    'description'      => $request->get('description', ''),
                    'shortDescription' => $request->get('shortDescription', ''),
                    'condition'        => $request->get('productCondition', ''),
                    'isFreeShipping'   => true,
                    'youtubeVideoUrl'  => $request->get('youtubeUrl', ''),
                    '_token'           => $request->get('_token'),
                    'shippingCategory' => $request->get('shippingCategory', ''),
                );

                $form = $this->createForm('product_upload_detail', $productEntity);
                $form->submit($formData);

                $productProperties = json_decode($request->get('productProperties', '{}'), true);
                $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId(), $productEntity);

                if (!$form->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => $formErrorService->throwInvalidFields($form),
                    );
                }
                else if ($propertyValidation['error'] === true) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => $propertyValidation['message'],
                    );
                }
                else if (sizeof($productProperties) === 0) {
                    $formDataUnit = array(
                        'quantity'        => 0,
                        'sku'             => $request->get('sku', ''),
                        'price'           => $request->get('basePrice', '0.00'),
                        'discountedPrice' => $request->get('discountedPrice', '0.00'),
                        'weight'          => $request->get('weight', null),
                        'length'          => $request->get('length', null),
                        'width'           => $request->get('width', null),
                        'height'          => $request->get('height', null),
                        'status'          => ProductUnit::STATUS_ACTIVE
                    );
                    $formProductUnit = $this->createForm (
                        'product_upload_unit',
                        new ProductUnit(),
                        array (
                            'csrf_protection' => false,
                            'userId' => $userEntity->getUserId(),
                            'excludeProductUnitId' => $request->get('productUnitId', null)
                        )
                    );
                    $formProductUnit->submit($formDataUnit);

                    if (!$formProductUnit->isValid()) {
                        $response = array(
                            'isSuccessful' => false,
                            'message' => $formErrorService->throwInvalidFields($formProductUnit),
                        );
                    }
                }

                /**
                 * Persist
                 */
                if ($response['isSuccessful'] === true) {
                    $images = json_decode($request->get('productImages', '{}'), true);

                    /**
                     * Begin Transaction
                     */
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->getConnection()->beginTransaction();
                    $productEntity = $form->getData();
                    try {

                        if (sizeof($productProperties) === 0) {
                            $productUnits[] = array (
                                'hasNoCombination'=> true,
                                'quantity'        => 0,
                                'sku'             => $request->get('sku', ''),
                                'price'           => $request->get('basePrice', '0.00'),
                                'discountedPrice' => $request->get('discountedPrice', '0.00'),
                                'unitWeight'      => $request->get('weight', null),
                                'unitLength'      => $request->get('length', null),
                                'unitWidth'       => $request->get('width', null),
                                'unitHeight'      => $request->get('height', null),
                                'status'          => ProductUnit::STATUS_ACTIVE,
                                'productUnitId'   => $request->get('productUnitId', null)
                            );
                        }
                        else {
                            $productUnits = $productProperties;
                        }

                        $productImageEntityContainer = array();

                        // TODO : REFACTOR : Add to product_form and add custom brand if exist
                        if ((int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                            $productUploadService->addCustomBrand ($productEntity, $request->get('customBrand'));
                        }

                        // START OF PRODUCT IMAGE UPLOAD
                        // TODO : RE-IMPLEMENT: move to a single method
                        $productImageRepository = $em->getRepository('YilinkerCoreBundle:ProductImage');
                        if (sizeof($images) > 0) {
                            $primaryImage = $productImageRepository->findOneBy(array(
                                                                         'product'   => $productEntity->getProductId(),
                                                                         'isPrimary' => true
                                                                     ));
                            if ($primaryImage instanceof ProductImage) {
                                $primaryImage->setIsPrimary(false);
                                $em->flush();
                            }

                            $primaryImageId = $request->get('primaryImageId', 0);

                            foreach ($images as $image) {
                                $isPrimaryImage = $primaryImageId == $image['id'];

                                if ($image['isNew'] === true) {
                                    $uploadPath = $fileUploadService->moveToPermanentFolder($image['image'], $productEntity->getProductId());
                                    $productImageEntity = $productUploadService->addProductImage($productEntity, $uploadPath, $isPrimaryImage);
                                }
                                else if (isset($image['isRemoved']) && $image['isRemoved'] === true && $image['isNew'] === false) {
                                    $productImageEntity = $productImageRepository->find($image['id']);
                                    $productUploadService->removeProductImage($productImageEntity);
                                    $productImageEntity = null;
                                }
                                else {
                                    $productImageEntity = $productImageRepository->find($image['id']);
                                }

                                if ($productImageEntity instanceof ProductImage) {
                                    if ($isPrimaryImage === true) {
                                        $productImageEntity->setIsPrimary(true);
                                        $em->flush();
                                    }
                                    $productImageEntityContainer[$image['image']] = $productImageEntity;

                                }

                            }

                        }
                        else {
                            throw new \Exception("There must be at least 1 image");
                        }
                        // END OF PRODUCT IMAGE UPLOAD

                        $productUploadService->updateProductUnits($productEntity, $productUnits, $productImageEntityContainer);
                        $productUploadService->updateProductGroups($productEntity, explode(',', $request->get('productGroups', '')));

                        $metaData = $entityManager->getClassMetadata(get_class($productEntity));
                        $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($metaData, $productEntity);
                        $entityManager->getConnection()->commit();
                        $response['data'] = array(
                            'slug' => $productEntity->getSlug()
                        );
                    }
                    catch (\Exception $e) {
                        $response = array(
                            'isSuccessful' => false,
                            'message' => array('Server Error'),
                        );
                        $entityManager->getConnection()->rollback();
                    }

                }

            }

        }

        return new JsonResponse($response);
    }

    public function getCategoryChildrenAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $productCategoryService = $this->get("yilinker_core.service.product_category");

        $rootCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory')->find($request->get("categoryId", null));
        $categories = $productCategoryService->getChildren($rootCategory, false);

        $response = new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Subcategories",
            "data" => array(
                "categories" => $categories
            )
        ), 200);

        $response->setPublic()->setMaxAge(86400)->setSharedMaxAge(86400);
        return $response;
    }

    /**
     * Product Upload Draft Action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productUploadSaveAsDraftAction (Request $request)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $userEntity = $this->getUser();
        $response = array (
            'isSuccessful' => false,
            'message' => array('Login to continue'),
        );

        if ($userEntity) {
            $response = array(
                'isSuccessful' => true,
                'message' => '',
            );
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');

            $formData = [
                'user'             => $userEntity->getUserId(),
                'brand'            => $request->get('brand', null),
                'productCategory'  => $request->get('productCategory', null),
                'name'             => $request->get('name', ''),
                'description'      => $request->get('description', ''),
                'shortDescription' => $request->get('shortDescription', ''),
                'condition'        => $request->get('productCondition', null),
                'isFreeShipping'   => true,
                'status'           => Product::DRAFT,
                'youtubeVideoUrl'  => $request->get('youtubeUrl', ''),
                '_token'           => $request->get('_token'),
                'shippingCategory' => $request->get('shippingCategory', '')
            ];

            $form = $this->createForm('api_product_upload_add_draft', new Product());
            $form->submit($formData);

            $productProperties = json_decode($request->get('productProperties', '{}'), true);
            $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId());

            if (!$form->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'message' => $formErrorService->throwInvalidFields($form),
                );
            }
            else if ($propertyValidation['error'] === true) {
                $response = array(
                    'isSuccessful' => false,
                    'message' => $propertyValidation['message'],
                );
            }
            else if (sizeof($productProperties) === 0) {
                $formDataUnit = array (
                    'quantity'        => 0,
                    'sku'             => $request->get('sku', ''),
                    'price'           => $request->get('basePrice', '0.00'),
                    'discountedPrice' => $request->get('discountedPrice', '0.00'),
                    'weight'          => $request->get('weight', '0'),
                    'length'          => $request->get('length', '0'),
                    'width'           => $request->get('width', '0'),
                    'height'          => $request->get('height', '0'),
                    'status'          => ProductUnit::STATUS_INACTIVE
                );
                $formProductUnit = $this->createForm(
                    'product_upload_unit_draft',
                    new ProductUnit(),
                    array ('csrf_protection' => false)
                );
                $formProductUnit->submit($formDataUnit);

                if (!$formProductUnit->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => $formErrorService->throwInvalidFields($formProductUnit),
                    );
                }
            }


            $translationService = $this->get('yilinker_core.translatable.listener');
            $country = $translationService->getCountry();
            $country = $entityManager->getRepository("YilinkerCoreBundle:Country")
                                     ->findOneByCode($country);
            /**
             * Persist
             */
            if ($response['isSuccessful'] === true && $country) {

                /**
                 * Begin Transaction
                 */
                $entityManager->getConnection()->beginTransaction();
                $images = json_decode($request->get('productImages', '{}'), true);

                try {

                    $product = $form->getData();
                    $productEntity = $productUploadService->addProduct($product);
                    $productImageEntityContainer = array();

                    $productCountry = new ProductCountry();
                    $productCountry->setCountry($country)
                                   ->setProduct($product)
                                   ->setStatus($product->getStatus());

                    $product->addProductCountry($productCountry);
                    $entityManager->persist($product);

                    if (sizeof($images) > 0) {
                        $primaryImageId = $request->get('primaryImageId', 0);

                        foreach ($images as $image) {
                            $isPrimaryImage = $primaryImageId == $image['id'];
                            $uploadPath = $fileUploadService->moveToPermanentFolder($image['image'], $productEntity->getProductId());
                            $productImageEntity = $productUploadService->addProductImage($productEntity, $uploadPath, $isPrimaryImage);
                            $productImageEntityContainer[$image['image']] = $productImageEntity;
                        }
                    }

                    if ((int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                        $productUploadService->addCustomBrand ($productEntity, $request->get('customBrand'));
                    }

                    if ($productProperties) {

                        foreach ($productProperties as $productProperty) {
                            $productUnitEntity = $productUploadService->addProductUnit(
                                $productEntity,
                                0,
                                $productProperty['sku'],
                                $productProperty['price'],
                                $productProperty['discountedPrice'],
                                $productProperty['unitWeight'],
                                $productProperty['unitLength'],
                                $productProperty['unitWidth'],
                                $productProperty['unitHeight'],
                                ProductUnit::STATUS_INACTIVE
                            );

                            foreach ($productProperty['images'] as $image) {
                                $productUploadService->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image['name']]);
                            }

                            foreach ($productProperty['attributes'] as $productAttribute) {
                                $productAttributeName = $productAttribute['name'];
                                $productAttributeValue = $productAttribute['value'];

                                if (isset($productAttributeContainer[$productAttributeName])) {
                                    $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                                }
                                else {
                                    $productAttributeEntity = $productUploadService
                                        ->addProductAttributeName (
                                            $productEntity,
                                            $productAttributeName
                                        );
                                    $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                                }

                                $productUploadService->addProductAttributeValue (
                                    $productAttributeEntity,
                                    $productUnitEntity,
                                    $productAttributeValue
                                );

                            }
                        }

                    }
                    else {
                        $productUploadService->addProductUnit (
                            $productEntity,
                            0,
                            $request->get('sku', '') == '' ? '0' : $request->get('sku', ''),
                            $request->get('basePrice', '0.00') == '' ? '0.00' : $request->get('basePrice', '0.00'),
                            $request->get('discountedPrice', '0.00') == '' ? '0.00' : $request->get('discountedPrice', '0.00'),
                            $request->get('weight', '0.00') == '' ? '0.00' : $request->get('weight', '0.00'),
                            $request->get('length', '0.00') == '' ? '0.00' : $request->get('length', '0.00'),
                            $request->get('width', '0.00') == '' ? '0.00' : $request->get('width', '0.00'),
                            $request->get('height', '0.00') == '' ? '0.00' : $request->get('height', '0.00'),
                            ProductUnit::STATUS_INACTIVE
                        );
                    }

                    $productUploadService->updateProductGroups($productEntity, explode(',', $request->get('productGroups', '')));

                    $response['slug'] = $productEntity->getSlug();

                    $entityManager->getConnection()->commit();
                }
                catch (\Exception $e) {
                    $response = array (
                        'isSuccessful' => false,
                        'message' => array('Something went wrong, try again later.'),
                    );
                    $entityManager->getConnection()->rollback();
                }
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Validate Product Properties
     * @param $productProperties
     * @param $userId
     * @param $product
     * @return array
     */
    private function validateProductPropertiesAction ($productProperties, $userId, Product $product = null)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $response = array(
            'error' => false
        );
        $listOfSku = array();

        if ($productProperties) {

            $combinationCount = 1;
            foreach ($productProperties as $productProperty) {
                $formDataProductUnit = array(
                                           'quantity'        => 0,
                                           'sku'             => $productProperty['sku'],
                                           'price'           => $productProperty['price'],
                                           'discountedPrice' => $productProperty['discountedPrice'],
                                           'length'          => $productProperty['unitLength'],
                                           'width'           => $productProperty['unitWidth'],
                                           'height'          => $productProperty['unitHeight'],
                                           'weight'          => $productProperty['unitWeight'],
                                           'status'          => ProductUnit::STATUS_ACTIVE
                                       );
                $productUnitId = null;
                $listOfSku[] = $productProperty['sku'];

                if (isset($productProperty['productUnitId']) && (int) $productProperty['productUnitId'] !== 0) {
                    $productUnitId = $productProperty['productUnitId'];
                }

                $formProductUnit = $this->createForm (
                    'product_upload_unit',
                    new ProductUnit(),
                    array(
                        'csrf_protection'      => false,
                        'userId'               => $userId,
                        'excludeProductUnitId' => $productUnitId,
                        'product'              => $product
                    )
                );
                $formProductUnit->submit($formDataProductUnit);

                if (!$formProductUnit->isValid()) {
                    $response['message'][] = ' Combination ' . $combinationCount . ': ' . implode($formErrorService->throwInvalidFields($formProductUnit), ', ');
                }

                $combinationCount++;
            }

        }

        $duplicateSku = array_unique(array_diff_assoc($listOfSku, array_unique($listOfSku)));

        if (count($duplicateSku) > 0) {
            foreach ($duplicateSku as $sku) {
                $response['message'][] = ' Duplicate SKU for ' . $sku;
            }
        }

        if (isset($response['message']) > 0) {
            $response['error'] = true;
        }

        return $response;
    }

    /**
     * Get Product Category Child
     * Usage:
     * -Click ProductCategory in BreadCrumbs
     * -Click ProductCategory
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductCategoryChildAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $productCategoryId = $request->query->get('productCategoryId');
        $productCategoryEntities = $productCategoryRepository->searchCategory($productCategoryId);
        $parents = $productCategoryRepository->getParentCategory($productCategoryId);
        $productCategoryResponse = array(
            'productCategory' => $productCategoryEntities,
            'parents' => $parents
        );

        return new JsonResponse($productCategoryResponse);
    }

    /**
     * Get Product Category Parents
     * Usage:
     * -Search
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductCategoryParentsByKeywordAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productCategoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $productCategoryKeyword = $request->query->get('categoryKeyword');
        $productCategoryEntities = $productCategoryRepository->getCategoriesByKeyword($productCategoryKeyword);
        $productCategoryContainer = array();

        if ($productCategoryEntities) {

            foreach ($productCategoryEntities as $productCategory) {

                $productCategoryEntity = $productCategory[0];
                $parents = $productCategoryRepository->getParentCategory($productCategoryEntity->getProductCategoryId());
                $parentNames = array_map(function ($value) { return $value['name']; }, $parents ) ;

                $productCategoryContainer[] = array(
                    'id' => $productCategoryEntity->getProductCategoryId(),
                    'parentId' => $productCategoryEntity->getParent()->getProductCategoryId(),
                    'name' => $productCategoryEntity->getName(),
                    'breadCrumb' => implode($parentNames, ' >> '),
                    'hasChild' => $productCategory['hasChildren']
                );

            }

        }

        return new JsonResponse($productCategoryContainer);
    }

    /**
     * Get Brand by name
     * @param Request $request
     * @return JsonResponse
     */
    public function getBrandByNameAction (Request $request)
    {
        $brandKeyword = $request->query->get('brandKeyword');
        $em = $this->getDoctrine()->getManager();
        $brandRepository = $em->getRepository('YilinkerCoreBundle:Brand');
        $brandEntities = $brandRepository->getBrandByName($brandKeyword);
        $ctr = 0;
        $brandContainer = array();

        if ($brandEntities) {

            foreach ($brandEntities as $brandEntity) {
                $brandContainer[$ctr]['id'] = $brandEntity->getBrandId();
                $brandContainer[$ctr]['value'] = $brandEntity->getName();
                $ctr++;
            }

        }

        return new JsonResponse($brandContainer);
    }

    /**
     * Get Category Attribute Name with Values
     * @param Request $request
     * @return JsonResponse
     */
    public function getCategoryAttributeAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productCategoryId = $request->query->get('productCategoryId');
        $productCategoryEntities = $em->getRepository('YilinkerCoreBundle:ProductCategory')->find($productCategoryId);
        $categoryAttributeEntities = $em->getRepository('YilinkerCoreBundle:CategoryAttributeName')
                                        ->getCategoryAttributeNameWithValue($productCategoryEntities);

        return new JsonResponse($categoryAttributeEntities);
    }

    /**
     * Upload Image to temp folder
     * @param Request $request
     * @return JsonResponse
     */
    public function productUploadImageAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'data' => '',
            'message' => 'Login to continue.'
        );

        if ($userEntity) {
            $file = $request->files->get('file');
            $fileImageData = array(
                'images' => array($file)
            );
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $formImages = $this->createForm('product_upload_image', null, array('csrf_protection' => false));
            $formImages->submit($fileImageData);

            if ($formImages->isValid() && $file instanceof File) {

                /**
                 * File Upload
                 */
                $folderName = $fileUploadService::TEMP_FOLDER;
                $fileName = trim($userEntity->getUserId() . '_' . rand(1, 1000) . '_' . strtotime(Carbon::now()));
                $fileWithExtension = $fileUploadService->uploadFile($file, $folderName, $fileName);
                $imageLocation = array (
                    'id' => $fileWithExtension,
                    'isNew' => true,
                    'image' => $fileWithExtension
                );

                $response = array(
                    'isSuccessful' => true,
                    'data' => $imageLocation,
                    'message' => ''
                );

            }
            else {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => 'Image Size Should be less than 3MB'
                );
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Upload image via CKEditor
     *
     * @param Request $request
     */
    public function productDescriptionImageUploadAction (Request $request)
    {
        $callBack = $request->get('CKEditorFuncNum');
        $userEntity = $this->getUser();
        $response = array (
            'isSuccessful' => true,
            'data' => '',
            'message' => ''
        );

        if ($userEntity) {
            $file = $request->files->get('upload');
            $fileImageData = array (
                'images' => array($file)
            );
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
            $formImages = $this->createForm('product_upload_image', null, array('csrf_protection' => false));
            $formImages->submit($fileImageData);

            if ($formImages->isValid() && $file instanceof File) {

                /**
                 * File Upload
                 */
                $folderName = $fileUploadService::PRODUCT_DESCRIPTION_IMAGE_FOLDER;
                $fileName = trim($userEntity->getUserId() . '_' . rand(1, 1000) . '_' . strtotime(Carbon::now()));
                $baseUri = $this->getParameter('merchant_hostname');
                $assetsDir = $fileUploadService->getUploadDirectory();
                $imageFullLocation = $baseUri . DIRECTORY_SEPARATOR . $assetsDir . DIRECTORY_SEPARATOR . $folderName . $fileUploadService->uploadFile($file, $folderName, $fileName);

                $response = array(
                    'data' => $imageFullLocation,
                    'message' => ''
                );

            }
            else {
                $response = array(
                    'data' => '',
                    'message' => 'Image Size Should be less than 3MB'
                );
            }

        }

        echo '<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(' . $callBack . ', "' . $response['data']. '","' . $response['message'] . '");</script></body></html>';
    }

    /**
     * Get Product & Product Unit Changes
     *
     * @param $product
     * @param $userId
     * @param array $productProperties
     * @param null $customBrand
     * @param array $images
     * @return array
     */
    private function __getProductChanges ($product, $userId, $productProperties = array(), $customBrand = null, $images = array())
    {
        $em = $this->getDoctrine()->getManager();
        $productUnitRepository = $em->getRepository('YilinkerCoreBundle:ProductUnit');
        $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
        $productChanges = $productUploadService->getProductChanges($product);
        $customBrandEntity = $em->getRepository('YilinkerCoreBundle:CustomBrand')
                                ->findOneByProduct($product->getProductId());

        if ($customBrand !== null &&
            $customBrand !== '' &&
            $customBrandEntity instanceof CustomBrand &&
            $customBrandEntity->getName() != $customBrand) {
            $productChanges['customBrand'] = array (
                0 => $customBrandEntity->getName(),
                1 => $customBrand
            );
        }

        if (sizeof($images) > 0) {

            foreach ($images as $image) {

                if ($image['isNew'] === true ||
                    (isset($image['isRemoved']) && $image['isRemoved'] === true && $image['isNew'] === false)) {
                    $productChanges['image'] = array (
                        0 => 'Image Updated',
                        1 => 'Image Updated'
                    );
                }

            }

        }

        $productUnitChanges = array();
        $hasNewProductUnit = false;

        foreach ($productProperties as $productProperty) {
            $formDataProductUnit = array (
                'quantity' => $productProperty['quantity'],
                'sku' => $productProperty['sku'],
                'price' => $productProperty['price'],
                'discountedPrice' => $productProperty['discountedPrice'],
                'length' => $productProperty['unitLength'],
                'width' => $productProperty['unitWidth'],
                'height' => $productProperty['unitHeight'],
                'weight' => $productProperty['unitWeight'],
                'status' => ProductUnit::STATUS_ACTIVE
            );
            $productUnitId = null;

            if (isset($productProperty['productUnitId']) && (int) $productProperty['productUnitId'] !== 0) {
                $productUnitId = $productProperty['productUnitId'];
            }

            if ( (int) $productUnitId > 0) {
                $productUnitEntity = $productUnitRepository->find($productUnitId);

                $formProductUnit = $this->createForm (
                    'product_upload_unit',
                    $productUnitEntity,
                    array (
                        'csrf_protection' => false,
                        'userId' => $userId,
                        'excludeProductUnitId' => $productUnitId
                    )
                );
                $formProductUnit->submit($formDataProductUnit);
                $changes = $productUploadService->getProductUnitChanges($productUnitEntity);

                if (sizeof($changes) > 0) {
                    $productUnitChanges[] = $changes;
                }

            }
            else {
                $hasNewProductUnit = true;
            }

            if (isset($productProperty['images'])) {

                foreach ($productProperty['images'] as $image) {

                    if (strpos($image['id'], '.') !== false && strpos($image['id'], '_') !== false) {
                        $productUnitChanges[]['image'] = array (
                            0 => 'Image Updated',
                            1 => 'Image Updated'
                        );
                    }

                }

            }

        }

        return compact (
            'productChanges',
            'hasNewProductUnit',
            'productUnitChanges'
        );
    }

    public function getCategoryByKeywordAction(Request $request)
    {
        $keyword = $request->get("keyword", null);
        $page = (int)$request->get("page", 1);

        $key = "product-category-search-{$keyword}";
        $content = $this->getCacheValue("product-category-search-{$keyword}", true, false);

        if($content === false){
            $productCategoryRepository = $this->getDoctrine()->getManager()->getRepository("YilinkerCoreBundle:ProductCategory");
            $productCategories = $productCategoryRepository->searchCategoryByKeyword($keyword, false, 30, $this->getOffset(30, $page));

            $productCategoryService = $this->get("yilinker_core.service.product_category");

            $collection = array();
            foreach ($productCategories as $productCategory){
                $category = $productCategoryService->iterateHeirarchy($productCategory, array(), true);
                array_push($collection, array_reverse($category));
            }

            $this->setCacheValue($key, $collection);
        }
        else{
            $collection = $content;
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
            "message" => "Searched categories",
            "data" => array(
                "categories" => $collection
            )
        ), 200);
    }

    /**
     * Render product translation page
     *
     * @param Request $request
     * @param string $languageCode
     * @param string $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function renderProductTranslationAction(Request $request, $languageCode, $slug)
    {
        $productData = $this->__getProductUploadData($slug, $this->getUser(), $languageCode, false);

        if ($productData === false) {
            $this->addFlash('error', 'Edit product translation is disabled.');

            return $this->redirectBack();
        }

        return $this->render('YilinkerMerchantBundle:Product:product_translation.html.twig', $productData);
    }

    /**
     * Translate Product
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function translateProductAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
        $locale = $request->get('locale', null);
        $user = $this->getUser();
        $productId = $request->get('productId', null);
        $name = $request->get('name', null);
        $fullDescription = $request->get('description', '');
        $shortDescription = $request->get('shortDescription', '');
        $attrNames = json_decode($request->get('attrNames', '{}'), true);
        $attrValues = json_decode($request->get('attrValues', '{}'), true);
        $productGroups = json_decode($request->get('productGroups', '{}'), true);
        $images = json_decode($request->get('productImages', '{}'), true);
        $productEntity = $productRepository->findOneBy(array(
                                                 'productId' => $productId,
                                                 'user'      => $user,
                                             ));
        $formData = [
            'user'             => $user instanceof User ? $user->getUserId() : 0,
            'name'             => $name,
            'shortDescription' => $shortDescription,
            'description'      => $fullDescription
        ];

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('translate_product', null, array('csrf_protection' => false));
        $form->submit($formData);

        if (!$form->isValid()) {
            $response = array(
                'isSuccessful' => false,
                'message'      => $formErrorService->throwInvalidFields($form),
            );
        }
        else {
            /**
             * Begin Transaction
             */
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->getConnection()->beginTransaction();

            try {
                $productImageRepository = $em->getRepository('YilinkerCoreBundle:ProductImage');
                $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');

                if (sizeof($images) > 0) {
                    $primaryImageId = $request->get('primaryImageId', 0);
                    $primaryImage = $productImageRepository->findOneBy(array(
                                                                 'product'       => $productEntity->getProductId(),
                                                                 'isPrimary'     => true,
                                                                 'defaultLocale' => $locale
                                                             ));

                    if ($primaryImage instanceof ProductImage) {
                        $primaryImage->setIsPrimary(false);
                        $em->flush();
                    }

                    foreach ($images as $image) {
                        $isPrimaryImage = $primaryImageId == $image['id'];

                        if ($image['isNew'] === true) {
                            $uploadPath = $fileUploadService->moveToPermanentFolder($image['image'], $productEntity->getProductId());
                            $productImageEntity = $productUploadService->addProductImage($productEntity, $uploadPath, $isPrimaryImage, $locale);
                        }
                        else if ($image['isNew'] === false && $image['defaultLocale'] != $locale) {
                            $productImageEntity = $productImageRepository->find($image['id']);
                            $em->detach($productImageEntity);
                            $productImageEntity->setDefaultLocale($locale);
                            $em->persist($productImageEntity);
                        }
                        else if (isset($image['isRemoved']) && $image['isRemoved'] === true && $image['isNew'] === false) {
                            $productImageEntity = $productImageRepository->findOneBy(array(
                                                                               'productImageId' => $image['id'],
                                                                               'defaultLocale'  => $locale
                                                                           ));

                            if ($productImageEntity instanceof ProductImage) {
                                $productUploadService->removeProductImage($productImageEntity);
                            }

                            $productImageEntity = null;
                        }
                        else {
                            $productImageEntity = $productImageRepository->findOneBy(array(
                                                                               'productImageId' => $image['id'],
                                                                               'defaultLocale'  => $locale
                                                                           ));
                        }

                        if ($productImageEntity instanceof ProductImage) {
                            if ($isPrimaryImage === true) {
                                $productImageEntity->setIsPrimary(true);
                                $em->flush();
                            }
                            $productImageEntityContainer[$image['image']] = $productImageEntity;

                        }

                        $productUploadService->updateProductCountryStatus($productEntity, Product::FOR_REVIEW, $locale);
                    }

                }
                else {
                    throw new \Exception("There must be at least 1 image");
                }

                $productUploadService->translateProduct(
                                           $productEntity,
                                           $locale,
                                           $name,
                                           $fullDescription,
                                           $shortDescription,
                                           $attrNames,
                                           $attrValues,
                                           $productGroups
                                       );

                $entityManager->getConnection()->commit();
                $response = array(
                    'isSuccessful' => true,
                    'slug'         => $productEntity->getSlug(),
                );
            }
            catch (\Exception $e) {
                $response = array(
                    'isSuccessful' => false,
                    'message'      => $e->getMessage(),
                );
                $entityManager->getConnection()->rollback();
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Get Product Upload Data
     *
     * @param $slug
     * @param User $user
     * @param $languageToTranslate
     * @return array
     */
    private function __getProductUploadData ($slug, User $user, $languageToTranslate, $checkTranslatable = true)
    {
        $em = $this->getDoctrine()->getManager();
        $translationService = $this->get('yilinker_core.translatable.listener');
        $translationService->setTranslatableLocale('en');
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $languageRepository = $em->getRepository('YilinkerCoreBundle:Language');
        $languageEntity = $languageRepository->findOneByCode($languageToTranslate);
        $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
        $product = $productRepository->findOneBy(array(
                                           'slug' => $slug,
                                           'user' => $user,
                                       ));

        if ($product === null) {
            return false;
        }

        $messageCount = $em->getRepository('YilinkerCoreBundle:Message')
                           ->getCountUnonepenedMessagesByUser($user);

        $productCategoryService = $this->get("yilinker_core.service.product_category");
        $rootCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory')
                           ->find(ProductCategory::ROOT_CATEGORY_ID);
        $categories = $productCategoryService->getChildren($rootCategory, false);
        $breadcrumbs = $productCategoryService->generateBreadcrumbs($product);

        $product2 = clone $product;
        $defaultLanguage = $product->getDefaultLocale();
        $product2->setLocale($defaultLanguage);
        $defaultProductDetail = $productUploadService->getProductUploadDetails($product2);

        $product->setLocale($languageToTranslate);
        $em->refresh($product);

        if ($checkTranslatable && !$product->getIsTranslatable()) {
            return false;
        }

        $translatedProductUploadDetail = $productUploadService->getProductUploadDetails($product);

        $data = array (
            'productUploadDetail'           => $defaultProductDetail,
            'translatedProductUploadDetail' => $translatedProductUploadDetail,
            'messageCount'                  => $messageCount,
            'categories'                    => $categories,
            'breadcrumbs'                   => $breadcrumbs,
            'languageToTranslate'           => $languageToTranslate,
            'productGroups'                 => $product->getProductGroups(),
            'languageEntity'                => $languageEntity
        );

        return $data;
    }

    private function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }

}
