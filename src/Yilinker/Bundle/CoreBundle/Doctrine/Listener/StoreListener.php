<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Services\QrCode\Generator;

class StoreListener
{
    private $qrCodeGenerator;

    public function __construct(Generator $qrCodeGenerator)
    {
        $this->qrCodeGenerator = $qrCodeGenerator;
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Store){
            if($args->hasChangedField("storeSlug")){
                $this->qrCodeGenerator->generateStoreQrCode($entity, $entity->getStoreSlug());
            }
        }
    }
}
