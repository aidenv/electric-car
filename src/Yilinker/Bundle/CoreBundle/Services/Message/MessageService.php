<?php

namespace Yilinker\Bundle\CoreBundle\Services\Message;

use Exception;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\ResultSetMapping;
use Endroid\Bundle\GcmBundle\DependencyInjection\EndroidGcmExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Contact;
use Yilinker\Bundle\CoreBundle\Entity\Message;
use Yilinker\Bundle\CoreBundle\Entity\MessageImage;
use Yilinker\Bundle\CoreBundle\Services\Contact\ContactService;
use Yilinker\Bundle\CoreBundle\Services\Predis\PredisService;
use Yilinker\Bundle\CoreBundle\Services\Upload\UploadService;
use Yilinker\Bundle\CoreBundle\Services\Device\Broadcaster;

/**
 * Class MessageService
 * @package Yilinker\Bundle\CoreBundle\Services\Message
 */
class MessageService
{
    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var null
     */
    public $authenticatedUser = null;

    /**
     * @var AssetsHelper
     */
    private $assetsHelper;

    /**
     * @var ContactService
     */
    private $contactService;

    /**
     * @var Predis
     */
    private $predisService;

    /**
     * @var UploadService
     */
    private $uploadService;

    /**
     * @var Broadcaster
     */
    private $broadcaster;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(
        EntityManager $entityManager, 
        AssetsHelper $assetsHelper, 
        ContactService $contactService,
        PredisService $predisService,
        UploadService $uploadService,
        Broadcaster $broadcaster
    )
    {
        $this->entityManager = $entityManager;
        $this->assetsHelper = $assetsHelper;
        $this->contactService = $contactService;
        $this->predisService = $predisService;
        $this->uploadService = $uploadService;
        $this->broadcaster = $broadcaster;
    }

    /**
     * @param null $authenticatedUser
     */
    public function setAuthenticatedUser($authenticatedUser)
    {
        $this->authenticatedUser = $authenticatedUser;
    }

    /**
     * Sends message
     *
     * @param $data
     * @return JsonResponse
     */
    public function sendMessage($message)
    {
        $authenticatedUser = $this->authenticatedUser;

        $recipient = $message->getRecipient();
        
        if(
            $authenticatedUser != $recipient &&
            $authenticatedUser->getUserType() != $recipient->getUserType() &&
            !(
                !is_null($recipient->getStore()) && 
                $recipient->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER
            ) ||
            (
                !is_null($authenticatedUser->getStore()) && 
                $authenticatedUser->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER
            )
        ){
            $message->setSender($authenticatedUser)
                    ->setTimeSent(Carbon::now())
                    ->setIsDeleteSender(false)
                    ->setIsDeleteRecipient(false)
                    ->setIsSeen(false);

            $this->entityManager->persist($message);
            $this->entityManager->flush();

            $gcmData = array(
                "isSuccessful" => true,
                "responseType" => "NEW_MESSAGE",
                "message" => "New message arrived.",
                "data" => array(
                    "senderUid"     => $authenticatedUser->getUserId(),
                    "recipientUid"  => $recipient->getUserId(),
                    "recipientName" => $recipient->getFullName(),
                    "message"       => $message->getMessage(),
                    "isImage"       => $message->getIsImage(),
                    "timeSent"      => $message->getTimeSent(),
                    "isSeen"        => $message->getIsSeen()
                )
            );

            //fetch registration ids
            $registrationIds = $this->entityManager
                                    ->getRepository("YilinkerCoreBundle:Device")
                                    ->loadConversationRegistrationIds($authenticatedUser->getUserId(), $recipient->getUserId());

            $this->contactService->addToContact($authenticatedUser, $recipient);

            $responseData = array(
                    "sentTo"        => $recipient->getUserId(),
                    "senderUid"     => $authenticatedUser->getUserId(),
                    "recipientUid"  => $recipient->getUserId(),
                    "message"       => $message->getMessage(),
                    "isImage"       => $message->getIsImage(),
                    "dateSent"      => Carbon::now()->toDateTimeString(),
                    "isSeen"        => $message->getIsSeen(),
                    "gcmLogs"       => $this->sendToGCM($gcmData, $registrationIds)
            );  

            try{
                $this->predisService
                     ->publishSentMessage($message, $authenticatedUser, $recipient)
                     ->publishConversationHead($message);
            }
            catch(Exception $e){
            }

            return $responseData;
        }
        else{
            return array();
        }
    }

    public function sendMessageImages(User $recipient, $fileNames)
    {
        $authenticatedUser = $this->authenticatedUser;
        $message = new Message();
        $message->setMessage("")
                ->setSender($authenticatedUser)
                ->setRecipient($recipient)
                ->setTimeSent(Carbon::now())
                ->setIsDeleteSender(false)
                ->setIsDeleteRecipient(false)
                ->setIsSeen(false)
                ->setIsImage(true);

        $this->entityManager->persist($message);

        foreach($fileNames as $fileName){
            $messageImage = new MessageImage();
            $messageImage->setFileLocation($fileName)
                         ->setDateAdded(Carbon::now())
                         ->setMessage($message);

            $this->entityManager->persist($messageImage);
        }

        $this->entityManager->flush();
    }

    /**
     * Set conversation as read
     *
     * @param $user
     * @return JsonResponse
     */
    public function setConversationAsRead($user)
    {
        $queryBuilder = $this->entityManager->createQueryBuilder();
        $queryBuilder->update("YilinkerCoreBundle:Message", "m")
                     ->set("m.isSeen", ":isSeen")
                     ->set("m.timeSeen", ":timeSeen")
                     ->where("m.sender = :sender")
                     ->andWhere("m.recipient = :recipient")
                     ->andWhere("NOT m.isSeen = :isSeen")
                     ->setParameter(":isSeen", true)
                     ->setParameter(":timeSeen", Carbon::now())
                     ->setParameter(":sender", $user->getUserId())
                     ->setParameter(":recipient", $this->authenticatedUser)
                     ->getQuery()
                     ->execute();

        $gcmData = array(
            "isSuccessful" => true,
            "responseType" => "CONVERSATION_SEEN",
            "message" => "The messages is set to seen.",
            "data" => array(
                "userId" => $user->getUserId(),
                "recipientName" => $this->authenticatedUser->getFullName(),
            )
        );

        $registrationIds = $this->entityManager
                                ->getRepository("YilinkerCoreBundle:Device")
                                ->loadConversationRegistrationIds($this->authenticatedUser->getUserId(), $user->getUserId());

        $this->sendToGCM($gcmData, $registrationIds);

        try{
            $this->predisService->publishSeen($user, $this->authenticatedUser);
        }
        catch(Exception $e){
        }
    }

    /**
     * Fetch the messages of the conversation
     *
     * @param $user
     * @param int $limit
     * @param int $page
     * @return JsonResponse
     */
    public function getConversationMessages($user, $limit = 10, $page = 1, $excludedTimeSent = null)
    {
        $offset = $this->getOffset($limit, $page);

        $authenticatedUser = $this->authenticatedUser;
        $messageRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Message");
        $messageEntities = $messageRepository->getConversationMessages($user, $authenticatedUser, $authenticatedUser, $limit, $offset, $excludedTimeSent);


        $messageCollection = array();
        foreach ($messageEntities as $message) {
            $sender = $message->getSender();
            $userProfileImage = $sender->getPrimaryImage();

            extract($this->getPrimaryImages($userProfileImage, $sender));

            $messageContent = $message->getMessage();
            if($message->getIsImage()){
                $messageContent = $this->assetsHelper->getUrl($messageContent, 'chat');
            }

            array_push($messageCollection, array(
                "messageId" => $message->getMessageId(),
                "senderId" => $sender->getUserId(),
                "recipientId" => $message->getRecipient()->getUserId(),
                "senderProfileImageUrl" => $profileImageUrl,
                "senderProfileThumbnailImageUrl" => $profileThumbnailImageUrl,
                "senderProfileSmallImageUrl" => $profileSmallImageUrl,
                "senderProfileMediumImageUrl" => $profileMediumImageUrl,
                "senderProfileLargeImageUrl" => $profileLargeImageUrl,
                "message" => $messageContent,
                "isImage" => $message->getIsImage()? "1":"0",
                "timeSent" => $message->getTimeSent()->format("Y-m-d H:i:s"),
                "isSeen" => $message->getIsSeen()? "1":"0",
                "timeSeen" => !is_null($message->getTimeSeen())? 
                                $message->getTimeSeen()->format("Y-m-d H:i:s") :
                                null,
                "isSenderOnline" => $sender->getIsOnline()
            ));
        }

        return $messageCollection;
    }

    /**
     * Fetch the contacts of the user
     *
     * @param string $keyword
     * @param int $limit
     * @param int $page
     * @return JsonResponse
     */
    public function getContacts($keyword = "", $limit = 10, $page = 1)
    {
        $authenticatedUser = $this->authenticatedUser;
        $offset = $this->getOffset($limit, $page);
        $userContacts = $this->contactService->getContacts($authenticatedUser, $keyword, $limit, $offset);

        $contacts = array();
        foreach ($userContacts as $contact) {
            $userProfileImage = $contact->getPrimaryImage();

            extract($this->getPrimaryImages($userProfileImage, $contact));

            $recievedUnreadMessages = $contact->getUnreadRecievedMessagesFromUser($authenticatedUser);

            $store = null;
            if($contact->getUserType() == User::USER_TYPE_SELLER){
                $store = $contact->getStore();
            }

            array_push($contacts, array(
                "userId" => $contact->getUserId(),
                "slug" => $contact->getUserType() == User::USER_TYPE_SELLER? $store->getStoreSlug() : $contact->getSlug(),
                "fullName" => !is_null($store)? $store->getStoreName() : $contact->getFullName(),
                "profileImageUrl" => $profileImageUrl,
                "profileThumbnailImageUrl" => $profileThumbnailImageUrl,
                "profileSmallImageUrl" => $profileSmallImageUrl,
                "profileMediumImageUrl" => $profileMediumImageUrl,
                "profileLargeImageUrl" => $profileLargeImageUrl,
                "isOnline" => $contact->getActiveNonIdleDevices()->count()? "1" : "0",
                "hasUnreadMessage" => (string)$recievedUnreadMessages->count()
            ));
        }

        return $contacts;
    }

    public function deleteConversation(User $user, User $authenticatedUser)
    {
        $qb1 = $this->entityManager->createQueryBuilder();
        $qb1->update("YilinkerCoreBundle:Message", "m")
            ->set("m.isDeleteSender", ":isDeleteSender")
            ->where("m.sender = :authenticatedUser")
            ->andWhere("m.recipient = :user")
            ->setParameter(":isDeleteSender", true)
            ->setParameter(":user", $user)
            ->setParameter(":authenticatedUser", $authenticatedUser)
            ->getQuery()
            ->execute();

        $qb2 = $this->entityManager->createQueryBuilder();
        $qb2->update("YilinkerCoreBundle:Message", "m")
            ->set("m.isDeleteRecipient", ":isDeleteRecipient")
            ->where("m.sender = :user")
            ->andWhere("m.recipient = :authenticatedUser")
            ->setParameter(":isDeleteRecipient", true)
            ->setParameter(":user", $user)
            ->setParameter(":authenticatedUser", $authenticatedUser)
            ->getQuery()
            ->execute();
    }

    public function getContact(User $user)
    {
        $authenticatedUser = $this->authenticatedUser;
        $userRepository = $this->entityManager->getRepository("YilinkerCoreBundle:User");


        $userProfileImage = $user->getPrimaryImage();

        extract($this->getPrimaryImages($userProfileImage, $user));

        $recievedUnreadMessages = $user->getUnreadRecievedMessagesFromUser($user);

        $store = null;
        if($user->getUserType() == User::USER_TYPE_SELLER){
            $store = $user->getStore();
        }

        $contactRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Contact");
        $contactEntry = $contactRepository->getUserContact($authenticatedUser, $user);

        $namespace = sha1($contactEntry->getContactId());

        return array(
            "userId" => $user->getUserId(),
            "fullName" => !is_null($store)? $store->getStoreName() : $user->getFullName(),
            "slug" => !is_null($store)? $store->getStoreSlug() : $user->getSlug(),
            "namespace" => $namespace,
            "profileImageUrl" => $profileImageUrl,
            "profileThumbnailImageUrl" => $profileThumbnailImageUrl,
            "profileSmallImageUrl" => $profileSmallImageUrl,
            "profileMediumImageUrl" => $profileMediumImageUrl,
            "profileLargeImageUrl" => $profileLargeImageUrl,
            "isOnline" => $user->getActiveNonIdleDevices()->count()? true : false,
            "isMerchant" => $user->getUserType() == User::USER_TYPE_SELLER? true : false,
            "hasUnreadMessage" => $recievedUnreadMessages->count()
        );
    }

    /**
     * Fetch the head of the conversation
     *
     * @param int $limit
     * @param int $page
     * @return JsonResponse
     */
    public function getConversationHead($limit = 10, $page = 1)
    {
        $authenticatedUser = $this->authenticatedUser;
        $offset = $this->getOffset($limit, $page);

        $userRepository = $this->entityManager->getRepository("YilinkerCoreBundle:User");
        $userWithMessages = $userRepository->getConnectedUserWithMessages($authenticatedUser, $limit, $offset);

        $users = array();
        foreach($userWithMessages as $userWithMessage){
            $user = $userRepository->find($userWithMessage["userId"]);

            $userProfileImage = $user->getPrimaryImage();

            extract($this->getPrimaryImages($userProfileImage, $user));

            $recievedUnreadMessages = $user->getUnreadRecievedMessagesFromUser($authenticatedUser);

            $store = null;
            if($user->getUserType() == User::USER_TYPE_SELLER){
                $store = $user->getStore();
            }

            array_push($users, array(
                "userId"                        => $userWithMessage["userId"],
                "fullName"                      => !is_null($store)? $store->getStoreName() : $user->getFullName(),
                "slug"                          => $user->getSlug(),
                "sender"                        => $userWithMessage["sender"],
                "message"                       => $userWithMessage["message"],
                "isImage"                       => $userWithMessage["isImage"],
                "lastMessageDate"               => $userWithMessage["lastMessageDate"],
                "profileImageUrl"               => $profileImageUrl,
                "profileThumbnailImageUrl"      => $profileThumbnailImageUrl,
                "profileSmallImageUrl"          => $profileSmallImageUrl,
                "profileMediumImageUrl"         => $profileMediumImageUrl,
                "profileLargeImageUrl"          => $profileLargeImageUrl,
                "lastLoginDate"                 => $userWithMessage["lastLoginDate"],
                "isOnline"                      => $user->getActiveNonIdleDevices()->count()? "1" : "0",
                "hasUnreadMessage"              => (string)$recievedUnreadMessages->count()
            ));
        }

        return $users;
    }

    private function getPrimaryImages($userProfileImage, $user)
    {
        $profileImageUrl = $userProfileImage ? 
            $this->assetsHelper->getUrl($userProfileImage->getImageLocation(), 'user') : 
            (
                $user->getUserType() == User::USER_TYPE_BUYER ? 
                    $this->assetsHelper->getUrl('images/default-buyer.png') : 
                    $this->assetsHelper->getUrl('images/default-merchant.png')
            );

        $profileThumbnailImageUrl = $userProfileImage ? 
            $this->assetsHelper->getUrl($userProfileImage->getImageLocationBySize('thumbnail'), 'user') : 
            (
                $user->getUserType() == User::USER_TYPE_BUYER ? 
                    $this->assetsHelper->getUrl('images/default-buyer.png') : 
                    $this->assetsHelper->getUrl('images/default-merchant.png')
            );

        $profileSmallImageUrl = $userProfileImage ? 
            $this->assetsHelper->getUrl($userProfileImage->getImageLocationBySize('small'), 'user') : 
            (
                $user->getUserType() == User::USER_TYPE_BUYER ? 
                    $this->assetsHelper->getUrl('images/default-buyer.png') : 
                    $this->assetsHelper->getUrl('images/default-merchant.png')
            );

        $profileMediumImageUrl = $userProfileImage ? 
            $this->assetsHelper->getUrl($userProfileImage->getImageLocationBySize('medium'), 'user') : 
            (
                $user->getUserType() == User::USER_TYPE_BUYER ? 
                    $this->assetsHelper->getUrl('images/default-buyer.png') : 
                    $this->assetsHelper->getUrl('images/default-merchant.png')
            );

        $profileLargeImageUrl = $userProfileImage ? 
            $this->assetsHelper->getUrl($userProfileImage->getImageLocationBySize('large'), 'user') : 
            (
                $user->getUserType() == User::USER_TYPE_BUYER ? 
                    $this->assetsHelper->getUrl('images/default-buyer.png') : 
                    $this->assetsHelper->getUrl('images/default-merchant.png')
            );

        return compact(
            "profileImageUrl", 
            "profileThumbnailImageUrl", 
            "profileSmallImageUrl", 
            "profileMediumImageUrl", 
            "profileLargeImageUrl" 
        );
    }

    /**
     * Upload the file
     *
     * @param $file
     * @return null|string
     * @internal param $imageName
     */
    public function uploadImage($file)
    {
        $this->uploadService->setType("message");
        $fileName = $this->uploadService->uploadFile($file);

        if(!$fileName){
            return false;
        }

        return array("url" => $this->assetsHelper->getUrl($fileName, 'chat'));
    }

    public function uploadMessageImages(array $files)
    {
        $this->uploadService->setType("message");
        $fileNames = $this->uploadService->uploadFiles($files);

        return $fileNames;
    }

    /**
     * Required fields not supplied
     *
     * @param string $error_type
     * @param null $form
     * @param bool|true $isFormTransaction
     * @param null $errors
     * @return JsonResponse
     */
    public function throwInvalidFields($error_type = "", $form = null, $isFormTransaction = true, $errors = null)
    {
        if($isFormTransaction){
            $errors = array($this->generateErrors($form));
        }

        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "responseType" => $error_type,
            "message" => "Invalid fields supplied.",
            "data" => $errors
        );

        return new JsonResponse($response, 400);
    }

    /**
     * User not found in DB
     *
     * @param string $error_type
     * @return JsonResponse
     */
    public function throwUserNotFound($error_type = "")
    {
        // if data is null/invalid/missing required data throw 400
        $response = array(
            "isSuccessful" => false,
            "responseType" => $error_type,
            "message" => "Requested user not found.",
            "data" => array()
        );

        return new JsonResponse($response, 402);
    }

    /**
     * Mime type of the file is invalid
     *
     * @param $mimeType
     * @return bool
     */
    public function checkIfValidMimeType($mimeType)
    {
        $acceptedMimeTypes = array(
            "image/jpg",
            "image/jpeg",
            "image/png",
        );

        if(in_array($mimeType, $acceptedMimeTypes)){
            return true;
        }

        return false;
    }

    /**
     * Trims the message
     *
     * @param $message
     * @return string
     */
    public function trimMessage($message)
    {
        return trim(strip_tags($message));
    }

    /**
     * Returns all form errors
     *
     * @param $form
     * @return array
     */
    private function generateErrors($form)
    {
        $errors = array();

        foreach($form->getErrors(true) as $error){
            array_push($errors, $error->getMessage());
        }

        return array_values(array_unique($errors));
    }

    /**
     * get Offset for pagination
     *
     * @param int $limit
     * @param int $page
     * @return int
     */
    private function getOffset($limit = 10, $page = 0)
    {
        if($page > 1){
            return $limit * ($page-1);
        }

        return 0;
    }

    /**
     * Send to GCM
     *
     * @param $data
     * @param $registrationIds
     */
    private function sendToGCM($data, $registrationIds)
    {
        if(!empty($registrationIds)){
            return $this->broadcaster->init($registrationIds)->send($data);
        }
    }

    private function setImageAssets(&$resultMap)
    {
        foreach($resultMap as $index => $data){
            $resultMap[$index]["profileImageUrl"] = $this->assetsHelper->getUrl($resultMap[$index]["profileImageUrl"], 'user');
        }
    }
}
