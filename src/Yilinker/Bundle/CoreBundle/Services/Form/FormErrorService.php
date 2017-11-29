<?php

namespace Yilinker\Bundle\CoreBundle\Services\Form;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class FormErrorService
 * @package Yilinker\Bundle\CoreBundle\Services\Form
 */
class FormErrorService
{
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    /**
     * Required fields not supplied
     *
     * @param null $form
     * @return array
     */
    public function throwInvalidFields($form = null)
    {
        $errors = array();

        foreach($form->getErrors(true) as $error){
            $message = $this->translator->trans($error->getMessage());
            array_push($errors, $message);
        }

        return array_values(array_unique($errors));
    }

    public function throwNoFieldsSupplied()
    {
        $response = array(
            "isSuccessful" => false,
            "message" => "No fields supplied.",
            "data" => array()
        );

        return new JsonResponse($response, 400);
    }

    /**
     * @param $error
     * @param $message
     * @return JsonResponse
     */
    public function throwCustomErrorResponse($error, $message)
    {
        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->translator->trans($message),
            "data" => array(
                "errors" => $error,
            )
        ), 400);
    }

    /**
     * @param $message
     * @return JsonResponse
     * @internal param $error
     */
    public function throwResourceNotFoundResponse($message)
    {
        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->translator->trans($message),
            "data" => array()
        ), 404);
    }
}
