<?php

namespace Yilinker\Bundle\FrontendBundle\Services\Product;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Model\Discount;

/**
 * Class ProductService
 * @package Yilinker\Bundle\FrontendBundle\Services\Pages
 */
class ProductService
{
    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var authenticatedUser
     */
    private $authenticatedUser;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
        $this->entityManager = $entityManager;
    }

    public function setProductUnitDiscount(&$productUnit, $quantity)
    {
        $discount = new Discount();
        $discount->setProductUnit($productUnit)
                 ->setCurrentQuantity($quantity)
                 ->apply();

        return $discount->getProductUnit();
    }

    public function setDefaultUnitDiscount(&$product)
    {
        $discount = new Discount();
        $discount->setProduct($product)->setDefaultUnit()->apply();

        return $discount->getProductUnit();
    }

    /**
     * Adds product review
     *
     * @param $product
     * @param $postData
     * @return JsonResponse
     */
    public function addProductReview($product, $postData, $orderProduct = null)
    {
        $authenticatedUser = $this->authenticatedUser;
        $productReview = new ProductReview();
        $productReview->setReviewer($authenticatedUser)
                      ->setProduct($product)
                      ->setOrderProduct($orderProduct)
                      ->setDateAdded(Carbon::now())
                      ->setTitle($postData["title"])
                      ->setReview($postData["review"])
                      ->setRating($postData["rating"])
                      ->setIsHidden(false);

        $this->entityManager->persist($productReview);
        $this->entityManager->flush();

        $userImage = $authenticatedUser->getPrimaryImage();
        if($userImage){
            $profileImageUrl = $this->assetsHelper->getUrl($userImage->getImageLocation(), 'user');
        }
        else{
            $profileImageUrl = "";
        }

        $response = array(
            "isSuccessful" => true,
            "message" => "Review added.",
            "data" => array(
                "userId" => $this->authenticatedUser->getUserId(),
                "fullName" => $this->authenticatedUser->getFullName(),
                "firstName" => $this->authenticatedUser->getFirstName(),
                "lastName" => $this->authenticatedUser->getLastName(),
                "productId" => $product->getProductId(),
                "profileImageUrl" => $profileImageUrl,
                "rating" => $postData["rating"],
                "dateAdded" => Carbon::now()->toDateTimeString()
            )
        );

        return new JsonResponse($response, 201);
    }

    /**
     * Get product review
     *
     * @param $product
     * @param $page
     * @param $limit
     * @return JsonResponse
     * @internal param $postData
     */
    public function getProductReviews($product, $page, $limit)
    {
        $productReviewRepository = $this->entityManager->getRepository("YilinkerCoreBundle:ProductReview");

        $rating = 0.00;
        $productReviews = $productReviewRepository->getProductReviews($product, $page, $limit);
        $reviews = $this->organizeProductReviewData($productReviews);

        if(count($reviews) > 0){
            $allProductReviews = $productReviewRepository->getProductReviews($product, null, null);

            foreach($allProductReviews as $review){
                $rating += floatval($review->getRating());
            }

            $rating = ($rating/count($reviews));
        }

        $response = array(
            "isSuccessful" => true,
            "message" => "Review fetched.",
            "data" => array(
                "ratingAverage" => $rating,
                "reviews" => $reviews
            )
        );

        return new JsonResponse($response, 201);
    }

    /**
     * @param mixed $authenticatedUser
     */
    public function setAuthenticatedUser($authenticatedUser)
    {
        $this->authenticatedUser = $authenticatedUser;
    }

    /**
     * Product not found in DB
     *
     * @return JsonResponse
     */
    public function throwProductNotFound()
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Requested product not found.",
            "data" => array()
        );

        return new JsonResponse($response, 402);
    }

    /**
     * Invalid fields
     *
     * @param $error
     * @return JsonResponse
     */
    public function throwInvalidFields($error)
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "message" => "Bad Request.",
            "data" => $error
        );

        return new JsonResponse($response, 400);
    }

    /**
     * Formats the review's data to be submitted
     * @param $productReviews
     * @return array
     */
    private function organizeProductReviewData($productReviews)
    {
        $reviews = array();

        foreach($productReviews as $productReview){
            $profileImageUrl = null;
            $userImage = $productReview->getReviewer()->getPrimaryImage();

            if($userImage){
                $profileImageUrl = $this->assetsHelper->getUrl($userImage->getImageLocation(), 'user');
            }
            else{
                $profileImageUrl = "";
            }

            $review = array(
                "userId" => $productReview->getReviewer()->getUserId(),
                "title" => $productReview->getTitle(),
                "review" => $productReview->getReview(),
                "rating" => $productReview->getRating(),
                "fullName" => $productReview->getReviewer()->getFullName(),
                "firstName" => $productReview->getReviewer()->getFirstName(),
                "lastName" => $productReview->getReviewer()->getLastName(),
                "profileImageUrl" => $profileImageUrl,
                "dateAdded" => $productReview->getDateAdded()->format('F d Y H:i:s A')
            );

            array_push($reviews, $review);
        }

        return $reviews;
    }

    public function getPromoProducts($limit, $page)
    {
        $offset = $this->getOffset($limit, $page);

        $promoProducts = array();
        $productUnitRepository = $this->entityManager->getRepository('YilinkerCoreBundle:ProductUnit');
        $productUnitCollection = $productUnitRepository->getPromoProductUnits($limit, $offset);

        foreach ($productUnitCollection["productUnits"] as $productUnit) {
            $productDetails = $productUnit->getProduct();

            $discount = $productUnit->getDiscount();

            $promoTypeId = null;
            $promoTypeName = null;

            $promoMaps = $productUnit->getProductPromoMaps();
            $promoInstance = $productUnit->attachPromoDetails($promoMaps, $promoTypeId, $promoTypeName, $discount);

            $productImage = $productDetails->getPrimaryImageLocation();

            if($productImage !== ""){
                $productImage = $this->assetsHelper->getUrl($productImage, 'product');
            }

            $product = array(
                "id"                => $productDetails->getProductId(),
                "productName"       => $productDetails->getName(),
                "originalPrice"     => !is_null($productUnit->getAppliedBaseDiscountPrice())?
                                            $productUnit->getAppliedBaseDiscountPrice() :
                                            $productUnit->getPrice(),
                "newPrice"          => !is_null($productUnit->getAppliedDiscountPrice())?
                                            $productUnit->getAppliedDiscountPrice() :
                                            $productUnit->getDiscountedPrice(),
                "discount"          => $discount,
                "promoType"         => $promoTypeId,
                "promoName"         => $promoTypeName,
                "imageUrl"          => $productImage
            );

            array_push($promoProducts, $product);
        }

        return array(
          "products" => $promoProducts,
          "totalResults" => $productUnitCollection["totalResults"]
        );
    }

    private function getOffset($limit = 10, $page = 0)
    {
        if((int)$page > 1){
            return (int)$limit * ((int)$page-1);
        }

        return 0;
    }
}
