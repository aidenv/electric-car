<?php

namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Yilinker\Bundle\CoreBundle\Entity\User;

class UniqueEmailValidator extends ConstraintValidator
{
    private $userRepository;

    public function __construct(EntityRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);

        $options = $constraint->getOptions();
        $userType = array_key_exists('userType', $options) ? $options['userType']: null;
        $storeType = array_key_exists('storeType', $options) ? $options['storeType']: null;

        if (!$options['repoMethod']) {
            if($options["excludeUserId"] !== null){
                $user = $this->userRepository
                             ->findUserByEmailExcludeId(
                                    $value, 
                                    $options["excludeUserId"], 
                                    null, 
                                    $userType,
                                    null,
                                    $storeType
                        );
            }
            else{
                $user = $this->userRepository
                             ->findUserByEmailExcludeId($value, null, null, $userType, null, $storeType);
            }
        }
        else {
            array_unshift($options['repoMethodArgs'], $value);
            $user = call_user_func_array(array($this->userRepository, $options['repoMethod']), $options['repoMethodArgs']);
        }
        
        if (strlen(trim($value)) > 0 && $user) {
            $this->context
                 ->buildViolation($options['message'])
                 ->setParameter('%string%', $value)
                 ->addViolation();
        }
    }
}
