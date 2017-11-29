<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueNewsletterEmailValidator extends ConstraintValidator
{
    private $newsLetterRepository;

    public function __construct(EntityRepository $newsLetterRepository)
    {
        $this->newsLetterRepository = $newsLetterRepository;
    }

    public function validate($value, Constraint $constraint)
    {

        $value = trim($value);

        $email = $this->newsLetterRepository
                     ->findOneBy(array(
                         'email'    => $value,
                         'isActive' => true,
                     ));

        if (strlen(trim($value)) > 0 && $email) {
            $this->context->addViolation(
                $constraint->message,
                array('%string%' => $value)
            );
        }
    }
}
