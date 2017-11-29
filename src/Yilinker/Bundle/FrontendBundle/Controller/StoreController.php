<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;

class StoreController extends Controller
{
    const SEARCH_RESULTS_PER_PAGE = 15;

    protected $storeService;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->storeService = $this->get('yilinker_core.service.entity.store');
    }

    /**
     * Check if the authenticated user is following the seller
     *
     * @param Yilinker\Bundle\FrontendBundle\Entity\User $seller
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function isFollowingAction($seller)
    {
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $isFollowing = $this->isFollowing($seller, $authenticatedUser);

        return $this->render('YilinkerFrontendBundle:Store:store_follow_button.html.twig', array(
            'isFollowing' => $isFollowing,
            'seller' => $seller,
        ));
    }

    /**
     * Renders Store page
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function productsAction(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->forward('YilinkerFrontendBundle:Store:productList', array('request' => $request));
        }

        $slug = $request->get('slug');
        $store = $this->storeService->findBySlug($slug);
        $seller = $store->getUser();
        $sellerId = $seller->getUserId();
        $filterMetaData = $this->storeService->filterMetaData($store);
        $merchantHostName = $this->getParameter('merchant_hostname');

        $attributesEnabled = true;

        $data = compact (
            'sellerId',
            'store',
            'filterMetaData',
            'merchantHostName',
            'attributesEnabled'
        );

        return $this->render('YilinkerFrontendBundle:Store:store_home.html.twig', $data);
    }

    public function productsRedirectAction(Request $request, $slug)
    {
        return new RedirectResponse($this->generateUrl("store_page_products", compact("slug")), 301);
    }

    public function productListAction(Request $request)
    {
        $justrow = $request->get('justrow', false);
        $slug = $request->get('slug');
        $store = $this->storeService->findBySlug($slug);
        $seller = $store->getUser();
        $q = $request->request->get('q', '');
        $q = $q ? $q: $request->query->get('q', '');
        $page = $request->get('page', 1);

        $priceRange = $request->get('priceRange');
        $priceRange = explode(';', $priceRange);
        $priceFrom = array_shift($priceRange);
        $priceTo = array_shift($priceRange);

        $brands = $request->get('brands');
        $attributes = $request->get('attributes');

        $sorting = $request->get('sorting', ProductRepository::BYDATE.'~'.ProductRepository::DIRECTION_DESC);
        $sorting = explode('~', $sorting);
        $sortType = array_shift($sorting);
        $sortDirection = array_shift($sorting);

        $categoryId = $request->get('categoryId');
        $customCategoryId = $request->get('customCategoryId');

        $perPage = 12;

        $productSearchService = $this->get('yilinker_core.service.search.product');
        $productSearch = $productSearchService->searchProductsWithElastic(
            $q,
            $priceFrom,
            $priceTo,
            $categoryId,
            $seller->getId(),
            $brands,
            null,
            $sortType,
            $sortDirection,
            null,
            $page,
            $perPage,
            true,
            true,
            $attributes,
            null,
            null,
            null,
            $seller->isAffiliate(false) ? true: null,
            $customCategoryId
        );

        $lastPage = $productSearch["totalPage"];
        $data = compact('store', 'productSearch', 'perPage', 'lastPage');
        $template = $justrow ? 'product_list_row.html.twig': 'product_list.html.twig';
        $this->storeService->incrementStoreview($store);

        return $this->render('YilinkerFrontendBundle:Store:'.$template, $data);
    }

    public function aboutAction(Request $request)
    {
        $slug = $request->get('slug');
        $store = $this->storeService->findBySlug($slug);
        $supportMobile = $this->container->getParameter('support_contact_number');
        $data = compact('store', 'supportMobile');

        return $this->render('YilinkerFrontendBundle:Store:store_about.html.twig', $data);
    }

    public function aboutRedirectAction(Request $request, $slug)
    {
        $supportMobile = $this->container->getParameter('support_contact_number');
        return new RedirectResponse($this->generateUrl("store_page_about", compact("slug", "supportMobile")), 301);
    }

    public function feedbackAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbUserFeedback = $em->getRepository('YilinkerCoreBundle:UserFeedback');

        $slug = $request->get('slug');
        $store = $this->storeService->findBySlug($slug);
        $reviews = $tbUserFeedback->findByReviewee($store);
        $supportMobile = $this->container->getParameter('support_contact_number');
        $data = compact('store', 'reviews', 'supportMobile');

        return $this->render('YilinkerFrontendBundle:Store:store_feedback.html.twig', $data);
    }

    public function feedbackRedirectAction(Request $request, $slug)
    {
        return new RedirectResponse($this->generateUrl("store_page_feedback", compact("slug")), 301);
    }

    private function isFollowing ($seller, $authenticatedUser)
    {
        $followManager = $this->get('yilinker_front_end.service.user.user_follow');
        $followManager->setAuthenticatedUser($authenticatedUser);

        return (bool) $followManager->isFollowed($seller);
    }

    /**
     * Follow Seller
     * @param Request $request
     * @return mixed
     */
    public function followSellerAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $followManager = $this->get('yilinker_front_end.service.user.user_follow');
        $followManager->setAuthenticatedUser($authenticatedUser);
        $sellerId = $request->request->get('sellerId', 0);
        $seller = $em->getRepository('YilinkerCoreBundle:User')
                     ->findOneBy(array(
                         "userId" => $sellerId,
                         "userType" => User::USER_TYPE_SELLER
                     ));

        $isSuccessful = false;

        if ($seller !== null) {
            $contactService = $this->get("yilinker_core.service.contact.contact_service");
            $contactService->addToContact($authenticatedUser, $seller);

            $followManager->followSeller($seller);
            $isSuccessful = $this->isFollowing($seller, $authenticatedUser);
        }

        return new JsonResponse($isSuccessful);
    }

    /**
     * UnFollow seller
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unFollowSellerAction (Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $tokenStorage = $this->container->get('security.token_storage');
        $authenticatedUser = $tokenStorage->getToken()->getUser();
        $followManager = $this->get('yilinker_front_end.service.user.user_follow');
        $followManager->setAuthenticatedUser($authenticatedUser);
        $sellerId = $request->request->get('sellerId', 0);
        $seller = $em->getRepository('YilinkerCoreBundle:User')
                     ->findOneBy(array(
                         "userId" => $sellerId,
                         "userType" => User::USER_TYPE_SELLER
                     ));

        $isSuccessful = false;

        if ($seller !== null) {
            $userFollowEntity = $em->getRepository('YilinkerCoreBundle:UserFollow')
                                   ->findOneBy(array(
                                       "follower" => $authenticatedUser,
                                       "followee" => $seller
                                   ));
            $followManager->unfollowSeller($seller, $userFollowEntity);
            $isSuccessful = $this->isFollowing($seller, $authenticatedUser) ? false : true;
        }

        return new JsonResponse($isSuccessful);
    }


    /**
     * Render Seller Search
     *
     * @param Symfony\Component\HttpFoundation\Request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchBySellerAction(Request $request)
    {
        $sortType = $request->get('sortBy', null);
        $sortDirection = $request->get('sortDirection', 'DESC');
        $page = (int) $request->get('page', 1);
        $queryString = $request->get('query', null);

        if($queryString === null || trim($queryString) === ""){
            return $this->redirectToRoute('all_categories');
        }

        $storeSearchResult = $this->get('yilinker_core.service.search.store')
                             ->searchStoreWithElastic(
                                 $queryString,
                                 0,
                                 $sortType,
                                 $sortDirection,
                                 $page,
                                 self::SEARCH_RESULTS_PER_PAGE
                             );

        $productSearchResult = $this->get('yilinker_core.service.search.product')
                                    ->searchProductsWithElastic(
                                        $queryString
                                    );

        $parameters = $request->query->all();
        if(isset($parameters['page'])){
            unset($parameters['page']);
        }

        return $this->render('YilinkerFrontendBundle:Search:search_page_by_seller.html.twig', array(
            'stores'                  => $storeSearchResult['stores'],
            'totalStoreResultCount'   => $storeSearchResult['totalResultCount'],
            'totalProductResultCount' => $productSearchResult['totalResultCount'],
            'totalPages'              => ceil($storeSearchResult['totalResultCount']/self::SEARCH_RESULTS_PER_PAGE),
            'page'                    => $page,
            'aggregations'            => array(),
            'query'                   => $queryString,
            'parameters'              => $parameters,
        ));
    }

    /**
     * Render the store card
     *
     * @param int $storeId
     * @return Response
     */
    public function renderStoreCardAction($storeId)
    {
        $em = $this->getDoctrine()->getManager();
        $store = $em->getRepository('YilinkerCoreBundle:Store')
                    ->getStoreByStoreId($storeId);

        $response = $this->render('YilinkerFrontendBundle:Store:store_card.html.twig', array('store' => $store));
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->setSharedMaxAge(3600);

        return $response;
    }

}
