<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Carbon\Carbon;
use \Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Entity\PromoType;
use Yilinker\Bundle\CoreBundle\Repository\PromoInstanceRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;

use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;
use Yilinker\Bundle\CoreBundle\Services\Redis\Keys as RedisKeys;

class HomeController extends YilinkerBaseController
{
    const HOME_FLASH_SALE_LIMIT = 4;

    const HOME_INSTANCE_TYPE_CURRENT = "current";

    const HOME_INSTANCE_TYPE_UPCOMING = "upcoming";

    const HOME_INSTANCE_TYPE_ENDED = "ended";

    public function comingSoonAction(Request $request)
    {
        $formData = $request->get('form', array());
        $contactForm = $this
            ->createFormBuilder()
            ->add('email', 'email')
            ->add('subject', 'text')
            ->add('body', 'textarea')
        ;

        $form = $contactForm->getForm();
        $form->submit($formData);

        if ($form->isValid()) {
            $message = \Swift_Message::newInstance()
                ->setSubject($formData['email'].' - '.$formData['subject'])
                ->setFrom('noreply@easyshop.ph')
                ->setTo('support@yilinker.ph')
                ->setBody($formData['body'], 'text/plain')
            ;
            $this->get('mailer')->send($message);
            $form = $contactForm->getForm();
        }

        $form = $form->createView();
        $data = compact('form');

        return $this->render('YilinkerFrontendBundle:Home:coming_soon.html.twig', $data);
    }

    /**
     * Renders the home page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     **/
    public function renderHomepageAction(Request $request)
    {
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')
                        ->fetchXML("home", "v3", "web");
        
        $homeXmlv2 = $this->get('yilinker_core.service.xml_resource_service')
                        ->fetchXML("home", "v2", "web");
                                        
        $nocache = $request->get('nocache', 'false') == 'true';

        $key = RedisKeys::HOME_DATA;
        $homeData = $this->getCacheValue($key, true);
        if(!$homeData || $nocache){
            $homeData = $pagesService->getWebv3Content($homeXml);
            $this->setCacheValue($key, $homeData);
        }

        foreach($homeData as $index => $data){
            if($data["layoutId"] == PagesService::HOMEPAGE_WEB_V6_CONTENT_FLASH_SALE){
                $homeData[$index]["content"]["flashSaleContent"] = $pagesService->constructHomeFlashSale(null, self::HOME_FLASH_SALE_LIMIT);
            }
        }

        $androidAppUrl = $this->container->getParameter('google_playstore_link');
        $iosAppUrl = $this->container->getParameter('appstore_link');
        $apkUrl = $this->container->getParameter('apk_link');
        $iosAppName = $this->container->getParameter('ios_app_name');

        $itemYoumaylike = $pagesService->getItemsYouMayLike ($homeXmlv2, 60, 0);

        return $this->render (
            'YilinkerFrontendBundle:Home:homepage.html.twig',
            array(
                'itemsYouMayLike'   => $itemYoumaylike,
                'homeData'          => $homeData,
                'androidAppUrl'     => $androidAppUrl,
                'iosAppUrl'         => $iosAppUrl,
                'apkUrl'            => $apkUrl,
                'iosAppName'        => $iosAppName
            )
        );
    }

    /**
     * render Sub categories in homepage
     *
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSubCategoriesAction ($id,$section=null)
    {
        $em = $this->getDoctrine()->getManager();
        $categoryRepository = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $categories = $categoryRepository->findCategoryByParentId($id);

        if(count($categories) > 0 && is_null($section)){
            $categories = new ArrayCollection($categories);
            $expr = Criteria::expr();
            $criteria = Criteria::create();
            $criteria->andWhere($expr->neq("image", ""));
            $criteria->andWhere($expr->neq("image", null));
            $categories = $categories->matching($criteria);
        }

        $categoryData = array();

        $key = "sub-categories-".$id;
        $categoryData = $this->getCacheValue($key, true);
        if(!$categoryData){
            if (sizeof($categories) > 0) {
                foreach ($categories as $category) {
                    $image = $category->getImage();
                    $categoryData[] = array(
                        'id'    => $category->getProductCategoryId(),
                        'name'  => $category->getName(),
                        'slug'  => $category->getSlug(),
                        'image' => $image
                    );
                }
            }
            $this->setCacheValue($key, $categoryData);
        }
        
        if ($section == 'footer') {
            $response =  $this->render ('YilinkerFrontendBundle:Base:footer_sub_category.html.twig', compact('categoryData'));
        } else {
            $response =  $this->render ('YilinkerFrontendBundle:Home:sub_category.html.twig', compact('categoryData'));
        } 
        
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * Renders the application header
     *
     * @param Request\Symfony\Component\HttpFoundation\Request $request
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function renderHeaderAction(Request $request, $store = null, $currentRoute = null)
    {
        $entityManager = $this->get("doctrine.orm.entity_manager");
        $cartService = $this->get('yilinker_front_end.service.cart')->apiMode(true);
        $wishlist = null;

        $primaryImage = null;
        $authorizationChecker = $this->get('security.authorization_checker');

        $messages = 0;
        $jwt = null;

        $entityManager = $this->get("doctrine.orm.entity_manager");

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->container
                                      ->get('security.token_storage')
                                      ->getToken()
                                      ->getUser();

            $userAvatar = $authenticatedUser->getPrimaryImage();
            $messages = $entityManager->getRepository('YilinkerCoreBundle:Message')
                                      ->getCountUnonepenedMessagesByUser($authenticatedUser);

            $wishlist = $cartService->getWishlist();

            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $jwt = $jwtService->encodeToken(array("userId" => $authenticatedUser->getUserId()));
        }

        $baseUri = $this->getParameter('frontend_hostname');
        $nodePort = $this->getParameter('node_messaging_port');

        $localeString = trim($this->getParameter('app.locales'), '|');
        $locales =  explode('|', $localeString);
        $cart = $cartService->apiMode(true)->getCart();
        $route = $this->container->get('request')->getPathInfo();

        $playstoreLink = $this->getParameter("google_playstore_link");
        $appstoreLink = $this->getParameter("appstore_link");
        $apkLink = $this->getParameter("apk_link");

        $yilinkerFacebook = $this->getParameter("yilinker_facebook");
        $yilinkerTwitter = $this->getParameter("yilinker_twitter");
        $yilinkerGoogle = $this->getParameter("yilinker_google");
        $globalDomainUrl = $this->getParameter('protocol').'://'.$this->getParameter('global_hostname').'?no_redirect';

        $key = "header-categories";
        $categories = $this->getCacheValue($key, true);
        if(!$categories){
            $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v2", "web");
            $pagesService = $this->get('yilinker_core.service.pages.pages');
            $categories = $pagesService->getV2HeaderCategories($homeXml);
            $this->setCacheValue($key, $categories);
        }

        $appCountryCode = $this->getAppCountry()->getCode();
        $countries = $entityManager->getRepository('YilinkerCoreBundle:Country')
                                   ->findAllWithExclude($appCountryCode);
        $key = "header-languages";
        $languages = $this->getCacheValue($key, true);
        if(!$languages){
            $languageEntities = $entityManager->getRepository('YilinkerCoreBundle:Language')
                                      ->filterBy()->getResult();
            $languages = array();
            foreach($languageEntities as $language){
                $languages[] = $language->toArray();
            }

            $this->setCacheValue($key, $languages);
        }

        return $this->render(
            'YilinkerFrontendBundle:Base:header.html.twig',
            compact(
                'currentRoute',
                'languages',
                'userAvatar',
                'cart',
                'wishlist',
                'messages',
                'store',
                'token',
                'baseUri',
                'nodePort',
                'jwt',
                'locales',
                'route',
                'playstoreLink',
                'appstoreLink',
                'apkLink',
                'yilinkerFacebook',
                'yilinkerTwitter',
                'yilinkerGoogle',
                'categories',
                'currentRoute',
                'globalDomainUrl',
                'countries'
            )
        );
    }

    public function renderIframesAction(Request $request)
    {
        $crmHostName = $this->getParameter("crm_hostname");
        return $this->render(
            "YilinkerFrontendBundle:Base:iframes.html.twig",
            compact("crmHostName")
        );
    }

    /**
     * Render Flash Sale Product List
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function webFlashSaleAction()
    {
        $promoManager = $this->get("yilinker_core.service.promo_manager");
        $promoInstances = $promoManager->getFlashSaleInstancesWithSameTime();
        $dateNow = new \DateTime();

        $currentPromoInstances = array();
        $upcomingPromoInstances = array();
        $completedPromoInstances = array();
        foreach($promoInstances as $key=>$promoInstance){
            $promoInstance["products"] = $this->getInstanceProducts($promoInstance["promoInstanceIds"]);
            if($promoInstance['dateTimeStart'] <= $dateNow && $dateNow < $promoInstance['dateTimeEnd']){
                array_push($currentPromoInstances, $promoInstance);
            }
            else if($promoInstance['dateTimeStart'] > $dateNow){
                array_push($upcomingPromoInstances, $promoInstance);
            }
            else if($promoInstance['dateTimeEnd'] < $dateNow){
                array_push($completedPromoInstances, $promoInstance);
            }
        }

        $response = $this->render('YilinkerFrontendBundle:FlashSale:product_list.html.twig', compact(
            'upcomingPromoInstances',
            'completedPromoInstances',
            'currentPromoInstances',
            'dateNow'
        ));

        return $response;
    }

    public function getInstanceProducts($promoInstanceIds = array()){
        $em = $this->get("doctrine.orm.entity_manager");
        $promoInstanceIds = explode('-', $promoInstanceIds);

        $productUnits = $em->getRepository("YilinkerCoreBundle:ProductUnit")
                           ->getPromoInstanceProductUnits(
                                $promoInstanceIds,
                                null,
                                null,
                                Carbon::now()->endOfDay()
                           );

        $promoManager = $this->get("yilinker_core.service.promo_manager");
        return $promoManager->constructFlashSaleProducts($productUnits);
    }

    /**
     * Renders the application sidebar
     * @param  SimpleXMLElement $homeXml
     * @param  array  $productsData
     * @param  array  $productUnitsData
     * @param  array  $productCategories
     * @return
     */
    public function renderSidebarAction(
        $homeXml = null,
        $productsData = array(),
        $productUnitsData = array(),
        $productCategories = array()
    )
    {
        if(is_null($homeXml)){
            $homeXml = $this->get('yilinker_core.service.xml_resource_service')->fetchXML("home", "v1", "web");

            if(property_exists($homeXml, 'sidebar')){
                $sidebarXml = $homeXml->sidebar;
                $data = $this->getXMLProductsAndCategories($sidebarXml);
                extract($data);
            }
        }

        $yilinkerFacebook = $this->getParameter("yilinker_facebook");
        $yilinkerTwitter = $this->getParameter("yilinker_twitter");
        $yilinkerGoogle = $this->getParameter("yilinker_google");

        $response = $this->render(
            'YilinkerFrontendBundle:Base:sidebar.html.twig',
            compact(
                "homeXml",
                "productsData",
                "productUnitsData",
                "productCategories",
                "yilinkerFacebook",
                "yilinkerTwitter",
                "yilinkerGoogle"
            )
        );

        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * Render footer
     *
     * @param Request $request
     */
    public function renderFooterAction(Request $request)
    {
        $entityManager = $this->get("doctrine.orm.entity_manager");
        $productCount = $entityManager->getRepository('YilinkerCoreBundle:Product')
                                      ->getActiveProductCount();
        $storeCount = $entityManager->getRepository('YilinkerCoreBundle:Store')
                                    ->getNumberOfAccreditedStores();

        $form = $this->createForm('email_newsletter', null);

        $yilinkerFacebook = $this->getParameter("yilinker_facebook");
        $yilinkerTwitter = $this->getParameter("yilinker_twitter");
        $yilinkerGoogle = $this->getParameter("yilinker_google");

        $androidAppUrl = $this->container->getParameter('google_playstore_link');
        $iosAppUrl = $this->container->getParameter('appstore_link');
        $apkUrl = $this->container->getParameter('apk_link');

        $tradingUrl = $this->container->getParameter('ylt_app_hostname');
        $expressUrl = $this->container->getParameter('ylx_hostname');
        $supportEmail = $this->container->getParameter('reports_csr_email');
        $supportMobile = $this->container->getParameter('support_contact_number');

        $categoryRepository = $this->getDoctrine()
                                   ->getRepository('YilinkerCoreBundle:ProductCategory');

        $categories = $categoryRepository->getMainCategories("ASC", "name");

        $response = $this->render('YilinkerFrontendBundle:Base:footer_public.html.twig', array(
            'newsletterForm'   => $form->createView(),
            'productCount'     => $productCount,
            'storeCount'       => $storeCount,
            'yilinkerFacebook' => $yilinkerFacebook,
            'yilinkerTwitter'  => $yilinkerTwitter,
            'yilinkerGoogle'   => $yilinkerGoogle,
            'androidAppUrl'    => $androidAppUrl,
            'iosAppUrl'        => $iosAppUrl,
            'apkUrl'           => $apkUrl,
            'expressUrl'       => $expressUrl,
            'tradingUrl'       => $tradingUrl,
            'supportEmail'     => $supportEmail,
            'supportMobile'    => $supportMobile,
            'categories'       => $categories
        ));

        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    /**
     * Render footer
     *
     * @param Request $request
     */
    public function renderStoreFooterAction(Request $request)
    {
        $form = $this->createForm('email_newsletter', null);
        $newsletterForm = $form->createView();
        $yilinkerFacebook = $this->getParameter("yilinker_facebook");
        $yilinkerTwitter = $this->getParameter("yilinker_twitter");
        $yilinkerGoogle = $this->getParameter("yilinker_google");

        $androidAppUrl = $this->container->getParameter('google_playstore_link');
        $iosAppUrl = $this->container->getParameter('appstore_link');
        $apkUrl = $this->container->getParameter('apk_link');
        $supportMobile = $this->container->getParameter('support_contact_number');

        $response =  $this->render('YilinkerFrontendBundle:Base:footer_store.html.twig', compact(
            "newsletterForm",
            "yilinkerFacebook",
            "yilinkerTwitter",
            "yilinkerGoogle",
            "androidAppUrl",
            "iosAppUrl",
            "apkUrl",
            "supportMobile"
        ));

        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    private function getXMLProductsAndCategories($xml)
    {
        $xmlParserService = $this->get('yilinker_core.service.pages.xml_parser');
        $pagesService = $this->get('yilinker_core.service.pages.pages');
        $entityManager = $this->get("doctrine.orm.entity_manager");

        $productIds = $xmlParserService->getAllNodeValues($xml, 'product');
        $productUnitIds = $xmlParserService->getAllNodeAttributeValues($xml, 'product', 'unit');
        $productRepository = $entityManager->getRepository("YilinkerCoreBundle:Product");
        $products = $productRepository->loadProductsIn($productIds, true, Product::ACTIVE);

        $productUnitRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductUnit");
        $productUnits = $productUnitRepository->loadProductUnitsIn($productUnitIds, null, true, Product::ACTIVE);
        $productsData = $pagesService->constructProducts($products);
        $productUnitsData = $pagesService->constructProductUnits($productUnits);

        $attributeCategoryIds = $xmlParserService->getAllNodeAttributeValues($xml, 'category', 'categoryId');
        $nodeCategoryIds = $xmlParserService->getAllNodeValues($xml, 'categoryId');
        $categoryIds = array_unique(array_values(array_merge($attributeCategoryIds, $nodeCategoryIds)));

        $productCategoryRepository = $entityManager->getRepository("YilinkerCoreBundle:ProductCategory");
        $productCategories = $productCategoryRepository->loadProductCategoriesIn($categoryIds);

        return compact("productsData", "productUnitsData", "productCategories");
    }

    /**
     * Render Make a Wish Promo pages
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function aboutMakeAWishAction()
    {
        $response = $this->render('YilinkerFrontendBundle:MakeAWish:about_the_promo.html.twig');

        return $response;
    }

    public function howToJoinMakeAWishAction()
    {
        $response = $this->render('YilinkerFrontendBundle:MakeAWish:how_to_join.html.twig');

        return $response;
    }

    public function howToRegisterMakeAWishAction()
    {
        $response = $this->render('YilinkerFrontendBundle:MakeAWish:how_to_register.html.twig');

        return $response;
    }

    public function howToCreateAnEntryMakeAWishAction()
    {
        $response = $this->render('YilinkerFrontendBundle:MakeAWish:how_to_create_an_entry.html.twig');

        return $response;
    }

    public function criteriaForClaimingThePrizeMakeAWishAction()
    {
        $response = $this->render('YilinkerFrontendBundle:MakeAWish:criteria_for_claiming_the_prize.html.twig');

        return $response;
    }

    /**
     * Render Privacy Policy pages
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function privacyPolicyAction()
    {
        $supportEmail = $this->container->getParameter('reports_csr_email');
        $supportContactNumber = $this->container->getParameter('support_contact_number');
        $response = $this->render('YilinkerFrontendBundle:PrivacyPolicy:privacy_policy.html.twig', array(
            "supportEmail"         => $supportEmail,
            "supportContactNumber" => $supportContactNumber,
        ));

        return $response;
    }

    /**
     * Render Voucher for first time purchase
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function giftVoucherAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:gift_voucher.html.twig');

        return $response;
    }

    /**
     * Render view more page for products
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function viewMoreAction()
    {
        $response = $this->render('YilinkerFrontendBundle:ViewMore:view_more.html.twig');
    }

    /**
     * Render shop wise at the lowest price promo
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function shopWiseAction()
    {
        $supportContactNumber = $this->container->getParameter('support_contact_number');
        $response = $this->render('YilinkerFrontendBundle:Promo:shop_wise.html.twig', array(
            "supportContactNumber" => $supportContactNumber,
        ));

        return $response;
    }

    /**
     * Render instant free load promo
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function instantFreeLoadAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:instant_free_load.html.twig');

        return $response;
    }

    /**
     * Render instant Summer Kick off
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function summerKickOffAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:summer_kick_off.html.twig');

        return $response;
    }

    /**
     * Render Chat Box
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chatBoxAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Chat:chat_frame.html.twig');

        return $response;
    }

    /**
     * Render Chat Box
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function chatCompressedAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Chat:chat_compressed_circle.html.twig');
    }

    /**
     * Render instant Midnight Sale
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function midnightSalesAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:midnight_sales.html.twig');

        return $response;
    }

    /**
     * Render instant Labor Day Essentials
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function laborDayEssentialsAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:labor_day_essentials.html.twig');

        return $response;
    }

    /**
     * Render instant Mom and Me
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function momAndMeAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:mom_and_me.html.twig');

        return $response;
    }

    /**
     * Render instant Extended Summer
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function extendedSummerAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:extended_summer.html.twig');

        return $response;
    }

    /**
     * Render instant Extended Summer Offer
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function extendedSummerOfferAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:extended_summer_offer.html.twig');

        return $response;
    }

    /**
     * Render instant Extended Summer Offer
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function backtoSchoolPackagesAction(Request $request)
    {
        $ismobile = $this->isMobile($request);
        $homeXml = $this->get('yilinker_core.service.xml_resource_service')
                        ->fetchXML("home", "v3", "web");
        $data = json_decode(json_encode($homeXml));
        $backToSchoolPromoPackage = $data->backToSchoolPromoPackage;

        $response = $this->render('YilinkerFrontendBundle:Promo:back_to_school_packages.html.twig', compact('ismobile','backToSchoolPromoPackage'));

        return $response;
    }

    public function yExclusivesCaiteCoupleWatchAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:y_exclusives_caite_couple_watch.html.twig');

        return $response;
    }

     public function yExclusivesWaterproofEditionAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:y_exclusives_waterproof_edition.html.twig');

        return $response;
    }

    public function youngForeverAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:young_forever.html.twig');

        return $response;
    }

    public function deskCompanionAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:desk_companion.html.twig');

        return $response;
    }

    public function shopAndWinGymMembershipPromoAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:shop_and_win_gym_membership.html.twig');

        return $response;
    }

    public function teamSuperheroAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:team_superhero.html.twig');

        return $response;
    }

    public function freegoFreestyleAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:freego_freestyle.html.twig');

       return $response;
    }

    public function heddiKateFashionCollectionAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:heddi_kate_fashion_collection.html.twig');

        return $response;
    }

    public function mbcMusicShowAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Promo:mbc_music_show_champion_in_manila.html.twig');

        return $response;
    }

    public function enjoyTheSipAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:enjoy_the_sip.html.twig');

       return $response;
    }


    public function pokemonCollectiblesAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:pokemon_collectibles.html.twig');

       return $response;
    }

    public function buy2pairsGetRefundAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:buy_2_pairs_get_refund.html.twig');

       return $response;
    }

    public function earlyChristmasSaleAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:early_christmas_sale.html.twig');

       return $response;
    }

    public function sdsBionicScreenProtectorAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:sds_bionic_screen_protector.html.twig');
        return $response;
    }

    public function payDaySaleAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:payday_sale.html.twig');
       return $response;
    }

    public function yiboFamilyBundleAction()
    {
       $response = $this->render('YilinkerFrontendBundle:Promo:yibo_family_bundle.html.twig');
       return $response;
    }

}
