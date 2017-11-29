<?php

namespace Yilinker\Bundle\FrontendBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsActiveManufacturerProductValidator extends ConstraintValidator
{
    private $manufacturerProductRepository;

    public function __construct(EntityRepository $manufacturerProductRepository)
    {
        $this->manufacturerProductRepository = $manufacturerProductRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $manufacturerProduct = $this->manufacturerProductRepository
                                    ->findOneBy(array(
                                        'status' => ManufacturerProduct::STATUS_ACTIVE,
                                        'manufacturerProductId' => $value,
                                    ));

        if ($manufacturerProduct === null) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
