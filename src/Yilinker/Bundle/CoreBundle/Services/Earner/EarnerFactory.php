<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Doctrine\ORM\EntityManager;

use Yilinker\Bundle\CoreBundle\Entity\OrderProduct;
use Yilinker\Bundle\CoreBundle\Entity\ProductReview;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserFollowHistory;
use Yilinker\Bundle\CoreBundle\Entity\UserOrder;

use Yilinker\Bundle\CoreBundle\Services\Earner\OrderProductEarner;
use Yilinker\Bundle\CoreBundle\Services\Earner\ProductReviewEarner;
use Yilinker\Bundle\CoreBundle\Services\Earner\RegistrationEarner;
use Yilinker\Bundle\CoreBundle\Services\Earner\UserFollowEarner;
use Yilinker\Bundle\CoreBundle\Services\Earner\UserOrderEarner;

class EarnerFactory
{
    private $em;

    public function get($object)
    {
        $instance = null;

        if ($object instanceof OrderProduct) {
            $instance = new OrderProductEarner;
        }
        elseif ($object instanceof ProductReview) {
            $instance = new ProductReviewEarner;
        }
        elseif ($object instanceof User) {
            $instance = new RegistrationEarner;
        }
        elseif ($object instanceof UserFollowHistory) {
            $instance = new UserFollowEarner;
        }
        elseif ($object instanceof UserOrder) {
            $instance = new UserOrderEarner;
        }

        return $instance->setEntityManager($this->em)
                        ->setSecondaryEntity($object);
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }
}
