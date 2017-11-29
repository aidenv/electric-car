<?php

namespace Yilinker\Bundle\BackendBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class UniqueUsername
 * @package Yilinker\Bundle\BackendBundle\Form\Validator\Constraints
 */
class UniqueUsernameValidator extends ConstraintValidator
{

    /**
     * @var EntityRepository
     */
    private $adminUserRepository;

    /**
     * @param EntityRepository $adminUserRepository
     */
    public function __construct(EntityRepository $adminUserRepository)
    {
        $this->adminUserRepository = $adminUserRepository;
    }

    /**
     * Check Username if exists
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);
        $user = $this->adminUserRepository
                     ->findOneByUsername($value);

        if (strlen(trim($value)) > 0 && $user) {
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }

    }

}
