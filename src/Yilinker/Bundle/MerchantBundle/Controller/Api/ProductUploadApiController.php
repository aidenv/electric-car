<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api;

use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\CustomBrand;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Yilinker\Bundle\CoreBundle\Entity\ProductImage;
use Yilinker\Bundle\CoreBundle\Entity\ProductUnit;

/**
 * Class ProductUploadApiController
 */
class ProductUploadApiController extends Controller
{

    /**
     * API for Product Upload
     * @param Request $request
     * @return JsonResponse
     */
    public function productUploadAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'data' => '',
            'message' => array('Login to continue'),
        );

        if ($userEntity) {
            $response = array(
                'isSuccessful' => true,
                'data' => '',
                'message' => '',
            );
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
            $formErrorService = $this->get('yilinker_core.service.form.form_error');

            /**
             * Product Detail
             */
            $formData = [
                'user' => $userEntity->getUserId(),
                'brand' => $request->request->get('brand'),
                'productCategory' => $request->request->get('productCategory'),
                'name' => $request->request->get('title', ''),
                'description' => $request->request->get('description', ''),
                'shortDescription' => $request->request->get('shortDescription', ''),
                'condition' => $request->request->get('condition'),
                'isFreeShipping' => true
            ];

            $form = $this->createForm('api_product_upload_add', new Product(), array('csrf_protection' => false));
            $form->submit($formData);

            /**
             * File Upload
             */
            $files = $request->files->get('images');
            $formDataImages = array(
                'images' => $files
            );
            $formImages = $this->createForm('product_upload_image', null, array('csrf_protection' => false));
            $formImages->submit($formDataImages);

            $productProperties = json_decode($request->request->get('productProperties', '{}'), true);
            $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId());

            if (!$form->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
                );
            }
            else if (!$formImages->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($formErrorService->throwInvalidFields($formImages), ' \n'),
                );
            }
            else if ($propertyValidation['error'] === true) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($propertyValidation['message'], ' \n'),
                );
            }
            else if (sizeof($productProperties) === 0) {
                $formDataUnit = array(
                    'quantity' => $request->request->get('quantity', ''),
                    'sku' => $request->request->get('sku', ''),
                    'price' => $request->request->get('price', ''),
                    'discountedPrice' => $request->request->get('discountedPrice', '0.00'),
                    'weight' => $request->request->get('weight', '0'),
                    'length' => $request->request->get('length', '0'),
                    'width' => $request->request->get('width', '0'),
                    'height' => $request->request->get('height', '0'),
                    'status' => ProductUnit::STATUS_ACTIVE
                );
                $formProductUnit = $this->createForm(
                    'product_upload_unit',
                    new ProductUnit(),
                    array(
                        'csrf_protection' => false,
                        'userId' => $userEntity->getUserId(),
                        'excludeProductUnitId' => null
                    )
                );
                $formProductUnit->submit($formDataUnit);

                if (!$formProductUnit->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => implode($formErrorService->throwInvalidFields($formProductUnit), ' \n'),
                    );
                }
            }

            /**
             * Persist
             */
            if ($response['isSuccessful'] === true) {
                /**
                 * Begin Transaction
                 */
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->getConnection()->beginTransaction();

                try {
                    $status = (int) $productUploadService->getProductStatus();
                    $form->getData()->setStatus($status);

                    $productEntity = $productUploadService->addProduct($form->getData());
                    $productImageEntityContainer = array();
                    $date = new Carbon($productEntity->getDateCreated()->format('Y/m/d H:m:s'));

                    if ((int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                        $productUploadService->addCustomBrand ($productEntity, $request->request->get('customBrand'));
                    }

                    if ($formDataImages['images'] !== null) {
                        $ctr = 1;

                        /**
                         * File Upload
                         */
                        foreach ($files as $file) {
                            $folderName = $fileUploadService::PRODUCT_FOLDER . trim($productEntity->getProductId());
                            $fileName = trim($productEntity->getProductId() . '_' . rand(1,100) . '_' . strtotime($date->format('Y/m/d H:m:s')));

                            if ($file instanceof File) {
                                $isPrimary = false;

                                if ($ctr === 1) {
                                    $isPrimary = true;
                                }

                                $imageLocation = $fileUploadService->uploadFile($file, $folderName, $fileName);
                                $imageFullPath = $fileUploadService->getUploadDirectory() . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $imageLocation;
                                $fileUploadService->uploadToCloud (new File($imageFullPath));
                                $fileUploadService->createImageWithDifferentSizes ($imageFullPath, $productEntity->getProductId());
                                $productImageEntity = $productUploadService->addProductImage($productEntity, $imageLocation, $isPrimary);
                                $productImageEntityContainer[] = $productImageEntity;
                            }

                        }
                    }

                    if ($productProperties) {
                        foreach ($productProperties as $productProperty) {
                            $productUnitEntity = $productUploadService->addProductUnit(
                                $productEntity,
                                $productProperty['quantity'],
                                $productProperty['sku'],
                                $productProperty['price'],
                                $productProperty['discountedPrice'],
                                $productProperty['unitWeight'],
                                $productProperty['unitLength'],
                                $productProperty['unitWidth'],
                                $productProperty['unitHeight'],
                                ProductUnit::STATUS_ACTIVE
                            );

                            if (sizeof($productImageEntityContainer) > 0) {

                                foreach ($productProperty['images'] as $image) {
                                    $productUploadService->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image]);
                                }

                            }

                            foreach ($productProperty['attribute'] as $productAttribute) {
                                $productAttributeName = $productAttribute['name'];
                                $productAttributeValue = $productAttribute['value'];

                                if (isset($productAttributeContainer[$productAttributeName])) {
                                    $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                                }
                                else {
                                    $productAttributeEntity = $productUploadService
                                        ->addProductAttributeName(
                                            $productEntity,
                                            $productAttributeName
                                        );
                                    $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                                }

                                $productUploadService->addProductAttributeValue(
                                    $productAttributeEntity,
                                    $productUnitEntity,
                                    $productAttributeValue
                                );

                            }
                        }

                    }
                    else {
                        $productUploadService->addProductUnit(
                            $productEntity,
                            $request->request->get('quantity', ''),
                            $request->request->get('sku', ''),
                            $request->request->get('price', ''),
                            $request->request->get('discountedPrice', '0.00'),
                            $request->request->get('weight', '0'),
                            $request->request->get('length', '0'),
                            $request->request->get('width', '0'),
                            $request->request->get('height', '0'),
                            ProductUnit::STATUS_ACTIVE
                        );
                    }

                    $entityManager->getConnection()->commit();
                }
                catch (\Exception $e) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => array('Server error, Try again later'),
                    );
                    $entityManager->getConnection()->rollback();
                }

            }

        }

        return new JsonResponse($response);

    }

    /**
     * API For Product Upload with status Draft
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function productUploadDraftAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'data' => '',
            'message' => array('Login to continue'),
        );

        if ($userEntity) {
            $response = array(
                'isSuccessful' => true,
                'data' => '',
                'message' => '',
            );
            $formErrorService = $this->get('yilinker_core.service.form.form_error');

            /**
             * Product Detail
             */
            $formData = [
                'user' => $userEntity->getUserId(),
                'brand' => $request->request->get('brand', null),
                'productCategory' => $request->request->get('productCategory', null),
                'name' => $request->request->get('title', ''),
                'description' => $request->request->get('description', ''),
                'shortDescription' => $request->request->get('shortDescription', ''),
                'condition' => $request->request->get('condition', null),
                'isFreeShipping' => true,
                'status' => Product::DRAFT
            ];

            $form = $this->createForm('api_product_upload_add_draft', new Product(), array('csrf_protection' => false));
            $form->submit($formData);

            /**
             * File Upload
             */
            $files = $request->files->get('images');
            $formDataImages = array(
                'images' => $files
            );
            $formImages = $this->createForm('product_upload_image', null, array('csrf_protection' => false));
            $formImages->submit($formDataImages);

            $productProperties = json_decode($request->request->get('productProperties', '{}'), true);
            $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId());

            if (!$form->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
                );
            }
            else if (!$formImages->isValid()) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($formErrorService->throwInvalidFields($formImages), ' \n'),
                );
            }
            else if ($propertyValidation['error'] === true) {
                $response = array(
                    'isSuccessful' => false,
                    'data' => '',
                    'message' => implode($propertyValidation['message'], ' \n'),
                );
            }
            else if (sizeof($productProperties) === 0) {
                $formDataUnit = array(
                    'quantity' => $request->request->get('quantity', '0'),
                    'sku' => $request->request->get('sku', ''),
                    'price' => $request->request->get('price', '0'),
                    'discountedPrice' => $request->request->get('discountedPrice', '0.00'),
                    'weight' => $request->request->get('weight', '0'),
                    'length' => $request->request->get('length', '0'),
                    'width' => $request->request->get('width', '0'),
                    'height' => $request->request->get('height', '0'),
                    'status' => ProductUnit::STATUS_INACTIVE
                );
                $formProductUnit = $this->createForm('product_upload_unit_draft', new ProductUnit(), array('csrf_protection' => false));
                $formProductUnit->submit($formDataUnit);

                if (!$formProductUnit->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => implode($formErrorService->throwInvalidFields($formProductUnit), ' \n'),
                    );
                }
            }

            /**
             * Persist
             */
            if ($response['isSuccessful'] === true) {

                /**
                 * Begin Transaction
                 */
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->getConnection()->beginTransaction();

                try {
                    $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
                    $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');

                    $productEntity = $productUploadService->addProduct($form->getData());
                    $date = new Carbon($productEntity->getDateCreated()->format('Y/m/d H:m:s'));
                    $productImageEntityContainer = array();

                    if ($productEntity->getBrand() !== null && (int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                        $productUploadService->addCustomBrand ($productEntity, $request->request->get('customBrand'));
                    }

                    if ($formDataImages['images'] !== null) {
                        $ctr = 1;

                        /**
                         * File Upload
                         */
                        foreach ($files as $file) {
                            $folderName = $fileUploadService::PRODUCT_FOLDER . trim($productEntity->getProductId());
                            $fileName = trim($productEntity->getProductId() . '_' . rand(1,100) . '_' . strtotime($date->format('Y/m/d H:m:s')));

                            if ($file instanceof File) {
                                $isPrimary = false;

                                if ($ctr === 1) {
                                    $isPrimary = true;
                                }

                                $imageLocation = $fileUploadService->uploadFile($file, $folderName, $fileName);
                                $imageFullPath = $fileUploadService->getUploadDirectory() . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $imageLocation;
                                $fileUploadService->uploadToCloud (new File($imageFullPath));
                                $fileUploadService->createImageWithDifferentSizes ($imageFullPath, $productEntity->getProductId());
                                $productImageEntity = $productUploadService->addProductImage($productEntity, $imageLocation, $isPrimary);
                                $productImageEntityContainer[] = $productImageEntity;
                            }

                        }
                    }

                    if ($productProperties) {
                        foreach ($productProperties as $productProperty) {
                            $productUnitEntity = $productUploadService->addProductUnit(
                                $productEntity,
                                $productProperty['quantity'],
                                $productProperty['sku'],
                                $productProperty['price'],
                                $productProperty['discountedPrice'],
                                $productProperty['unitWeight'],
                                $productProperty['unitLength'],
                                $productProperty['unitWidth'],
                                $productProperty['unitHeight'],
                                ProductUnit::STATUS_INACTIVE
                            );

                            if (sizeof($productImageEntityContainer) > 0) {

                                foreach ($productProperty['images'] as $image) {
                                    $productUploadService->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image]);
                                }

                            }

                            foreach ($productProperty['attribute'] as $productAttribute) {
                                $productAttributeName = $productAttribute['name'];
                                $productAttributeValue = $productAttribute['value'];

                                if (isset($productAttributeContainer[$productAttributeName])) {
                                    $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                                }
                                else {
                                    $productAttributeEntity = $productUploadService
                                        ->addProductAttributeName(
                                            $productEntity,
                                            $productAttributeName
                                        );
                                    $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                                }

                                $productUploadService->addProductAttributeValue(
                                    $productAttributeEntity,
                                    $productUnitEntity,
                                    $productAttributeValue
                                );

                            }
                        }

                    }
                    else {
                        $productUploadService->addProductUnit(
                            $productEntity,
                            $request->request->get('quantity', '0'),
                            $request->request->get('sku', ''),
                            $request->request->get('price', '0'),
                            $request->request->get('discountedPrice', '0.00'),
                            $request->request->get('weight', '0'),
                            $request->request->get('length', '0'),
                            $request->request->get('width', '0'),
                            $request->request->get('height', '0'),
                            ProductUnit::STATUS_INACTIVE
                        );
                    }

                    $entityManager->getConnection()->commit();
                }
                catch (\Exception $e) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => array('Server error, Try again later'),
                    );
                    $entityManager->getConnection()->rollback();
                }

            }

        }

        return new JsonResponse($response);
    }

    /**
     * Get Product Upload Details
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductEditDetailsAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $productId = $request->query->get('productId', 0);
        $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
        $productEntity = $productRepository->find($productId);
        $response = array (
            'isSuccessful' => true,
            'message' => '',
            'data' => ''
        );

        if (!$productEntity) {
            $response = array (
                'isSuccessful' => false,
                'message'      => 'Invalid Product Id',
                'data'         => '',
            );
        }

        if ($response['isSuccessful'] === true) {
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $productDetails = $productUploadService->getProductUploadDetails($productEntity);
            $productEntity = $productDetails['productEntity'];
            $brandEntity = $productDetails['brandEntity'];

            /**
             * Remove Image in "Image" if exist in Product Unit Image
             */
            $productUnitImageIds = array();
            if (sizeof($productDetails['productUnit']) > 0) {

                foreach ($productDetails['productUnit'] as &$productUnit) {
                    $productUnitImage = $productUnit['images'];

                    if (sizeof($productUnitImage) > 0) {

                        foreach ($productUnitImage as $imageKey => $image) {
                            $productUnitImageIds[$image['id']] = true;

                            $productUnit['images'][$imageKey]['path'] = $productEntity->getProductId() . '/' . $image['name'];
                            $productUnit['images'][$imageKey]['image'] = $image['name'];

                            unset($productUnit['images'][$imageKey]['isNew']);
                            unset($productUnit['images'][$imageKey]['name']);
                        }

                    }

                }

            }

            $isUnset = false;
            if (sizeof($productUnitImageIds) > 0  && sizeof($productDetails['productImageEntity']) > 0) {

                foreach ($productDetails['productImageEntity'] as $key => $productImage) {

                    if (isset($productUnitImageIds[$productImage['id']])) {
                        unset($productDetails['productImageEntity'][$key]);
                        $isUnset = true;
                    }

                }

            }

            $productDetailArray = array (
                'productId' => $productEntity->getProductId(),
                'conditionId' => $productEntity->getCondition() !== null ? $productEntity->getCondition()->getProductConditionId() : null,
                'conditionName' => $productEntity->getCondition() !== null ? $productEntity->getCondition()->getName() : null,
                'categoryId' => $productEntity->getProductCategory() !== null ? $productEntity->getProductCategory()->getProductCategoryId() : null,
                'categoryName' => $productEntity->getProductCategory() !== null ? $productEntity->getProductCategory()->getName() : null,
                'brandId' => $productEntity->getBrand() !== null ? $productEntity->getBrand()->getBrandId() : null,
                'brandName' => $brandEntity !== null && $brandEntity !== false && $brandEntity !== '' ? $brandEntity->getName() : null,
                'title' => $productEntity->getName(),
                'shortDescription' => $productEntity->getShortDescription(),
                'description' => $productEntity->getDescription(),
                'images' => $isUnset === true ? array_values($productDetails['productImageEntity']) : $productDetails['productImageEntity'],
                'hasCombination' => $productDetails['hasCombination'],
                'productProperties' => $productDetails['productUnit'],
                'productVariants' => $productDetails['productAndCategoryAttributes'],
                'baseUri' => $this->getParameter('frontend_hostname')
            );

            $response = array (
                'isSuccessful' => true,
                'message' => '',
                'data' => $productDetailArray
            );

        }

        return new JsonResponse($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProductAction (Request $request)
    {
        $userEntity = $this->getUser();
        $response = array(
            'isSuccessful' => false,
            'data' => '',
            'message' => array('Login to continue'),
        );

        if ($userEntity) {
            $response = array(
                'isSuccessful' => false,
                'data' => '',
                'message' => 'Invalid Product Id',
            );
            $em = $this->getDoctrine()->getManager();
            $productUploadService = $this->get('yilinker_merchant.service.product_uploader');
            $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
            $productEntity = $productRepository->find($request->request->get('productId', 0));
            $imageDetails = json_decode($request->get('imageDetails', '{}'), true);

            if ($productEntity && $productEntity->getIsEditable()) {
                $response = array (
                    'isSuccessful' => true,
                    'data' => '',
                    'message' => '',
                );
                $formErrorService = $this->get('yilinker_core.service.form.form_error');

                /**
                 * Product Detail
                 */
                $formData = [
                    'user' => $userEntity->getUserId(),
                    'brand' => $request->request->get('brand'),
                    'productCategory' => $request->request->get('productCategory'),
                    'name' => $request->request->get('title', ''),
                    'description' => $request->request->get('description', ''),
                    'shortDescription' => $request->request->get('shortDescription', ''),
                    'condition' => $request->request->get('condition'),
                    'isFreeShipping' => true
                ];

                $form = $this->createForm('api_product_upload_add', $productEntity, array('csrf_protection' => false));
                $form->submit($formData);

                /**
                 * File Upload
                 */
                $files = $request->files->get('images');
                $formDataImages = array(
                    'images' => $files
                );
                $formImages = $this->createForm('product_upload_image', null, array('csrf_protection' => false));
                $formImages->submit($formDataImages);

                $productProperties = json_decode($request->request->get('productProperties', '{}'), true);
                $propertyValidation = $this->validateProductPropertiesAction($productProperties, $userEntity->getUserId());

                if (!$form->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => implode($formErrorService->throwInvalidFields($form), ' \n'),
                    );
                } else if (sizeof($files) === 0) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => 'Image is required',
                    );
                } else if (sizeof($imageDetails) == 0) {
                    $response = array(
                        'isSuccessful' => false,
                        'message' => 'Image Detail is required',
                    );
                } else if (!$formImages->isValid()) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => implode($formErrorService->throwInvalidFields($formImages), ' \n'),
                    );
                } else if (sizeof($imageDetails) > 0 && $formImages->isValid()) {
                    $error = '';
                    $errorCount = 0;

                    foreach ($imageDetails as $imageDetail) {

                        if (!($formDataImages['images'][$imageDetail['imageId']] instanceof File)) {
                            $error .= 'Invalid Image File \n';
                            $errorCount++;
                        }

                    }

                    if ($errorCount > 0) {
                        $response = array (
                            'isSuccessful' => false,
                            'data' => '',
                            'message' => $error,
                        );
                    }

                } else if ($propertyValidation['error'] === true) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => implode($propertyValidation['message'], ' \n'),
                    );
                } else if (sizeof($productProperties) === 0) {
                    $formDataUnit = array(
                        'quantity' => $request->request->get('quantity', ''),
                        'sku' => $request->request->get('sku', ''),
                        'price' => $request->request->get('price', ''),
                        'discountedPrice' => $request->request->get('discountedPrice', '0.00'),
                        'weight' => $request->request->get('weight', '0'),
                        'length' => $request->request->get('length', '0'),
                        'width' => $request->request->get('width', '0'),
                        'height' => $request->request->get('height', '0'),
                        'status' => ProductUnit::STATUS_ACTIVE
                    );
                    $formProductUnit = $this->createForm (
                        'product_upload_unit',
                        new ProductUnit(),
                        array(
                            'csrf_protection' => false,
                            'userId' => $userEntity->getUserId(),
                            'excludeProductUnitId' => $request->request->get('productUnitId', null)
                        )
                    );
                    $formProductUnit->submit($formDataUnit);

                    if (!$formProductUnit->isValid()) {
                        $response = array(
                            'isSuccessful' => false,
                            'data' => '',
                            'message' => implode($formErrorService->throwInvalidFields($formProductUnit), ' \n'),
                        );
                    }
                }
            }

            /**
             * Persist
             */
            if ($response['isSuccessful'] === true) {

                /**
                 * Begin Transaction
                 */
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->getConnection()->beginTransaction();
                $productEntity = $form->getData();

                try {

                    if (sizeof($productProperties) === 0) {
                        $productUnits[] = array (
                            'quantity' => $request->request->get('quantity', ''),
                            'sku' => $request->request->get('sku', ''),
                            'price' => $request->request->get('price', ''),
                            'discountedPrice' => $request->request->get('discountedPrice', '0.00'),
                            'unitWeight' => $request->request->get('weight', null),
                            'unitLength' => $request->request->get('length', null),
                            'unitWidth' => $request->request->get('width', null),
                            'unitHeight' => $request->request->get('height', null),
                            'status' => ProductUnit::STATUS_ACTIVE,
                            'productUnitId' => $request->request->get('productUnitId', null)
                        );
                    }
                    else {
                        $productUnits = $productProperties;
                    }

                    $getChanges = $this->__getProductChanges(
                        $productEntity, $userEntity->getUserId(), $productUnits, $request->request->get('customBrand', null), $imageDetails
                    );
                    $status = (int) $productUploadService->getProductStatus($productEntity, $getChanges);
                    $productEntity->setStatus($status);

                    $fileUploadService = $this->get('yilinker_merchant.service.product_file_uploader');
                    $productPromoMapRepository = $em->getRepository('YilinkerCoreBundle:ProductPromoMap');
                    $productEntity = $productUploadService->updateProduct($productEntity);
                    $productImageEntityContainer = array();
                    $date = new Carbon($productEntity->getDateCreated()->format('Y/m/d H:m:s'));

                    if ((int) $productEntity->getBrand()->getBrandId() === Brand::CUSTOM_BRAND_ID) {
                        $productUploadService->addCustomBrand ($productEntity, $request->request->get('customBrand'));
                    }

                    $promoMapArray = $productUploadService->removeProductUnits($productEntity);

                    if (sizeof($formDataImages['images']) > 0 && sizeof($imageDetails) > 0) {
                        $productImageRepository = $em->getRepository('YilinkerCoreBundle:ProductImage');
                        $ctr = 1;
                        $imageId = 0;

                        /**
                         * File Upload
                         *
                         */
                        foreach ($imageDetails as $imageDetail) {
                            $folderName = $fileUploadService::PRODUCT_FOLDER . trim($productEntity->getProductId());
                            $fileName = trim($productEntity->getProductId() . '_' . rand(1, 100) . '_' . strtotime($date->format('Y/m/d H:m:s')));

                            $isPrimary = false;

                            if ($ctr === 1) {
                                $isPrimary = true;
                            }

                            if ( (bool) $imageDetail['isNew'] === true) {
                                $imageLocation = $fileUploadService->uploadFile($formDataImages['images'][$imageDetail['imageId']], $folderName, $fileName);
                                $imageFullPath = $fileUploadService->getUploadDirectory() . DIRECTORY_SEPARATOR . $folderName . DIRECTORY_SEPARATOR . $imageLocation;
                                $fileUploadService->uploadToCloud (new File($imageFullPath));
                                $fileUploadService->createImageWithDifferentSizes ($imageFullPath, $productEntity->getProductId());
                                $productImageEntity = $productUploadService->addProductImage($productEntity, $imageLocation, $isPrimary);
                            }
                            else if ( (bool) $imageDetail['isRemoved'] === true && (bool) $imageDetail['isNew'] === false) {
                                $productImageEntity = $productImageRepository->find($imageDetail['oldId']);
                                $productUploadService->removeProductImage($productImageEntity);
                            }
                            else {
                                $productImageEntity = $productImageRepository->find($imageDetails[$imageId]['oldId']);
                            }

                            if ($productImageEntity !== null) {
                                $productImageEntityContainer[] = $productImageEntity;
                            }

                            $imageId++;
                        }
                    }

                    if ($productProperties) {
                        foreach ($productProperties as $productProperty) {
                            $productUnitEntity = $productUploadService->addProductUnit(
                                                                            $productEntity,
                                                                            $productProperty['quantity'],
                                                                            $productProperty['sku'],
                                                                            $productProperty['price'],
                                                                            $productProperty['discountedPrice'],
                                                                            $productProperty['unitWeight'],
                                                                            $productProperty['unitLength'],
                                                                            $productProperty['unitWidth'],
                                                                            $productProperty['unitHeight'],
                                                                            ProductUnit::STATUS_ACTIVE
                                                                        );

                            if (sizeof($productImageEntityContainer) > 0) {

                                foreach ($productProperty['images'] as $image) {

                                    if ($productImageEntityContainer[$image] instanceof ProductImage) {
                                        $productUploadService->addProductUnitImage($productUnitEntity, $productImageEntityContainer[$image]);
                                    }

                                }

                            }

                            $attributeNameValuePair = '';
                            foreach ($productProperty['attribute'] as $productAttribute) {
                                $productAttributeName = $productAttribute['name'];
                                $productAttributeValue = $productAttribute['value'];

                                if (isset($productAttributeContainer[$productAttributeName])) {
                                    $productAttributeEntity = $productAttributeContainer[$productAttributeName];
                                }
                                else {
                                    $productAttributeEntity = $productUploadService
                                                              ->addProductAttributeName(
                                                                  $productEntity,
                                                                  $productAttributeName
                                                              );
                                    $productAttributeContainer[$productAttributeName] = $productAttributeEntity;
                                }

                                $productUploadService->addProductAttributeValue(
                                                           $productAttributeEntity,
                                                           $productUnitEntity,
                                                           $productAttributeValue
                                                       );

                                $attributeNameValuePair .= strtoupper($productAttributeName.$productAttributeValue);
                            }

                            if (sizeof($promoMapArray) > 0) {

                                foreach ($promoMapArray as $promoMap) {

                                    if ($attributeNameValuePair === $promoMap['attributeNameValuePair']) {
                                        $promoMapEntity = $productPromoMapRepository->find($promoMap['promoMapId']);
                                        $productUploadService->updateProductPromoMap($promoMapEntity, $productUnitEntity);
                                    }

                                }

                            }
                        }

                    }
                    else {
                        $productUploadService->addProductUnit (
                                                   $productEntity,
                                                   $request->request->get('quantity', ''),
                                                   $request->request->get('sku', ''),
                                                   $request->request->get('price', ''),
                                                   $request->request->get('discountedPrice', '0.00'),
                                                   $request->request->get('weight', '0'),
                                                   $request->request->get('length', '0'),
                                                   $request->request->get('width', '0'),
                                                   $request->request->get('height', '0'),
                                                   ProductUnit::STATUS_ACTIVE
                                               );
                    }

                    $metaData = $entityManager->getClassMetadata(get_class($productEntity));
                    $entityManager->getUnitOfWork()->recomputeSingleEntityChangeSet($metaData, $productEntity);
                    $entityManager->getConnection()->commit();
                }
                catch (\Exception $e) {
                    $response = array(
                        'isSuccessful' => false,
                        'data' => '',
                        'message' => $e->getMessage(),
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
     * @return array
     */
    private function validateProductPropertiesAction ($productProperties, $userId)
    {
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $response = array(
            'error' => false
        );

        if ($productProperties) {

            foreach ($productProperties as $productProperty) {
                $formDataProductUnit = array(
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

                $formProductUnit = $this->createForm(
                    'product_upload_unit',
                    new ProductUnit(),
                    array (
                        'csrf_protection' => false,
                        'userId' => $userId,
                        'excludeProductUnitId' => $productUnitId
                    )
                );
                $formProductUnit->submit($formDataProductUnit);

                if (!$formProductUnit->isValid()) {
                    $response['message'][] = $productProperty['sku'] . ' ' . $formErrorService->throwInvalidFields($formProductUnit)[0];
                }

            }

        }

        if (isset($response['message']) > 0) {
            $response['error'] = true;
        }

        return $response;
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
        $productUnitChanges = array();
        $hasNewProductUnit = false;
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

            foreach ($images as $imageDetail) {

                if ( (bool) $imageDetail['isNew'] === true ||
                    ((bool) $imageDetail['isRemoved'] === true && (bool) $imageDetail['isNew'] === false)) {
                    $productChanges['image'] = array (
                        0 => 'Image Updated',
                        1 => 'Image Updated'
                    );
                }

            }

        }

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

        }

        return compact (
            'productChanges',
            'hasNewProductUnit',
            'productUnitChanges'
        );
    }

}
