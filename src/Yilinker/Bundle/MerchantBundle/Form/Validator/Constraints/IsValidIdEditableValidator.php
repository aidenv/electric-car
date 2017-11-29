<?php

namespace Yilinker\Bundle\MerchantBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;

class IsValidIdEditableValidator extends ConstraintValidator
{
    private $legalDocumentTypeRepository;

    public function __construct($legalDocumentTypeRepository)
    {
        $this->legalDocumentTypeRepository = $legalDocumentTypeRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        $value = trim($value);
        
        $options = $constraint->getOptions();
        $user = array_key_exists("user", $options)? $options["user"] : null;

        $accreditationApplication = $user && $user->getAccreditationApplication()? $user->getAccreditationApplication() : null;

        $legalDocumentType = $this->legalDocumentTypeRepository->find(LegalDocumentType::TYPE_VALID_ID);

        $validLegalType = $accreditationApplication->getLegalDocumentByType($legalDocumentType);

        $tinNotEditable = (
            !is_null($accreditationApplication) &&
            $value && 
            $legalDocumentType && $validLegalType
        )? true : false;


        if ($validLegalType && $validLegalType->getIsEditable()) {
            $tinNotEditable = false;
        }

        $tinNotBlank = ($value != "" || !is_null($value))? true : false;

        if ($tinNotEditable && $tinNotBlank){
            $this->context
                 ->buildViolation($constraint->message)
                 ->addViolation();
        }
    }

}