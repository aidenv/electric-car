<?php

namespace Yilinker\Bundle\CoreBundle\Traits;

trait FormHandler
{
    public function transactForm($formType, $entity = null, $request = array(), $options = array())
    {
        if(method_exists($this, "get")){
            $factory = $this->get("form.factory");
        }
        else{
            if(method_exists($this, "getMainContainer") && $this->getMainContainer()){
                $factory = $this->getMainContainer()->get("form.factory");
            }
            else{
                $factory = $this->getContainer()->get("form.factory");
            }
        }

        $form = $factory->create($formType, $entity, $options);
        $form->submit($request);

        return $form;
    }
    
    public function getErrors($form, $isArray = true)
    {
        if(method_exists($this, "get")){
            $formErrorService = $this->get("yilinker_core.service.form.form_error");
        }
        else{
            if(method_exists($this, "getMainContainer") && $this->getMainContainer()){
                $formErrorService = $this->getMainContainer()->get("yilinker_core.service.form.form_error");
            }
            else{
                $formErrorService = $this->getContainer()->get("yilinker_core.service.form.form_error");
            }
        }

        $errors = $formErrorService->throwInvalidFields($form);

        if($isArray){
            return $errors;
        }

        return implode("\n", $errors);
    }
}
