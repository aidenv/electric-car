<?php
namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueSlugValidator extends ConstraintValidator
{
    private $storeRepository;
    
    private $router;

    public function __construct(EntityRepository $storeRepository, $router)
    {
        $this->storeRepository = $storeRepository;
        $this->router = $router;
    }
    
    public function validate($value, Constraint $constraint)
    {
        $routeCollection = $this->router->getRouteCollection()->all();
        
        $registeredRoutes = array();
        foreach ($routeCollection as $route) {
            array_push($registeredRoutes, $route->getPath());
        }
        
        if(!is_null($value) OR $value != ""){
            $value = trim($value);

            $user = $constraint->getUser();
            $store = $this->storeRepository->getStoreByStoreSlug($value, $user);

            if (count($store) OR in_array(DIRECTORY_SEPARATOR.$value, $registeredRoutes)) {
                $this->context
                     ->buildViolation($constraint->message)
                     ->setParameter('%string%', $value)
                     ->addViolation();
            }
        }
    }
}
