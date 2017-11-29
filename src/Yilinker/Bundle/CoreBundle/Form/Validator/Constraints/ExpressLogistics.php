<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class expressLogistics
 * @package Yilinker\Bundle\CoreBundle\Form\Validator\Constraints
 */
class ExpressLogistics extends Constraint
{

    public $message = 'Yilinker Express can only be selected if User warehouse is in the philippines';

    private $userWarehouse;

    public function __construct($userWarehouse)
    {
        $this->userWarehouse = $userWarehouse;
    }

    public function getUserWarehouse()
    {
        return $this->userWarehouse;
    }

    public function validatedBy()
    {
        return 'express_logistics';
    }

}
