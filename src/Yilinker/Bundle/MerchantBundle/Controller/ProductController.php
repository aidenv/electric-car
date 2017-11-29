<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;

class ProductController extends Controller
{
    /**
     * Render the merchant header 
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderProductManagementAction (Request $request)
    {
    	$user = $this->getUser();
        $page = $request->get("page", 1);
        $dateFrom = $request->get("dateFrom", null);
        $dateFrom = $dateFrom ? $dateFrom: '2015-01-01';
        $dateTo = $request->get("dateTo", null);
        $dateTo = $dateTo ? $dateTo: Carbon::instance(new \DateTime())->endOfYear()->format('Y-m-d');
        $categoryId = (int)$request->get("categoryId", null);
        $period = (int)$request->get("period", 1);
        $categoryIds = null;

        $em = $this->getDoctrine()->getManager();

        switch ($request->get("status", null)) {
            case 'active':
                $status = Product::ACTIVE;
                break;
            case 'inactive':
                $status = Product::INACTIVE;
                break;
            case 'draft':
                $status = Product::DRAFT;
                break;
            case 'delete':
                $status = Product::DELETE;
                break;
            case 'review':
                $status = Product::FOR_REVIEW;
                break;
            case 'rejected':
                $status = Product::REJECT;
                break;
            case 'for-completion':
                $status = Product::FOR_COMPLETION;
                break;
            default:
                $status = null;
                break;
        }

        if($user->isAffiliate() || strlen($dateFrom) == 0){
            $dateFrom = null;
        }

        if($user->isAffiliate() || strlen($dateTo) == 0){
            $dateTo = null;
        }

        if($categoryId == 0){
            $categoryId = null;
            $categoryIds = null;
        }

        if(!is_null($categoryId)){
            $categoryIds = $em->getRepository("YilinkerCoreBundle:CategoryNestedSet")
                                         ->getAllCategoriesByCategoryId($categoryId);
        }

        $productCategoryRepository = $em->getRepository("YilinkerCoreBundle:ProductCategory");
        $categories = $productCategoryRepository->getMainCategories();
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $countries = $tbCountry->filterBy()->setMaxResults(10)->getResult();

        $filters = compact("dateFrom", "dateTo", "categoryId", "period");
        $perPage = 6;
        $userProducts = $this->getProducts($user, $status, $dateFrom, $dateTo, $categoryIds, $page, $perPage, $request->getLocale());
        $totalResults = $userProducts["count"];
        $products = $userProducts["products"];
        $seller = $user;

        return $this->render('YilinkerMerchantBundle:Product:product_management.html.twig', compact(
            "products", 
            "status", 
            "categories",
            "countries",
            "totalResults", 
            "perPage", 
            "filters",
            "seller"
        ));
    }

    public function getProducts(User $user, $status, $dateFrom, $dateTo, $categoryId, $page, $perPage = 20, $locale)
    {
        if ($user->isAffiliate(false)) {
            $em = $this->getDoctrine()->getEntityManager();
            $tbInhouseProduct = $em->getRepository('YilinkerCoreBundle:InhouseProduct');
            $products = $tbInhouseProduct
                ->searchBy(array(
                    'affiliate'             => $user,
                    'statuses'              => $status,
                    'statuses.exclude'      => array(Product::FULL_DELETE, Product::DELETE),
                    'dateLastModified.from' => $dateFrom,
                    'dateLastModified.to'   => $dateTo,
                    'dateLastModified.DESC' => ''
                ))
                ->setLimit($perPage)
                ->page($page)
                ->getResult()
            ;
            $count = $tbInhouseProduct->getCount();

            return compact('count', 'products');
        }
        $productService = $this->get("yilinker_core.service.product.product");

        return $productService->getAllUserProducts($user, $status, $dateFrom, $dateTo, $categoryId, $page, $perPage, $locale);
    }


    /**
     * Returns authenticated user from oauth
     *
     * @return mixed
     */
    private function getAuthenticatedUser()
    {
        $tokenStorage = $this->container->get('security.token_storage');
        return $tokenStorage->getToken()->getUser();
    }

    /**
     * Renders Product Detail
     *
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function renderProductDetailAction ($slug)
    {
        $authenticatedUser = $this->getAuthenticatedUser();

        if ($authenticatedUser instanceof User) {
            $storeEntity = $authenticatedUser->getStore();

            $em = $this->getDoctrine()->getManager();
            $productRepository = $em->getRepository('YilinkerCoreBundle:Product');
            $product = $productRepository->findOneBy(array(
                'slug' => $slug,
            ));

            $productUnits = null;
            if ( (int) $storeEntity->getStoreType() === Store::STORE_TYPE_RESELLER) {
                $productUnits = $product->getUnits();
            }

            if (!isset($product) || $product->getStatus() === Product::FULL_DELETE) {
                return $this->redirect($this->generateUrl('merchant_product_management'));
            }

            $data = array (
                'product' => $product,
                'store'   => $storeEntity,
                'units'   => $productUnits
            );

            return $this->render('YilinkerMerchantBundle:Product:product_view.html.twig', $data);
        }
        else {
            return $this->redirect($this->generateUrl('user_merchant_login'), 301);
        }

    }

    public function countryModalAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $countries = $tbCountry->filterBy()->setMaxResults(10)->getResult();
        $data = compact('countries');

        return $this->render('YilinkerMerchantBundle:Product/modal:country_selection.html.twig', $data);
    }

    public function languageModalAction(Request $request)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbLanguage = $em->getRepository('YilinkerCoreBundle:Language');
        $languages = $tbLanguage->qb()->setMaxResults(10)->getResult();
        $data = compact('languages');

        return $this->render('YilinkerMerchantBundle:Product/modal:language_selection.html.twig', $data);
    }

    public function countrySetupAction(Request $request) 
    {
        $em = $this->getDoctrine()->getEntityManager();
        //get product
        $productId = $request->get('productId');
        $tbProduct = $em->getRepository('YilinkerCoreBundle:Product');
        $product = $tbProduct->find($productId);
        $this->throwNotFoundUnless($product, 'Product does not exist');
        
        //get country
        $countryCode = $request->get('countryCode');
        $tbCountry = $em->getRepository('YilinkerCoreBundle:Country');
        $country = $tbCountry->findOneByCode($countryCode);
        $this->throwNotFoundUnless($country, 'Country does not exist');

        //set country of tranlation
        $transListener = $this->get('yilinker_core.translatable.listener');
        $transListener->setCountry($countryCode);

        //create product country form
        $form = $this->createForm('product_country', $product, array('disabled' => !$product->getIsEditable()));
        $form->handleRequest($request);
        if ($form->isValid()) {
            $this->addFlash('success', 'Country setup succcesful');
            $em->flush();

            /**
             * Re-index elasticsearch product
             */
            /*$productPersister = $this->container->get('fos_elastica.object_persister.yilinker_online.product');            
            $productPersister->insertOne($product);*/
        }

        //remarks per country
        $tbProductRemarks = $em->getRepository('YilinkerCoreBundle:ProductRemarks');
        $remarks = $tbProductRemarks->findBy(array(
            'product' => $product,
            'countryCode' => $countryCode
        ), array(
            'dateAdded' => 'DESC'
        ));

        $form = $form->createView();
        $data = compact('country', 'form', 'remarks');

        return $this->render('YilinkerMerchantBundle:Product:product_country_setup.html.twig', $data);
    }
}
