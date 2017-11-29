<?php

namespace Yilinker\Bundle\MerchantBundle\Controller;

use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class ResellerController extends Controller
{
    const PRODUCT_PER_PAGE = 15;
    
    public function productSelectAction(Request $request)
    {
        $user = $this->getUser();
        $this->throwNotFoundUnless($user->isAffiliate(), 'Affiliate only page');
        $em = $this->getDoctrine()->getEntityManager();
        $tbProductCategory = $em->getRepository('YilinkerCoreBundle:ProductCategory');
        $categories = $tbProductCategory->getMainCategories();

        return $this->render('YilinkerMerchantBundle:Product:product_select.html.twig', compact('categories'));
    }


    /**
     * Reseller View Uploadable Product List
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resellerViewProductsAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'You are not allowed to access this page',
            'data' => array(),
        );

        $user = $this->getUser();
        if($user->isAffiliate()){
            $response['message'] = 'No products found';
            $response['isSuccessful'] = true;

            $page = (int) $request->get('page', 1);
            $query = $request->get('query', null);
            $categoryId = $request->get('categoryId', null);
            $selectedInhouseProductIds = $request->get('manufacturerProductIds', array());

            $em = $this->getDoctrine()->getManager();
            $tbInhouseProduct = $em->getRepository('YilinkerCoreBundle:InhouseProduct');
            $inhouseProducts = $tbInhouseProduct->searchBy(array(
                    //'country'               => $user->getCountry(),
                    //'statuses'              => array(Product::ACTIVE),
                    'productId.exclude'     => $selectedInhouseProductIds,
                    'query'                 => $query,
                    'affiliate.unselected'  => $user
                ))
                ->findByProductUnit()
                ->andWhere('ipu.quantity > :quantity')
                ->andWhere('this.status = :status')
                ->setParameter('quantity', 0)
                ->setParameter('status', Product::ACTIVE)
                ->setLimit(self::PRODUCT_PER_PAGE)
                ->page($page)
                ->getResult()
            ;

            if($inhouseProducts){
                $productData = array();
                $assetsHelper = $this->container->get('templating.helper.assets');
                foreach($inhouseProducts as $inhouseProduct){
                    $primaryImage = null;
                    $primaryImage = $inhouseProduct->getPrimaryImage() !== null ? 
                                    $inhouseProduct->getPrimaryImage() : 
                                    $inhouseProduct->getFirstImage();
                     
                    $defaultUnit = $inhouseProduct->getDefaultUnit();

                    if($defaultUnit){
                        $productData[] = array(
                                'id'           => $inhouseProduct->getProductId(),
                                'manufacturer' => $inhouseProduct->getManufacturer()->getName(),
                                'productName'  => $inhouseProduct->getName(),
                                'description'  => $inhouseProduct->getDescription(),
                                'shortDescription' => $inhouseProduct->getShortDescription(),
                                'categoryId'   => $inhouseProduct->getProductCategory()->getProductCategoryId(),
                                'categoryName' => $inhouseProduct->getProductCategory()->getName(),
                                'image'        => $primaryImage ? $assetsHelper->getUrl($primaryImage->getImageLocationBySize('small'), 'product') : '',
                                'defaultUnit'  => $defaultUnit->toArray(),
                                'attributes'   => $inhouseProduct->getAvailableAttributes(),
                                'comission'    => number_format((float)$defaultUnit->getCommission(), 2, '.', ','),
                        );
                    }
                }

                $response['data'] = $productData;
                $response['isSuccessful'] = true;
                $response['message'] = count($inhouseProducts)." result(s) found.";
            }
        }

        $jsonResponse = new Response(json_encode($response, JSON_UNESCAPED_UNICODE));
        $jsonResponse->headers->set('Content-Type', 'application/json');

        return $jsonResponse;
    }

    /**
     * Reseller Upload Product
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Reseller",
     * )
     */
    public function resellerUploadAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'No selected manufacturer product',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user->isAffiliateVerified()) {
            $response['message'] = 'Affiliate does not have the minimum information to be able to select products.';
            return new JsonResponse($response);
        }

        $tbInhouseProduct = $em->getRepository('YilinkerCoreBundle:InhouseProduct');
        $productIds = $request->get('productIds', array());
        $availableStoreSpace = $user->getAvailableStoreSpace();
        if ($availableStoreSpace >= count($productIds)) {
            $inhouseProducts = $tbInhouseProduct
                ->searchBy(array(
                    'productId' => $productIds,
                    'statuses'  => Product::ACTIVE
                ))
                ->getResult()
            ;
            
            $ids = array();
            foreach ($inhouseProducts as $inhouseProduct) {
                $inhouseProductUser = new InhouseProductUser;
                $inhouseProductUser->setUser($user);
                $inhouseProductUser->setProduct($inhouseProduct);
                $inhouseProductUser->setStatus(Product::ACTIVE);

                $em->persist($inhouseProductUser);
                $ids[] = $inhouseProduct->getProductId();
            }
            $em->flush();

            if($ids){
                $response['isSuccessful'] = true;
                $response['message'] = 'Successfully uploaded '.count($ids).' product(s)';
                $response['data'] = $ids;
            }
        }
        else {
            $response['message'] = 'You can only select ' . $availableStoreSpace. ' product(s)';
        }

        return new JsonResponse($response);
    }

    /**
     * Render dashboard reseller product selection
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function renderResellerSelectionAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authenticatedUser = $this->get('security.token_storage')->getToken()->getUser();
        $categories = $em->getRepository('YilinkerCoreBundle:ProductCategory')
                         ->getMainCategories();
        
        return $this->render('YilinkerMerchantBundle:Dashboard:dashboard_reseller_selection.html.twig', array(
            'categories' => $categories,
        ));
    }

    /**
     * Render the reseller product detail action
     *
     * @param int $manufacturerProductId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resellerProducDetailAction($inhouseProductId)
    {
        $em = $this->getDoctrine()->getManager();
        $tbInhouseProduct = $em->getRepository('YilinkerCoreBundle:InhouseProduct');
        $inhouseProduct = $tbInhouseProduct->find($inhouseProductId);

        if($inhouseProduct === null){
            throw $this->createNotFoundException('Inhouse Product not found');
        }

        return $this->render('YilinkerMerchantBundle:Product:product_view.html.twig', array(
            'product' => $inhouseProduct,
        ));
    }

    private function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }

}

