<?php

namespace Yilinker\Bundle\BackendBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\BackendBundle\Services\Cms\CmsManager;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Class CmsController
 *
 * @Security("has_role('ROLE_ADMIN') or has_role('ROLE_MARKETING')")
 * @package Yilinker\Bundle\BackendBundle\Controller
 */
class CmsController extends Controller
{

    /**
     * Render product list
     *
     * @param Request $request
     */
    public function renderCmsProductAction(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $service = $this->get('yilinker_core.service.cms.product_lists');
        $nodes = $service->page($page, $perPage);
        $totalNodes = $service->totalProductLists;
        $data = compact('perPage', 'nodes', 'totalNodes');

        return $this->render('YilinkerBackendBundle:Cms:product.html.twig', $data);
    }

    /**
     * Remove Products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeProductsAction (Request $request)
    {
        $isSuccessful = $this->get('yilinker_backend.cms_manager')
                             ->removeProducts($request->get('productIds', array()));

        return new JsonResponse($isSuccessful);
    }

    /**
     * Render product detail
     *
     * @param null $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderProductDetailAction($id = null)
    {
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $assetHelper = $this->get('templating.helper.assets');

        $em = $this->getDoctrine()->getManager();

        $productDetails = array(
            'productDetails' => $pagesService->getProductDetailById($id),
            'sections'       => $pagesService->getProductDetailSection(),
            'fromTemp'       => false
        );

        // get data in temporary file
        $file = $this->get('yilinker_backend.cms_manager')->getTempJsonFile(CmsManager::PRODUCTS_JSON_FILE_NAME);

        if (!is_null($file)) {
            $jsonData = json_decode($file, true);

            if (isset($jsonData[$id])) {
                $jsonData = $jsonData[$id];
                $productDetails['productDetails'] = array(
                    'productDetailId'         => $id,
                    'sectionId'               => (int) $jsonData['sectionId'],
                    'title'                   => $jsonData['title'],
                    'homePageBannerSrc'       => $jsonData['featuredProductUrl'],
                    'homePageBannerUrl'       => $assetHelper->getUrl(PagesService::HOME_IMAGE_DIRECTORY.$jsonData['featuredProductBanner'][0]),
                    'homePageBannerFileName'  => $jsonData['featuredProductBannerFileName'],
                    'innerPageBannerSrc'      => $jsonData['innerPageBannerUrl'],
                    'innerPageBannerUrl'      => $assetHelper->getUrl(PagesService::PRODUCT_LIST_IMAGE_DIRECTORY.$jsonData['innerPageBannerSrc'][0]),
                    'innerPageBannerFileName' => $jsonData['innerPageBannerFileName'],
                    'products'                => $em->getRepository('YilinkerCoreBundle:Product')->findByProductId($jsonData['products'])
                );
                $productDetails['fromTemp'] = true;
            }
        }

        return $this->render('YilinkerBackendBundle:Cms:product_details.html.twig', $productDetails);
    }

    /**
     * Update product detail in xml
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProductDetailAction(Request $request)
    {
        $title = $request->get('title', null);
        $isSuccessful = true;
        $message = '';

        if (intval($_SERVER['CONTENT_LENGTH']) > 0 && $request->request->count() === 0) {
            $isSuccessful = false;
            $message = 'Total file upload max size exceed, Files are too large.';
        }

        $formData = [
            'title'                         => $title,
            'sectionId'                     => $request->get('sectionId', null),
            'featuredProductBanner'         => array($request->files->get('homePageBannerSrc', null)),
            'featuredProductUrl'            => $request->get('homePageBannerUrl'),
            'featuredProductBannerFileName' => $request->get('featuredProductBannerFileName'),
            'innerPageBannerSrc'            => array($request->files->get('innerPageBannerSrc', null)),
            'innerPageBannerUrl'            => $request->get('innerPageBannerUrl'),
            'innerPageBannerFileName'       => $request->get('innerPageBannerFileName'),
            'products'                      => explode(',', $request->get('products')),
            'applyImmediate'                => filter_var($request->get('applyImmediate' , false), FILTER_VALIDATE_BOOLEAN)
        ];

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('core_cms_product_detail')
                     ->submit($formData);

        if ($form->isValid() && $isSuccessful === true) {
            $isSuccessful = true;
            $cmsManager = $this->get('yilinker_backend.cms_manager');
            $cmsManager->saveProductList($form->getData());
        }
        else {
            $isSuccessful = false;
            $message = $formErrorService->throwInvalidFields($form);
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'message'      => $message
        ));
    }

    /**
     * Get Product by slug
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductBySlugAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message'      => 'No product/s found.',
            'data'         => array()
        );
        $products = $this->getDoctrine()
                         ->getManager()
                         ->getRepository('YilinkerCoreBundle:Product')
                         ->findBySlug($request->get('slugs', ''));

        if (sizeof($products) > 0) {

            foreach ($products as $product) {
                $productArray[] = array(
                    'product'     => $product->getDetails(),
                    'productUnit' => $product->getDefaultUnit()->toArray()
                );
            }

            $response = array(
                'isSuccessful' => true,
                'message'      => 'Invalid Slugs',
                'data'         => $productArray
            );

        }

        return new JsonResponse($response);
    }

    /**
     * Render Daily login cms
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderDailyLoginAction ()
    {
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $dailyLoginData = $pagesService->getDailyLoginData($homeXml);

        return $this->render('YilinkerBackendBundle:Cms:daily_login.html.twig', compact('dailyLoginData'));
    }

    /**
     * Edit daily login xml
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function editDailyLoginContentAction (Request $request)
    {
        $response = array (
            'isSuccessful' => true,
            'message'      => '',
            'data'         => ''
        );
        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $form = $this->createForm('daily_login_form_type', null, array('csrf_protection' => false));

        $images = array (
            $request->files->get('firstInnerBanner'),
            $request->files->get('secondInnerBanner'),
            $request->files->get('thirdInnerBanner')
        );

        $form->submit(array (
            'images'          => $images,
            'firstMessage'    => $request->get('firstMessage', null),
            'secondMessage'   => $request->get('secondMessage', null),
            'firstBannerUrl'  => $request->get('firstBannerUrl', null),
            'secondBannerUrl' => $request->get('secondBannerUrl', null),
            'thirdBannerUrl'  => $request->get('thirdBannerUrl', null)
        ));

        if (!$form->isValid()) {
            $response = array (
                'isSuccessful' => false,
                'message'      => implode($formErrorService->throwInvalidFields($form), ' <br> -'),
                'data'         => ''
            );
        }
        else {
            $cmsManager = $this->get('yilinker_backend.cms_manager');
            $cmsManager->updateDailyLogin($form->getData());
        }

        return new JsonResponse($response);
    }

    /**
     * Render Main banner
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderMainBannerAction()
    {
        $cmsManager = $this->get('yilinker_backend.cms_manager');
        $file = $cmsManager->getTempJsonFile(CmsManager::TOP_BANNERS_JSON_FILE_NAME);
        $mainBanners = $this->get('yilinker_core.service.pages.pages')->getMainBanners();

        if (!is_null($file)) {
            $assetHelper = $this->get('templating.helper.assets');
            $banners = json_decode($file, true);
            foreach($banners as &$banner) {
                $banner['image'] = $assetHelper->getUrl(PagesService::HOME_IMAGE_DIRECTORY.$banner['imageName']);
            }

            $mainBanners = array(
                'banners' => $banners,
                'isTemp'  => true
            );
        }

        return $this->render('YilinkerBackendBundle:Cms:banner_details.html.twig', $mainBanners);
    }

    /**
     * Update main banner
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateMainBannerAction(Request $request)
    {
        $isSuccessful = true;
        $message = '';

        if (intval($_SERVER['CONTENT_LENGTH']) > 0 && $request->request->count() === 0) {
            $isSuccessful = false;
            $message = 'Total file upload max size exceed, Files are too large.';
        }

        $data = $this->constructMainBannerData(
            $request->files->get('bannerFile'),
            $request->get('fileName'),
            $request->get('isNew'),
            $request->get('link'),
            $request->get('order')
        );
        $applyImmediately = filter_var($request->get('applyImmediate' , false), FILTER_VALIDATE_BOOLEAN);

        if ($isSuccessful) {
            $cmsManager = $this->get('yilinker_backend.cms_manager');
            $cmsManager->saveMainBanners($data, $applyImmediately);
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'message'      => $message
        ));
    }

    /**
     * Construct main banner data
     *
     * @param $bannerFiles
     * @param $fileNames
     * @param $isNew
     * @param $links
     * @param $orders
     * @return array
     */
    private function constructMainBannerData ($bannerFiles, $fileNames, $isNew, $links, $orders)
    {
        $data = array();
        $ctr = 0;

        foreach ($fileNames as $fileName) {
            $data[$ctr] = array(
                'bannerFile' => isset($bannerFiles[$ctr]) && $bannerFiles[$ctr] instanceof File ? $bannerFiles[$ctr] : null,
                'fileName'   => $fileNames[$ctr],
                'isNew'      => $isNew[$ctr],
                'link'       => $links[$ctr],
                'order'      => $orders[$ctr]
            );
            $ctr++;
        }

        return $data;
    }

    /**
     * Render brand data
     *
     * @param $id
     * @return mixed
     */
    public function renderBrandDataAction ($id)
    {
        $em = $this->getDoctrine()->getManager();
        $brand = $em->getRepository('YilinkerCoreBundle:Brand')->find($id);
        $isNew = $id == 'new';
        $brandData = null;

        if ($brand instanceof Brand || $isNew) {
            $brandData = $isNew ? null: $this->get('yilinker_core.service.pages.pages')->getBrand($brand);
            $file = $this->get('yilinker_backend.cms_manager')->getTempJsonFile(CmsManager::TOP_BRANDS_JSON_FILE_NAME);

            if (!is_null($file) && !$isNew) {
                $brands = json_decode($file, true);

                if (isset($brands[$brand->getBrandId()])) {
                    $brandData = $brands[$brand->getBrandId()];
                    $brandData['products'] = $em->getRepository('YilinkerCoreBundle:Product')->findByProductId($brandData['products']);
                    $brandData['isTemp'] = true;
                    $brandData['image'] = $brandData['imageFileName'];
                }

            }
        }
        else {
            return $this->redirect($this->generateUrl('admin_home_page'));
        }

        $data = array(
            'brandData' => $isNew ?:$brandData,
            'isNew'     => $isNew
        );

        return $this->render('YilinkerBackendBundle:Cms:brand_details.html.twig', $data);
    }

    /**
     * Render brand list
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderBrandListAction (Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $service = $this->get('yilinker_core.service.cms.brand_lists');
        $nodes = $service->getHomePageBrands($page, $perPage);
        $totalNodes = $service->totalBrandCount;
        $tempBrands = $service->getTempRows();
        $data = compact('perPage', 'nodes', 'totalNodes', 'tempBrands');

        return $this->render('YilinkerBackendBundle:Cms:brand.html.twig', $data);
    }

    /**
     * Remove Brands
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeBrandsAction (Request $request)
    {
        $isSuccessful = $this->get('yilinker_backend.cms_manager')
                             ->removeBrands($request->get('brandIds', array()));

        return new JsonResponse($isSuccessful);
    }

    /**
     * Remove Stores
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function removeStoresAction (Request $request)
    {
        $isSuccessful = $this->get('yilinker_backend.cms_manager')
                             ->removeStores($request->get('storeIds', array()));

        return new JsonResponse($isSuccessful);
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
        $excludedBrands = $this->get('yilinker_core.service.pages.pages')->getBrandIdsInTopBrands();
        $excludedBrands = count($excludedBrands) > 0 ? array_diff($excludedBrands, array($request->get('excludedBrandId', 0))) : array();
        $brandEntities = $brandRepository->getBrandByName($brandKeyword, 10, false, false, $excludedBrands);
        $brandContainer = array();

        if ($brandEntities) {

            foreach ($brandEntities as $key => $brandEntity) {
                $brandContainer[$key]['id'] = $brandEntity->getBrandId();
                $brandContainer[$key]['value'] = $brandEntity->getName();
            }

        }

        return new JsonResponse($brandContainer);
    }

    /**
     * Update Brand CMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateBrandAction (Request $request)
    {
        $isSuccessful = true;

        if (intval($_SERVER['CONTENT_LENGTH']) > 0 && $request->request->count() === 0) {
            $isSuccessful = false;
            $message = 'Total file upload max size exceed, Files are too large.';
        }

        $formErrorService = $this->get('yilinker_core.service.form.form_error');
        $data = array(
            'brand'         => $request->get('brandId', null),
            'description'   => $request->get('description', ''),
            'isImageNew'    => $request->get('isImageNew', null),
            'image'         => array($request->files->get('image')),
            'imageFileName' => $request->get('imageFileName', ''),
            'products'      => explode(',', $request->get('productIds', '0'))
        );
        $form = $this->createForm('cms_brand_form')->submit($data);

        if ($form->isValid() && $isSuccessful == true) {
            $cmsManager = $this->get('yilinker_backend.cms_manager');
            $applyImmediately = filter_var($request->get('applyImmediate' , false), FILTER_VALIDATE_BOOLEAN);
            $isSuccessful = $cmsManager->saveBrand($data, $applyImmediately);
            $message = $isSuccessful ?: 'Server error, try again later.';
        }
        else if (!$form->isValid() && $isSuccessful == true){
            $isSuccessful = false;
            $message = $formErrorService->throwInvalidFields($form);
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'message'      => $message
        ));
    }

    /**
     * Render store list
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderStoreListAction(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $service = $this->get('yilinker_core.service.cms.store_lists');
        $nodes = $service->getHomePageStores($page, $perPage);
        $totalNodes = $service->totalStoreCount;
        $data = compact('perPage', 'nodes', 'totalNodes');

        return $this->render('YilinkerBackendBundle:Cms:seller.html.twig', $data);
    }

    /**
     * Render Seller Data for update
     *
     * @param $storeListNodeId
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function renderSellerDataAction($storeListNodeId, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $storeRepository = $em->getRepository('YilinkerCoreBundle:Store');
        $store = $storeRepository->find($id);
        $data = array();
        $storeListNodes = array(
            array(
                'id' => PagesService::STORE_LIST_NODE_ID_ONE,
                'name' => 'Top Seller'
            ),
            array(
                'id' => PagesService::STORE_LIST_NODE_ID_TWO,
                'name' => 'New Seller'
            ),
        );

        if ($store instanceof Store && in_array($storeListNodeId, array(PagesService::STORE_LIST_NODE_ID_ONE, PagesService::STORE_LIST_NODE_ID_TWO))) {
            $data = $this->get('yilinker_core.service.pages.pages')->getStoreDetailsInXml($storeListNodeId, $store);
            $file = $this->get('yilinker_backend.cms_manager')->getTempJsonFile(CmsManager::SELLER_JSON_FILE_NAME);

            if (!is_null($file)) {
                $stores = json_decode($file, true);

                if (isset($stores[$storeListNodeId][$id])) {
                    $data = $stores[$storeListNodeId][$id];
                    $data['products'] = $em->getRepository('YilinkerCoreBundle:Product')->findByProductId($stores[$storeListNodeId][$id]['productIds']);
                }

            }

        }

        return $this->render('YilinkerBackendBundle:Cms:seller_details.html.twig', compact('data', 'storeListNodes'));
    }

    /**
     * Update Seller cms
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateSellerAction(Request $request)
    {
        $isSuccessful = false;
        $message = 'Invalid Store';
        $productIds = explode(',', $request->get('productIds', '0'));

        if (count($productIds) > 0) {
            $cmsManager = $this->get('yilinker_backend.cms_manager');
            $applyImmediately = filter_var($request->get('applyImmediate' , false), FILTER_VALIDATE_BOOLEAN);
            $isSuccessful = $cmsManager->saveStore($request->get('storeId', 0),
                                                   $request->get('storeListNodeId'),
                                                   $productIds,
                                                   $applyImmediately,
                                                   $request->get('oldStoreId', null));
            $message = $isSuccessful ?: 'Server error, try again later.';
        }

        return new JsonResponse(array(
            'isSuccessful' => $isSuccessful,
            'message'      => $message,
        ));
    }

    /**
     * Get Store by store name excluding stores in a specific node in cms
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStoreByNameAction(Request $request)
    {
        $storeKeyword = $request->get('storeKeyword');
        $em = $this->getDoctrine()->getManager();
        $storeRepository = $em->getRepository('YilinkerCoreBundle:Store');
        $excludedStores = $this->get('yilinker_core.service.pages.pages')->getStoreIdsInStoreList($request->get('storeListNodeId'));
        $excludedStores = count($excludedStores) > 0 ? array_diff($excludedStores, array($request->get('excludedStoreId', 0))) : array();
        $storeEntities = $storeRepository->searchStoreByStoreName($storeKeyword, $excludedStores, 10);
        $storeContainer = array();

        if ($storeEntities) {

            foreach ($storeEntities as $key => $storeEntity) {
                $storeContainer[$key]['id'] = $storeEntity->getStoreId();
                $storeContainer[$key]['value'] = $storeEntity->getStoreName();
            }

        }

        return new JsonResponse($storeContainer);
    }

}
