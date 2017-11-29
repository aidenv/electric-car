<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueStoreNameValidator extends ConstraintValidator
{
    private $storeRepository;

    public function __construct(EntityRepository $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }
    
    public function validate($storeName, Constraint $constraint)
    {
        $options = $constraint->getOptions();
        
        $user = array_key_exists("user", $options)? $options["user"] : null;
        $store = $this->storeRepository->getStoreByStoreName($storeName, $user);

        if($store){
            $this->context
                 ->buildViolation($constraint->message)
                 ->setParameter('%string%', $storeName)
                 ->addViolation();
        }
    }
}
