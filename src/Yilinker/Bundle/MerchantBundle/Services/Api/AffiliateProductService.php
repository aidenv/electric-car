<?php
namespace Yilinker\Bundle\MerchantBundle\Services\Api;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Exception;
use Sluggable\Fixture\Inheritance2\Car;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\Brand;
use Yilinker\Bundle\CoreBundle\Entity\InhouseProductUser;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;

class AffiliateProductService
{

    private $em;

    private $container;

    private $authenticatedUser;

    private $country;

    public function __construct($entityManager, $container,AssetsHelper $assetsHelper)
    {
        $this->em = $entityManager;
        $this->container = $container;
        $this->assetsHelper = $assetsHelper;
        $this->authenticatedUser = $this->container->get('security.token_storage')->getToken()->getUser();

        $countryCode = $this->container->get('yilinker_core.translatable.listener')
            ->getCountry();

        $this->country = $this->em->getRepository("YilinkerCoreBundle:Country")
            ->findOneByCode($countryCode);
    }



    public function getFilterCategories()
    {
        $categoryRepository = $this->em->getRepository('YilinkerCoreBundle:ProductCategory');
        $categories = $categoryRepository->getMainCategories("ASC", "name");

        $manufacturerCategories = array();
        foreach ($categories as $category) {
            $manufacturerCategories[] = $category->toArray();
        }

        return $manufacturerCategories;
    }


    /**
     * List of Manufacturer Products
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function getAffiliateProducts($data = array())
    {
        $affiliateUser = null;
        $availableOnly = false;
        $orderby = $data['sortby'] == 'earning' ? array('productUnit' => '', 'commision.DESC' => 'DESC') : array('dateCreated.DESC' => Carbon::now());
        $categoryIds = !is_null($data['categoryIds']) ? array('productCategory' => $data['categoryIds']) : array();

        if ($data['status'] === 'selected') {
            $status = array('affiliate' => $this->authenticatedUser);
        }
        else if ($data['status'] == 'available' ) {
            $status = array();
        }
        else {
            $status = array('statuses' => Product::ACTIVE, 'country' => $this->country);
        }

        $inhouse = $this->em
                ->getRepository('YilinkerCoreBundle:InhouseProduct')
                ->searchBy(
                    array_merge($status,array('query' => $data['name']), $orderby, $categoryIds)
                )
                ->setLimit($data['limit'])
                ;

        $count = $inhouse->getCount();

       $productsData = $this->constructProductsData($inhouse->getResult());

        return array(
            'totalResults'              => $count,
            'totalPage'                 => (int) ceil($count/$data['limit']),
            'selectedProductCount'      => $productsData['selectedProductCount'],
            'manufacturerProductIds'    => $productsData['manufacturerProductIds'],
            'selectedManufacturerProductIds' => $productsData['selectedManufacturerProductIds'],
            'storeSpace'                => $this->getAffiliateStoreSpace(),
            'products'                  => $productsData['products'],

        );
    }

    /**
     * [saveAffiliateProducts description]
     * @param  array  $data [description]
     */
    public function saveAffiliateProducts($data=array())
    {
        $hasError = 0;
        $responses = array();

        if (count($data['manufacturerProductIds']) > 0) {

            $manufacturerProducts = $this->em
                ->getRepository('YilinkerCoreBundle:InhouseProduct')
                ->searchBy(array(
                    'productId' => $data['manufacturerProductIds'],
                ))
                ->getResult()
            ;

            foreach($manufacturerProducts as $manufacturerProduct) {
                $response = array();

                    $inhouseProduct = $this->em->getRepository('YilinkerCoreBundle:InhouseProduct')->find($manufacturerProduct);

                    $inhouseProductUser = $this->em
                                                ->getRepository('YilinkerCoreBundle:InhouseProductUser')
                                                ->findOneBy(array(
                                                    'user' => $this->authenticatedUser,
                                                    'product' => $inhouseProduct
                                                ));
                    if (is_null($inhouseProductUser)) {
                        $inhouseProductUser = new InhouseProductUser();
                        $inhouseProductUser->setProduct($inhouseProduct);
                        $inhouseProductUser->setUser($this->authenticatedUser);
                        $inhouseProductUser->setDateAdded(Carbon::now());
                    }

                    $inhouseProductUser->setStatus($inhouseProduct->getStatus());
                    $inhouseProductUser->setDateLastModified(Carbon::now());

                    $this->em->persist($inhouseProductUser);
                    $this->em->flush();

                    $response['error'] = null;
                    $response['isSuccessful'] = true;
                    $response['manufacturerProductId'] =  $inhouseProduct->getProductId();
                    $response['productId'] =  $inhouseProduct->getProductId();

                array_push($responses, $response);
            }
        }

        return array('data' => $responses, 'hasError' => $hasError );

    }

    /**
     * [unBindAffiliateProducts remove affiliates selected products ]
     * @param  array  $data [description]
     * @return array
     */
    public function unBindAffiliateProducts($data=array())
    {
        $hasError = 0;
        $responses = array();

        if (array_key_exists('removeManufacturerProductIds',$data)) {

            $inhouseProductUsers = $this->em
                ->getRepository('YilinkerCoreBundle:InhouseProductUser')
                ->findBy(array(
                    'product' => $data['removeManufacturerProductIds'],
                    'user'    => $this->authenticatedUser
                ));
            ;

            foreach($inhouseProductUsers as $inhouseProductUser) {

                $response['manufacturerProductId'] =  $inhouseProductUser->getProduct()->getProductId();

                $this->em->remove($inhouseProductUser);
                array_push($responses,$response);
            }

            $this->em->flush();

        }

        return array('data' => $responses, 'hasError' => false );
    }


    /**
     * [constructProductsData retrieve manufacturerProduct data with affiliates selected product]
     * @param  [type] $manufacturerProducts
     */
    public function constructProductsData($manufacturerProducts)
    {
        $products = array();

        $manufacturerProductIds = array();
        $selectedManufacturerProductIds = array();
        $selectedProduct = 0;

        foreach($manufacturerProducts as $manufacturerProduct){

            if($manufacturerProduct->getDefaultUnit() || $manufacturerProduct->getFirstUnit()) {

                $manufacturerProductDetails = $this->constructInhouseProduct($manufacturerProduct);

                if ($manufacturerProductDetails['isSelected'] === true) {
                    array_push($selectedManufacturerProductIds, (int)$manufacturerProduct->getProductId());
                    $selectedProduct++;
                }

                $manufacturerProductIds[] = (int)$manufacturerProductDetails['manufacturerProductId'];

                array_push($products, $manufacturerProductDetails);

            }
        }

        return array(
            'products'                  => $products,
            'selectedProductCount'      => $selectedProduct,
            'manufacturerProductIds'    => $manufacturerProductIds,
            'selectedManufacturerProductIds' => $selectedManufacturerProductIds,
        );
    }


    public function getAffiliateStoreSpace()
    {
        $storeLevel = $this->authenticatedUser->getStore()->getStoreLevel();

        if ( is_null($storeLevel)) {

            $storeLevelRepo = $this->em
            ->getRepository('YilinkerCoreBundle:StoreLevel')
            ->findOneBy(array('storeLevelId' => StoreLevel::STORE_LEVEL_SILVER));

            return $storeLevelRepo->getStoreSpace();
        }

        return $storeLevel->getStoreSpace();
    }


    public function constructInhouseProduct($inhouseProduct)
    {
        $manufacturerProductDetails = array();
        $productUnits = array();
        $productImages = array();
        $manufacturerProductUnits = $inhouseProduct->getUnits();
        $images = $inhouseProduct->getImages();

        foreach($manufacturerProductUnits as $manufacturerProductUnit){
            //$combinations = $manufacturerProductUnit->getCombination();

            array_push($productUnits, array(
                "manufacturerProductUnitId" => (int)$manufacturerProductUnit->getProductUnitId(),
                "sku"                       => $manufacturerProductUnit->getSku(),
                "quantity"                  => $manufacturerProductUnit->getQuantity(),
                "length"                    => $manufacturerProductUnit->getLength(),
                "width"                     => $manufacturerProductUnit->getWidth(),
                "height"                    => $manufacturerProductUnit->getHeight(),
                "weight"                    => $manufacturerProductUnit->getWeight(),
                "price"                     => $manufacturerProductUnit->getPrice(),
                "discountedPrice"           => $manufacturerProductUnit->getDiscountedPrice(),
                "shippingFee"               => $manufacturerProductUnit->getShippingFee(),
                "retailPrice"               => $manufacturerProductUnit->getRetailPrice(),
                "commission"                => $manufacturerProductUnit->getCommission(),
                //"combinations"              => $combinations,
            ));
        }

        foreach ($images as $image) {
            array_push($productImages, $this->assetsHelper->getUrl($image->getImageLocation(), "product"));
        }

        $brandName = null;
        if($inhouseProduct->getBrand() && $inhouseProduct->getBrand()->getBrandId() !== Brand::CUSTOM_BRAND_ID){
            $brandName = $inhouseProduct->getBrand()->getName();
        }


        $manufacturerProductDetails = array(
            "manufacturerProductId" => (int)$inhouseProduct->getProductId(),
            "dateAdded"             => $inhouseProduct->getDateCreated()->format("Y-m-d H:i:s"),
            "name"                  => $inhouseProduct->getName(),
            "storeName"             => $inhouseProduct->getManufacturer()->getName(),
            "category"              => $inhouseProduct->getProductCategory() ? $inhouseProduct->getProductCategory()->getName(): '',
            "brand"                 => $brandName,
            "sku"                   => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getSku() : $inhouseProduct->getFirstUnit()->getSku(),
            "description"           => $inhouseProduct->getDescription(),
            "shortDescription"      => $inhouseProduct->getShortDescription(),
            "condition"             => $inhouseProduct->getCondition() ? $inhouseProduct->getCondition()->getName() : null,
            "originalPrice"         => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getPrice() : $inhouseProduct->getFirstUnit()->getPrice(),
            "discountedPrice"       => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getDiscountedPrice() : $inhouseProduct->getFirstUnit()->getDiscountedPrice(),
            "commission"            => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getCommission() : $inhouseProduct->getFirstUnit()->getCommission(),
            "discount"              => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getDiscountPercentage() : $inhouseProduct->getFirstUnit()->getDiscountPercentage(),
            "length"                => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getLength() : $inhouseProduct->getFirstUnit()->getLength(),
            "width"                 => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getWidth() : $inhouseProduct->getFirstUnit()->getWidth(),
            "height"                => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getHeight() : $inhouseProduct->getFirstUnit()->getHeight(),
            "weight"                => $inhouseProduct->getDefaultUnit()? $inhouseProduct->getDefaultUnit()->getWeight() : $inhouseProduct->getFirstUnit()->getWeight(),
            "status"                => $inhouseProduct->getStatus(),
            "units"                 => $productUnits,
            "images"                => $productImages,
            "isSelected"            => $inhouseProduct->isSelectedBy($this->authenticatedUser),
        );

        return $manufacturerProductDetails;
    }

}
