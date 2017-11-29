<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;
use Yilinker\Bundle\CoreBundle\Entity\Logistics;

/**
 * Class expressLogisticsValidator
 * @package Yilinker\Bundle\CoreBundle\Form\Validator\Constraints
 */
class ExpressLogisticsValidator extends ConstraintValidator
{

    /**
     * @var EntityManager $em
     */
    private $em;

    /**
     * @var ServiceContainer $container
     */
    private $container;

    /**
     * Constructor
     *
     * @param ServiceContainer $container
     */
    public function __construct($container)
    {
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->container = $container;
    }

    public function validate($logistics, Constraint $constraint)
    {
        $userWareHouseEntity = $this->em->getRepository('YilinkerCoreBundle:UserWarehouse')->find($constraint->getUserWarehouse());
        $warehouseLocationEntity = $userWareHouseEntity->getLocation()->getParentByLocationType(LocationType::LOCATION_TYPE_COUNTRY);
        $trans = $this->container->get('yilinker_core.translatable.listener');

        if ($logistics instanceof Logistics &&
            $logistics->getLogisticsId() == Logistics::YILINKER_EXPRESS &&
            $warehouseLocationEntity instanceof Location &&
            $warehouseLocationEntity->getCode() != Location::LOCATION_CODE_PHILIPPINES &&
            $trans->getCountry() == Location::LOCATION_CODE_PHILIPPINES) {
            $this->context->buildViolation($constraint->message)
                 ->addViolation();
        }
    }

}
