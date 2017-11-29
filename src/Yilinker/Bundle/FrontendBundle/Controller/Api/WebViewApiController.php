<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserPoint;
use Yilinker\Bundle\CoreBundle\Repository\PromoInstanceRepository;

/**
 * Class WebViewApiController
 * @package Yilinker\Bundle\FrontendBundle\Controller\Api
 */
class WebViewApiController extends YilinkerBaseController
{

    const WEB_VIEW_LIMIT = 10;

    /**
     * Render daily login mechanics
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderDailyLoginAction(Request $request)
    {
        $authenticatedUser = $this->container
                                  ->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();

        if ($authenticatedUser instanceof User) {
            $dailyLoginService = $this->get('yilinker_front_end.service.user_promo.daily_login');
            $isUserQualified = $dailyLoginService->isUserQualified($authenticatedUser);
            $isSuccess = false;

            if ($isUserQualified) {
                $dailyLoginService->registerDailyLoginPromo($authenticatedUser);
                $isSuccess = true;
            }

            $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
            $pagesService = $this->get('yilinker_core.service.pages.pages');
            $dailyLoginData = $pagesService->getDailyLoginData($homeXml);
            $em = $this->getDoctrine()->getManager();
            $userPointRepository = $em->getRepository('YilinkerCoreBundle:UserPoint');
            $dailyLoginPoints = $userPointRepository->filterBy(array('user' => $authenticatedUser, 'type' => UserPoint::DAILY_LOGIN))
                                                    ->getSum('this.points');

            return $this->render('YilinkerFrontendBundle:WebView:daily-login.html.twig', compact('dailyLoginData', 'isSuccess', 'dailyLoginPoints'));
        }
        else {
            return $this->redirect($this->generateUrl('home_page'));
        }

    }

    /**
     * Register to daily login promo
     *
     * @return JsonResponse
     */
    public function registerToDailyLoginAction ()
    {
        $authenticatedUser = $this->container
                                  ->get('security.token_storage')
                                  ->getToken()
                                  ->getUser();
        $response = array (
            'isSuccessful' => false,
            'message'      => 'Login to continue',
            'data'         => ''
        );

        if ($authenticatedUser instanceof User) {
            $dailyLoginService = $this->get('yilinker_front_end.service.user_promo.daily_login');
            $isUserQualified = $dailyLoginService->isUserQualified($authenticatedUser);

            $response = array (
                'isSuccessful' => true,
                'message'      => 'User has already earned a point today.',
                'data'         => ''
            );

            if ($isUserQualified) {
                $dailyLoginService->registerDailyLoginPromo($authenticatedUser);

                $response = array (
                    'isSuccessful' => true,
                    'message'      => '',
                    'data'         => ''
                );
            }

        }

        return new JsonResponse($response);
    }

    /**
     * Render flash sale
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function flashSaleAction(Request $request)
    {
        $promoManager = $this->get("yilinker_core.service.promo_manager");
        $promoInstances = $promoManager->getFlashSaleInstancesWithSameTime();
        $dateNow = new \DateTime();

        return $this->render('YilinkerFrontendBundle:WebView:flash-sales.html.twig', compact('promoInstances', 'dateNow'));
    }

    /**
     * Get flash sale products
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function flashSaleProductsAction(Request $request)
    {
        $promoInstanceId = $request->get("promoInstanceId", 0);
        $page = $request->get("page", 1);
        $offset = $this->getOffset(self::WEB_VIEW_LIMIT, $page);

        $em = $this->getDoctrine()->getManager();

        $promoInstance = explode('-', $promoInstanceId);

        if ($promoInstance) {
            $productUnits = $em->getRepository("YilinkerCoreBundle:ProductUnit")
                               ->getPromoInstanceProductUnits(
                                    $promoInstance,
                                    self::WEB_VIEW_LIMIT,
                                    $offset,
                                    Carbon::now()->endOfDay()
                               );

            $promoManager = $this->get("yilinker_core.service.promo_manager");

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Products",
                "data" => array(
                    "products" => $promoManager->constructFlashSaleProducts($productUnits)
                )
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => "Invalid promo instance.",
            "data" => array()
        ), 400);
    }

    /**
     * Render Mobile category page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderCategoryWebViewAction (Request $request)
    {
        $xml = $this->get('yilinker_core.service.xml_resource_service')
                        ->fetchXML("home", "v3", "web");
        $layout = $xml->layout;
        $categoryIds = array();
        foreach ($layout->categorySideBar->category as $category) {
            array_push($categoryIds, (string)$category->categoryId);
        }

        $em = $this->getDoctrine()->getManager();
        $productCategoryRepository = $em ->getRepository('YilinkerCoreBundle:ProductCategory');
        $mainCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds, false);        
        //$mainCategories = $productCategoryRepository->getMainCategories('ASC');
        
        $imageDirectory = $productCategoryRepository::CATEGORY_IMAGE_DIR;
        $v3path = $this->getV3Path();
        $categories = array();

        if (sizeof($mainCategories) > 0) {

            foreach ($mainCategories as $mainCategory) {
                $childCategories = $productCategoryRepository->findByParent($mainCategory->getProductCategoryId());
                $categories[] = array (
                    'main'            => $mainCategory,
                    'childCategories' => $childCategories
                );
            }

        }

        return $this->render('YilinkerFrontendBundle:WebView:category-view.html.twig', compact('categories', 'imageDirectory','v3path'));
    }

    /**
     * Render store view
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderStoresWebViewAction(Request $request)
    {
        $page = 0;
        $node = $request->get('node', 'hotStore');
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $stores = $pagesService->getStoreDataWithPages($homeXml, $node, self::WEB_VIEW_LIMIT, $page);

        return $this->render('YilinkerFrontendBundle:WebView:store-view.html.twig', $stores);
    }

    /**
     * Get stores in xml
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getStoresAction (Request $request)
    {
        $page = $request->get("page", 1);
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $stores = $pagesService->getStoreDataWithPages($homeXml, self::WEB_VIEW_LIMIT, $page)['stores'];

        return new JsonResponse(array (
            "isSuccessful" => true,
            "message" => "Stores",
            "data" => sizeof($stores) > 0 ? $stores : 0
        ), 200);
    }

    /**
     * Render product list by node
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productListAction (Request $request)
    {
        $node = $request->get('node', 'hotItems');
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $v3path = $this->getV3Path();
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $products = $pagesService->getProductsByNode($node, $homeXml, self::WEB_VIEW_LIMIT, 1);

        if (sizeof($products) > 0) {
            return $this->render('YilinkerFrontendBundle:WebView:product-list.html.twig', compact('products', 'node','v3path'));
        }
        else {
            throw $this->createNotFoundException('404');
        }

    }

    /**
     * Get products by node and page
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getProductsByNodeAction (Request $request)
    {
        $page = (int) $request->get("page", 1);
        $node = $request->get('node', 'hotItems');

        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $productData = $pagesService->getProductsByNode($node, $homeXml, self::WEB_VIEW_LIMIT, $page)['products'];;
        $productData['data'] = isset($productData['data']) ? array_values($productData['data']) : array();

        return new JsonResponse(array (
            "isSuccessful" => true,
            "message" => "Products",
            "data" => count($productData['data']) > 0 ? $productData : array(
                'data' => array(),
            ),
        ), 200);
    }

    /**
     * Get offset
     *
     * @param int $limit
     * @param int $page
     * @return int
     */
    private function getOffset($limit = 10, $page = 0)
    {
        return $page > 1 ? $limit * ($page-1) : 0;
    }

    /**
     * Render download app
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function downloadAppAction (Request $request)
    {
        $androidAppUrl = $this->container->getParameter('google_playstore_link');
        $iosAppUrl = $this->container->getParameter('appstore_link');
        $apkUrl = $this->container->getParameter('apk_link');

        $response = $this->render('YilinkerFrontendBundle:WebView:app.html.twig', array(

            'androidAppUrl'    => $androidAppUrl,
            'iosAppUrl'        => $iosAppUrl,
            'apkUrl'           => $apkUrl,
        ));

        return $response;
    }

/**
     * Render Home Featured Products
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function featuredProductsAction ()
    {
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "mobile");
        $xmlToArray = json_decode(json_encode($homeXml), true);
        $promos = array();
        if (isset($xmlToArray['backToSchoolPromo'])) {
            $promos = $xmlToArray['backToSchoolPromo']['promo'];
        }
        $response = $this->render('YilinkerFrontendBundle:WebView:featured_products.html.twig', array('backToSchoolPromo' => $promos ));
        
        return $response;
    }
}
