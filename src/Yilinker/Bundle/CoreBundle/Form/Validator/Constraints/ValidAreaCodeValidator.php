<?php
namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidAreaCodeValidator extends ConstraintValidator
{
    private $countryRepository;

    public function __construct(EntityRepository $countryRepository)
    {
        $this->countryRepository = $countryRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        $country = $this->countryRepository->findOneBy(array(
            "areaCode" => $value
        ));

        if (!$country) {
            $this->context
                 ->buildViolation($options['message'])
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
