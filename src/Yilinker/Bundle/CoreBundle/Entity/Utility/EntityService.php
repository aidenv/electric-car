<?php

namespace Yilinker\Bundle\CoreBundle\Entity\Utility;

use Yilinker\Bundle\CoreBundle\Entity\Utility\EntityServiceable;

class EntityService
{
    protected $entity;

    public function entity(EntityServiceable $entity)
    {
        if ($entity) {
            $this->entity = $entity;
        }

        return $this->entity;
    }
}