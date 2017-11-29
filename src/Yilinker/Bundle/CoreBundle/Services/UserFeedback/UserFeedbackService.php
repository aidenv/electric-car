<?php

namespace Yilinker\Bundle\CoreBundle\Services\UserFeedback;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yilinker\Bundle\CoreBundle\Entity\UserFeedback;
use Yilinker\Bundle\CoreBundle\Entity\UserFeedbackRating;

/**
 * Class UserFeedbackService
 * @package Yilinker\Bundle\FrontendBundle\Services\Pages
 */
class UserFeedbackService
{
    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(EntityManager $entityManager, AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
        $this->entityManager = $entityManager;
    }


    public function addUserFeedback($user, $store, $order, $title, $feedback, $userRatings, $feedbackTypes)
    {
        try{
            $userFeedback = new UserFeedback();
            $userFeedback->setReviewer($user)
                ->setReviewee($store)
                ->setDateAdded(Carbon::now())
                ->setTitle($title)
                ->setFeedback($feedback)
                ->setOrder($order)
                ->setIsHidden(false);

            $this->entityManager->persist($userFeedback);

            $ratingAverage = $this->persistFeedbackRatings($userRatings, $feedbackTypes, $userFeedback);

            $userFeedback->setRating($ratingAverage);
            $this->entityManager->flush();

            $profileImageUrl = $this->getProfileImageLocation($user);
            $ratingCollection = $this->getRatingsCollection($userFeedback->getRatings());

            $review = array(
                "userId" => $user->getUserId(),
                "storeName" => $store->getStoreName(),
                "title" => $userFeedback->getTitle(),
                "feedback" => $userFeedback->getFeedback(),
                "averageRating" => $userFeedback->getRating(),
                "fullName" => $user->getFullName(),
                "firstName" => $user->getFirstName(),
                "lastName" => $user->getLastName(),
                "profileImageUrl" => $profileImageUrl,
                "ratings" => $ratingCollection
            );

            return $review;
        }
        catch(Exception $e){
            return false;
        }
    }

    public function persistFeedbackRatings($userRatings, $feedbackTypes, &$userFeedback)
    {
        $ratings = array();
        foreach($userRatings as $userRating){
            $userFeedbackRating = new UserFeedbackRating();
            $userFeedbackRating->setRating($userRating["rating"])
                               ->setFeedbacks($userFeedback)
                               ->setType($feedbackTypes[$userRating["rateType"]]);

            $this->entityManager->persist($userFeedbackRating);
            $userFeedback->addRating($userFeedbackRating);
            array_push($ratings, floatval($userRating["rating"]));
        }

        return array_sum($ratings)/count($feedbackTypes);
    }

    public function getUserFeedbacks($store, $page, $limit)
    {
        $offset = $page > 1? $limit * ($page-1) : 0;
        $rating = 0.00;
        $storeReviews = $store->getReviews();
        $paginatedResults = $store->getPaginatedReviews($limit, $offset);
        $reviews = $this->organizeStoreReviewData($paginatedResults);

        if(count($storeReviews) > 0){
            foreach($storeReviews as $review){
                $rating += floatval($review->getRating());
            }

            $rating = ($rating/count($storeReviews));
        }

        return array(
                "ratingAverage" => $rating,
                "reviews" => $reviews
        );
    }

    private function organizeStoreReviewData($storeReviews)
    {
        $reviews = array();

        foreach($storeReviews as $storeReview){
            $profileImageUrl = null;
            $ratings = $storeReview->getRatings();
            $store = $storeReview->getReviewee();

            $profileImageUrl = $this->getProfileImageLocation($storeReview->getReviewer());

            $ratingCollection = $this->getRatingsCollection($ratings);

            $review = array(
                "userId" => $storeReview->getReviewer()->getUserId(),
                "storeName" => $store->getStoreName(),
                "title" => $storeReview->getTitle(),
                "feedback" => $storeReview->getFeedback(),
                "averageRating" => $storeReview->getRating(),
                "fullName" => $storeReview->getReviewer()->getFullName(),
                "firstName" => $storeReview->getReviewer()->getFirstName(),
                "lastName" => $storeReview->getReviewer()->getLastName(),
                "profileImageUrl" => $profileImageUrl,
                "dateAdded" => $storeReview->getDateAdded()->format('F d Y H:i:s A'),
                "ratings" => $ratingCollection
            );

            array_push($reviews, $review);
        }

        return $reviews;
    }

    private function getProfileImageLocation($user)
    {
        $userImage = $user->getPrimaryImage();

        if($userImage){
            $profileImageUrl = $this->assetsHelper->getUrl($userImage->getImageLocation(), 'user');
        }
        else{
            $profileImageUrl = "";
        }

        return $profileImageUrl;
    }

    private function getRatingsCollection($ratings)
    {
        $ratingCollection = array();
        foreach($ratings as $rating){
            array_push($ratingCollection, array(
                "typeId" => $rating->getType()->getFeedbackTypeId(),
                "type" => $rating->getType()->getName(),
                "rating" => $rating->getRating()
            ));
        }

        return $ratingCollection;
    }
}
