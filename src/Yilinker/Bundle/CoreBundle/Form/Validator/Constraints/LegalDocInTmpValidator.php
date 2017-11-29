<?php
namespace Yilinker\Bundle\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LegalDocInTmpValidator extends ConstraintValidator
{
    public function validate($fileName, Constraint $constraint)
    {
        $options = $constraint->getOptions();

        $dir = "assets/legal_documents/tmp/".$options["user"]->getUserId().DIRECTORY_SEPARATOR;

        if($fileName && !file_exists($dir.$fileName)){
            $this->context->addViolation($constraint->message);
        }
    }
}
