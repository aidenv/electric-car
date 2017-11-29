<?php

namespace Yilinker\Bundle\CoreBundle\Services\Predis;

use Exception;
use Predis;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Services\Jwt\JwtManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Contact;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Yilinker\Bundle\CoreBundle\Entity\Message;
use Yilinker\Bundle\CoreBundle\Entity\Product;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;
use Yilinker\Bundle\CoreBundle\Services\Predis\PredisService;

class PredisService
{
    const MANUFACTURER_PRODUCT_CHANNEL = 'manufacturer_product_update';

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var AssetsHelper
	 */
	private $assetsHelper;

	/**
	 * Predis instance
	 */
	private $predis = null;

    /**
     * @var JwtManager
     */
    private $jwtManager = null;

    /**
     * @param EntityManager $entityManager
     * @param AssetsHelper $assetsHelper
     */
    public function __construct(EntityManager $em, AssetsHelper $assetsHelper, JwtManager $jwtManager, $redisHost = "127.0.0.1", $redisPort = "6379")
    {
        $this->em = $em;
        $this->assetsHelper = $assetsHelper;
        $this->jwtManager = $jwtManager;

        try{
            if(!$this->predis instanceof Predis\Client){
                $this->predis = new Predis\Client(array(
                    "scheme" => "tcp",
                    "host" => $redisHost,
                    "port" => $redisPort
                ));
            }
        }
        catch(Exception $e){
        }
    }

    public function publishUserOnline(User $user, $devices)
    {
        foreach ($devices as $device) {
            $this->predis->publish("account_online", json_encode(

                array(
                    "userId" => $user->getUserId(),
                    "namespace" => $device->getToken(),
                    "slug" => $user->isSeller()? $user->getStore()->getStoreSlug() : $user->getSlug(),
                    "isOnline" => true
                )
            ));
        }
    }

    public function publishUserOffline(User $user, $devices)
    {
        foreach ($devices as $device) {
            $this->predis->publish("account_offline", json_encode(
                array(
                    "userId" => $user->getUserId(),
                    "namespace" => $device->getToken(),
                    "slug" => $user->isSeller()? $user->getStore()->getStoreSlug() : $user->getSlug(),
                    "isOnline" => false
                )
            ));
        }
    }
	
    public function publishSentMessage(Message $message, User $sender, User $recipient)
    {
        $contactRepository = $this->em->getRepository("YilinkerCoreBundle:Contact");
        $contactEntry = $contactRepository->getUserContact($sender, $recipient);

        if(!is_null($contactEntry)){
            $namespace = sha1($contactEntry->getContactId());

            $userProfileImage = $sender->getPrimaryImage();

            extract($this->getPrimaryImages($userProfileImage, $sender));

            $messageContent = $message->getMessage();
            if($message->getIsImage()){
                $messageContent = $this->assetsHelper->getUrl($messageContent, 'chat');
            }

            $messageDetails = array(
                "namespace" => $namespace,
                "data"      => array(
                    "messageId"                         => $message->getMessageId(),
                    "senderId"                          => $sender->getUserId(),
                    "senderRoom"                        => $this->jwtManager->encodeToken(array("userId" => $sender->getUserId())),
                    "recipientId"                       => $recipient->getUserId(),
                    "senderProfileImageUrl"             => $profileImageUrl,
                    "senderProfileThumbnailImageUrl"    => $profileThumbnailImageUrl,
                    "senderProfileSmallImageUrl"        => $profileSmallImageUrl,
                    "senderProfileMediumImageUrl"       => $profileMediumImageUrl,
                    "senderProfileLargeImageUrl"        => $profileLargeImageUrl,
                    "slug"                              => $sender->isSeller()? $sender->getStore()->getStoreSlug() : $sender->getSlug(),
                    "message"                           => $messageContent,
                    "isImage"                           => $message->getIsImage()? "1":"0",
                    "timeSent"                          => $message->getTimeSent()->format("Y-m-d H:i:s"),
                    "isSeen"                            => $message->getIsSeen()? "1":"0",
                    "timeSeen"                          => !is_null($message->getTimeSeen())? 
                                                                $message->getTimeSeen()->format("Y-m-d H:i:s") :
                                                                null
                )
            );
            
            $this->predis->publish("new_message", json_encode($messageDetails));
            $this->publishUnreadMessages($sender, $recipient);
        }

        return $this;
    }

    public function publishSeen(User $sender, User $recipient)
    {   
        $this->predis->publish("message_seen", json_encode(
            array(
                "namespace" => $this->jwtManager->encodeToken(array("userId" => $sender->getUserId())),
                "data"      => array(
                    "recipient" => $recipient->getUserId(),
                    "slug"      => $recipient->isSeller()? $recipient->getStore()->getStoreSlug() : $recipient->getSlug(),
                    "sender"    => $sender->getUserId(),
                    "senderSlug"=> $sender->isSeller()? $sender->getStore()->getStoreSlug() : $sender->getSlug(),
                )
            )
        ));
        
        $this->predis->publish("message_seen", json_encode(
            array(
                "namespace" => $this->jwtManager->encodeToken(array("userId" => $recipient->getUserId())),
                "data"      => array(
                    "recipient" => $recipient->getUserId(),
                    "slug"      => $recipient->isSeller()? $recipient->getStore()->getStoreSlug() : $recipient->getSlug(),
                    "sender"    => $sender->getUserId(),
                    "senderSlug"=> $sender->isSeller()? $sender->getStore()->getStoreSlug() : $sender->getSlug(),
                )
            )
        ));

        $this->publishUnreadMessages($sender, $recipient);

        return $this;
    }

    public function publishUnreadMessages(User $sender, User $recipient)
    {
        $senderUnreadMessages = $this->em->getRepository('YilinkerCoreBundle:Message')->getCountUnonepenedMessagesByUser($sender);
        $recipientUnreadMessages = $this->em->getRepository('YilinkerCoreBundle:Message')->getCountUnonepenedMessagesByUser($recipient);

        $this->predis->publish("unread_messages", json_encode(
            array(
                "namespace" => $this->jwtManager->encodeToken(array("userId" => $sender->getUserId())),
                "data"      => array(
                    "unreadMessages" => $senderUnreadMessages
                )
            )
        ));
        
        $this->predis->publish("unread_messages", json_encode(
            array(
                "namespace" => $this->jwtManager->encodeToken(array("userId" => $recipient->getUserId())),
                "data"      => array(
                    "unreadMessages" => $recipientUnreadMessages
                )
            )
        ));

        return $this;
    }

    public function publishConversationHead(Message $message)
    {
    	$sender 				= $message->getSender();
    	$recipient 				= $message->getRecipient();

        $messageDetails 		= array(
            "senderUid"         => $sender->getUserId(),
            "recipientUid"      => $recipient->getUserId(),
            "lastMessageDate"   => $message->getTimeSent()->format("Y-m-d H:i:s"),
            "message"           => $message->getMessage(),
            "isImage"           => $message->getIsImage()? "1":"0"
        );

		$senderDetails 		= $this->constructUserDetails($sender, $recipient);		
		$recipientDetails 	= $this->constructUserDetails($recipient, $sender);		

		$senderHeadDetails		= array_merge($messageDetails, $recipientDetails);
		$recipientHeadDetails	= array_merge($messageDetails, $senderDetails);

        $this->predis->publish("update_head", json_encode($senderHeadDetails));
        $this->predis->publish("update_head", json_encode($recipientHeadDetails));

        return $this;
    }


    public function publishContact(Contact $contact)
    {
    	$requestor = $contact->getRequestor();
    	$requestee = $contact->getRequestee();

		$requestorDetails 	= $this->constructUserDetails($requestor, $requestee);		
		$requesteeDetails	= $this->constructUserDetails($requestee, $requestor);

        $this->predis->publish("new_contact", json_encode($requestorDetails));
        $this->predis->publish("new_contact", json_encode($requesteeDetails));

        return $this;
    }

    private function constructUserDetails(User $user, $contactUser)
    {
        $userProfileImage 		= $user->getPrimaryImage();

        extract($this->getPrimaryImages($userProfileImage, $user));

        $userFullName 			= $user->getFullName();

        if($user->getUserType() == User::USER_TYPE_SELLER){
            $userFullName = $user->getStore()->getStoreName();
        }

        $recievedUnreadMessages = $user->getUnreadRecievedMessagesFromUser($contactUser);

        $userDevices = $user->getDevices()->count();
        $userIdleDevices = $user->getIdleDevices()->count();

        return array(
        	"namespace"			        => $this->jwtManager->encodeToken(array("userId" => $contactUser->getUserId())),
        	"userId"			        => $user->getUserId(),
            "slug"                      => $user->isSeller()? $user->getStore()->getStoreSlug() : $user->getSlug(),
        	"fullName"			        => $userFullName,
            "profileImageUrl"           => $profileImageUrl,
            "profileThumbnailImageUrl"  => $profileThumbnailImageUrl,
            "profileSmallImageUrl"      => $profileSmallImageUrl,
            "profileMediumImageUrl"     => $profileMediumImageUrl,
            "profileLargeImageUrl"      => $profileLargeImageUrl,
        	"isOnline"			        => $userDevices != $userIdleDevices? "1" : "0",
            "hasUnreadMessage"          => (string)$recievedUnreadMessages->count(),
    	);
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

    public function publishManufacturerProductUpdate(ManufacturerProduct $manufacturerProduct)
    {
        $productMaps = $this->em->getRepository('YilinkerCoreBundle:ManufacturerProductMap')
                                ->findByManufacturerProduct($manufacturerProduct);

        try {
            foreach ($productMaps as $productMap) {
                $this->predis->rpush(self::MANUFACTURER_PRODUCT_CHANNEL, json_encode(array(
                    'product' => $productMap->getProduct()->getProductId(),
                    'manufacturerProduct' => $manufacturerProduct->getManufacturerProductId(),
                )));
            }
        }
        catch (Exception $e) {}

        return true;
    }
}
