<?php

namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\MobileFeedBack;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class MobileFeedBackApiController extends YilinkerBaseController
{
    
    /**
     * Update the user information
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when error occured in updating or invalid data",
     *         401="Returned when the user is not authorized to update information",
     *         404={
     *           "Returned when the user is not found"
     *         }
     *     },
     *     parameters={
     *         {"name"="title", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="description", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="phoneModel", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="osVersion", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="osName", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="userType", "dataType"="int", "required"=true, "description"="0=buyer,1=affiliate, 2=seller,3=guest"},
     *     },
     *     section="MobileFeedBack"
     * )
     */
    public function addAction(Request $request)
    {
        $params = array(
            'title' => $request->get('title'),
            'description' => $request->get('description'),
            'phoneModel' => $request->get('phoneModel'),
            'osVersion' => $request->get('osVersion'),
            'osName'    => $request->get('osName'),
            'userType'  => $request->get('userType',null),
        );

        $user = $this->getUser();

        if ($request->getMethod() == 'POST') {

            $formErrorService = $this->get('yilinker_core.service.form.form_error');
            $form = $this->createForm('api_mobilefeedback', null, array('csrf_protection' => false));
            $form->submit($params);
            
            if($form->isValid()) {
                $this->save($params);
                return $this->resp(array('isSuccessful'=> true, 'data' => '', 'message' => 'Successfully Sent' ));
            
            }else {

                $error = $formErrorService->throwInvalidFields($form);
                
                $res['isSuccessful'] = false;
                $res['data']['error'] = $error;
                $res['message'] = "Please Provide required fields";

                return $this->resp($res);    
            }

        }
    }

    protected function resp($response=array())
    {
        $this->jsonResponse['data'] = $response['data'];
        $this->jsonResponse['isSuccessful'] = $response['isSuccessful'];
        $this->jsonResponse['message'] = $response['message'];

        return $this->jsonResponse(); 
    }

    protected function save($data=array())
    {
        if (!is_null($data['userType'])) {
            $type = $data['userType'];
        } else {
            $type = MobileFeedBack::USER_GUEST;
            if ($this->getUser()) {
                if ($this->getUser()->isSeller()) {
                    $type = MobileFeedBack::USER_SELLER;
                } else if ($this->getUser()->isAffiliate()) {
                    $type = MobileFeedBack::USER_AFFILIATE;   
                } else {
                    $type = MobileFeedBack::USER_BUYER;
                }
            }
        }
        
        $mobileFeedback = new MobileFeedBack();
        $mobileFeedback->setTitle($data['title']);
        $mobileFeedback->setDescription($data['description']);
        $mobileFeedback->setPhoneModel($data['phoneModel']);
        $mobileFeedback->setUser($this->getUser());
        $mobileFeedback->setUserType($type);
        $mobileFeedback->setOsVersion($data['osVersion']);
        $mobileFeedback->setOsName($data['osName']);

        $em = $this->getDoctrine()->getManager();
        $em->persist($mobileFeedback);

        $em->flush();
    }
}
