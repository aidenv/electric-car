<?php

namespace Yilinker\Bundle\CoreBundle\Entity\Utility;

use Yilinker\Bundle\CoreBundle\Entity\Utility\EntityService;

class EntityServiceable
{
    public $service;

    public function service(EntityService $service = null)
    {
        if ($service) {
            $service->entity($this);
            $this->service = $service;
        }

        return $this->service;
    }
}