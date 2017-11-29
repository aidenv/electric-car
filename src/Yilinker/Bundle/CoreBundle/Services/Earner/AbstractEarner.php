<?php

namespace Yilinker\Bundle\CoreBundle\Services\Earner;

use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\Earning;
use Yilinker\Bundle\CoreBundle\Entity\User;

abstract class AbstractEarner
{
    protected $status;
    protected $user;
    protected $amount = 0;
    protected $em;
    protected $secondaryEntity = null;
    protected $parameter = array();

    abstract protected function createObject(&$earning);

    abstract public function earn();

    protected function createEarningEntity()
    {
        $earning = new Earning;
        $earning->setDateAdded(Carbon::now())
                ->setDateLastModified(Carbon::now());

        $this->createObject($earning);

        return $earning;
    }

    public function setSecondaryEntity($secondaryEntity)
    {
        $this->secondaryEntity = $secondaryEntity;

        return $this;
    }

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;

        return $this;
    }

    public function addParameter(array $parameter)
    {
        $this->parameter = array_merge($this->parameter, $parameter);

        return $this;
    }
}
