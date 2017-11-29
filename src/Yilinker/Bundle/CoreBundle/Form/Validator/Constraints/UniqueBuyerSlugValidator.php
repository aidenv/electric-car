<?php
namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueBuyerSlugValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        if(!is_null($value) OR $value != ""){
            $value = trim($value);
            $user = $this->userRepository
                          ->findBy(array("slug" => $value));

            if (count($user)) {
                $this->context
                     ->buildViolation($constraint->message)
                     ->setParameter('%string%', $value)
                     ->addViolation();
            }
        }
    }
}
