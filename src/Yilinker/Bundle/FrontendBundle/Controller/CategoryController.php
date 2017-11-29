<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\ProductCategory;
use Yilinker\Bundle\CoreBundle\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\JsonResponse;

class CategoryController extends YilinkerBaseController
{

    const PRODUCTS_PER_PAGE = 18;
    
    /**
     * Render All Categories Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function allCategoriesAction()
    {
        $categoryRepository = $this->getDoctrine()
                                   ->getRepository('YilinkerCoreBundle:ProductCategory');
        $categories = $categoryRepository->getMainCategories("ASC", "name");
        
        return $this->render('YilinkerFrontendBundle:Category:all_categories.html.twig', array('categories' => $categories));
    }

    /**
     * Render category page
     *
     * @param string $slug
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return  \Symfony\Component\HttpFoundation\Response
     */
    public function renderCategoryPageAction($slug = null, Request $request)
    {
        $categoryRepository = $this->getDoctrine()
                                   ->getRepository('YilinkerCoreBundle:ProductCategory');
        $category =  $categoryRepository->findCategoryBySlug($slug, false);

        if(!$category){
            throw $this->createNotFoundException('The category does not exist');
        }

        $cmsContent = $this->get('yilinker_core.service.pages.pages')
                           ->getMainCategoryContent($slug); 

        if($category->getParent()->getProductCategoryId() === ProductCategory::ROOT_CATEGORY_ID && empty($cmsContent) === false){
            return $this->render('YilinkerFrontendBundle:Category:main_category_page.html.twig', array(
                'category' => $category,
                'cmsContent' => $cmsContent,
            ));
        }
        else{
            $nestedSetCategoryRepository = $this->getDoctrine()
                                                ->getRepository('YilinkerCoreBundle:CategoryNestedSet');            
            $categoryId = $category->getProductCategoryId();
            $parentCategories = $nestedSetCategoryRepository->getAncestorCategories($categoryId);

            $sortType = $request->query->get('sortBy', ProductRepository::BYDATE);
            $sortDirection = $request->query->get('sortDirection', ProductRepository::DIRECTION_DESC);
            $page = $request->query->get('page', 1);
            $priceFrom = $request->query->get('priceFrom', 0);
            $priceTo = $request->query->get('priceTo', null);
            $brands = $request->query->get('brands', null);
            if($brands){
                $brands = explode(',', $brands);
            }
            $subcategoryIds = $request->query->get('subcategories', null);
            if($subcategoryIds){
                $subcategoryIds = explode(',', $subcategoryIds);
            }

            $childrenCategories = $nestedSetCategoryRepository->getChildrenCategories($categoryId);
            $key = "category-page-children-".$categoryId;
            $categoryIds = $this->getCacheValue($key, true);
            if(!$categoryIds){
                $categoryIds = array();
                foreach($childrenCategories as $childCategory){
                    array_push($categoryIds,$childCategory->getProductCategoryId());
                }
                $this->setCacheValue($key, $categoryIds);
            }

            $categoryIds = array($categoryId);
            $searchResults = $this->get('yilinker_core.service.search.product')
                                  ->searchProductsWithElastic(
                                      null,
                                      $priceFrom,
                                      $priceTo,
                                      $categoryIds,
                                      null,
                                      $brands,
                                      $subcategoryIds,
                                      $sortType,
                                      $sortDirection,
                                      null,
                                      $page,
                                      self::PRODUCTS_PER_PAGE,
                                      true
                                  ); 

            $aggregationSearchResults = $this->get('yilinker_core.service.search.product')
                                             ->searchProductsWithElastic(
                                                 null, null, null, $categoryIds,
                                                 null, null, null, null, null,
                                                 null, 1, 1, false,
                                                 $getResults = false
                                             ); 

            $parameters = $request->query->all();
            $parameters['slug'] = $slug;
            if(isset($parameters['page'])){
                unset($parameters['page']);
            }
            
            return $this->render('YilinkerFrontendBundle:Category:sub_category.html.twig', array(
                'category' => $category,
                'breadcrumbs' => $parentCategories,
                'products' => $searchResults['products'],
                'totalPages' => ceil($searchResults['totalResultCount']/self::PRODUCTS_PER_PAGE),
                'aggregations' => $aggregationSearchResults['aggregations'],
                'page' => $page,
                'parameters' => $parameters,
            ));
        }
    }


    public function getCategoryByIdAction(Request $request)
    {
        $id = $request->get('id');
        $priceFrom = $request->get('priceFrom');
        $priceTo = $request->query->get('priceTo');
        $sortType = $request->query->get('sortBy', ProductRepository::BYDATE);
        $sortDirection = $request->query->get('sortDirection', ProductRepository::DIRECTION_DESC);

        $categoryRepository = $this->getDoctrine()
            ->getRepository('YilinkerCoreBundle:ProductCategory');
        
        
        $slug = $categoryRepository->find($id)->getSlug();
        
        return $this->redirect($this->generateUrl('get_category', array(
          'slug' => $slug,
          'priceFrom' => $priceFrom,
          'priceTo' => $priceTo,
          'sortBy' => $sortType,
          'sortDirection' => $sortDirection
        )));
    }
}
