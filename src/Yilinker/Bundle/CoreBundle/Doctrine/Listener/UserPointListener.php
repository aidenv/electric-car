<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Carbon\Carbon;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserPoint;
use Yilinker\Bundle\CoreBundle\Entity\UserPointRegistration;
use Yilinker\Bundle\CoreBundle\Entity\UserPointPurchase;
use Yilinker\Bundle\CoreBundle\Entity\UserPointReferralPurchase;
use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;

class UserPointListener
{
    protected $container;
    protected $em;
    protected $uow;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();
        $entity = $args->getEntity();

        if ($entity instanceof User) {
            $this->userRegistrationPoints($entity);
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->em = $args->getEntityManager();
        $this->uow = $this->em->getUnitOfWork();
        $entity = $args->getEntity();

        if ($entity instanceof OrderProduct) {
            $changes = $this->uow->getEntityChangeSet($entity);
            if (array_key_exists('orderProductStatus', $changes)) {
                $orderProductStatus = array_pop($changes['orderProductStatus']);
                if ($orderProductStatus && 
                    $orderProductStatus->getOrderProductStatusId() == 
                    OrderProduct::STATUS_SELLER_PAYMENT_RELEASED) {
                    $this->referralPurchasePoints($entity);
                    $this->purchasePoints($entity);
                }
            }
        }
    }

    public function userRegistrationPoints($entity)
    {
        if ($entity->canEarnPoints()) {
            $userPointRegistration = new UserPointRegistration;
            $userPointRegistration->setSource($entity);
            $userPointRegistration->setPoints(UserPoint::BUYER_REGISTRATION_POINT);
            $userPointRegistration->setUser($entity);
            $userPointRegistration->setType(UserPoint::BUYER_REGISTRATION);
            $userPointRegistration->setDateAdded(Carbon::now());

            $this->em->persist($userPointRegistration);
        }
    }

    public function referralPurchasePoints($entity)
    {
        $buyer = $entity->getOrder()->getBuyer();
        $referral = $buyer->getUserReferral();
        if ($referral) {
            $referrer = $referral->getReferrer();

            $userPointReferralPurchase = new UserPointReferralPurchase;
            $userPointReferralPurchase->setSource($entity);
            $userPointReferralPurchase->setPoints(5);
            $userPointReferralPurchase->setUser($referrer);
            $userPointReferralPurchase->setType(UserPoint::REFERRAL_PURCHASE);

            $tbUserPointReferralPurchase = $this->em->getRepository('YilinkerCoreBundle:UserPointReferralPurchase');
            if (!$tbUserPointReferralPurchase->entityExists($userPointReferralPurchase)) {
                $this->customEMSaveUserPointOrderProduct($userPointReferralPurchase);
            }
        }
    }

    public function purchasePoints($entity)
    {
        $minimum = 0;
        $increment = 200;
        $points = 1;

        $order = $entity->getOrder();
        $orderNet = $order->getNet();
        if ($orderNet < ($minimum + $increment)) {
            return;
        }
        $paymentReleased = $this->em->getReference('YilinkerCoreBundle:OrderProductStatus', OrderProduct::STATUS_SELLER_PAYMENT_RELEASED);
        $orderProducts = $order->getOrderProductWithStatus($paymentReleased);
        $releasedAmount = 0;
        foreach ($orderProducts as $orderProduct) {
            $releasedAmount += $orderProduct->getNet();
        }

        if (!$orderProducts->contains($entity)) {
            $releasedAmount += $entity->getNet();
        }
        $amount = $releasedAmount - $minimum;
        if ($amount < 0) {
            return;
        }
        $pointsMultiplier = intval($amount / $increment);
        if ($pointsMultiplier) {
            $userPointPurchase = new UserPointPurchase;
            $userPointPurchase->setSource($entity);
            $userPointPurchase->setPoints($points * $pointsMultiplier);
            $userPointPurchase->setUser($order->getBuyer());
            $userPointPurchase->setType(UserPoint::PURCHASE);
            $this->customEMSaveUserPointOrderProduct($userPointPurchase);
        }
    }

    /**
     * for user points with order product only
     */
    private function customEMSaveUserPointOrderProduct($userPoint)
    {
        $customEm = $this->container->get('doctrine.orm.custom_entity_manager');
        $customEm->clear();
        $orderProduct = $userPoint->getSource();
        $orderProduct = $customEm->getReference('YilinkerCoreBundle:OrderProduct', $orderProduct->getOrderProductId());
        $userPoint->setSource($orderProduct);

        $user = $userPoint->getUser();
        $user = $customEm->getReference('YilinkerCoreBundle:User', $user->getUserId());
        $userPoint->setUser($user);

        $customEm->persist($userPoint);
        $customEm->flush();
    }
}