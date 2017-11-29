<?php
namespace Yilinker\Bundle\MerchantBundle\Controller\Api\v2;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use OAuth2\OAuth2ServerException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;

class UserApiController extends YilinkerBaseController
{
    use FormHandler;

    /**
     * Minimal user update 
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Edit Profile",
     *     statusCodes={
     *         200="Returned when successful",
     *         400="Returned when error occured in updating or invalid data"
     *     },
     *     parameters={
     *         {"name"="firstName", "dataType"="string", "required"=true},
     *         {"name"="lastName", "dataType"="string", "required"=true},
     *         {"name"="email", "dataType"="string", "required"=true},
     *         {"name"="tin", "dataType"="string", "required"=false},
     *         {"name"="referralCode", "dataType"="string", "required"=false},
     *         {"name"="validId", "dataType"="string", "required"=false},
     *         {"name"="isSent", "dataType"="boolean", "required"=true},
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */
    public function updateUserAction(Request $request)
    {
        $postData = array(
            "firstName" => $request->get("firstName", null),
            "lastName" => $request->get("lastName", null),
            "tin" => $request->get("tin", null),
            "email" => $request->get("email", null),
            "referralCode" => $request->get("referralCode", null),
            "validId" => $request->get("validId", null),
        );

        $isSent = $request->get("isSent", false);

        $authenticatedUser = $this->getUser();
        $form = $this->transactForm(
                    "update_merchant_info_v2", 
                    null, 
                    $postData, 
                    array(
                        "csrf_protection" => false,
                        "user" => $authenticatedUser,
                        "excludeUserId" => $authenticatedUser->getUserId(),
                        "userType" => $authenticatedUser->getUserType(),
                        "storeType" => $authenticatedUser->getStore()->getStoreType(),
                    )
        );

        if($form->isValid()){
            
            $em = $this->get("doctrine.orm.entity_manager");
            $data = $form->getData();

            $accountManager = $this->get("yilinker_core.service.account_manager");
            $imageUploader = $this->get("yilinker_core.service.image_uploader");
            $mailer = $this->container->get("yilinker_core.service.user.mailer");
            $verification = $this->container->get("yilinker_core.service.user.verification");

            $accreditationApplication = $authenticatedUser->getAccreditationApplication();

            $authenticatedUser->setFirstName($data["firstName"])
                              ->setLastName($data["lastName"])
                              ->setEmail($data["email"]);

            if(
                ($accreditationApplication && $accreditationApplication->getIsBusinessEditable()) ||
                !$authenticatedUser->getTin()
            ){
                $authenticatedUser->setTin($data["tin"]);
            }

            if($data["referralCode"] && !$authenticatedUser->getUserReferral()){
                $accountManager->processReferralCode($data["referralCode"], $authenticatedUser);
            }

            if(!is_null($data["validId"])){
                $imageUploader->uploadLegalDoc(
                    $data["validId"], 
                    $authenticatedUser,
                    ImageUploader::UPLOAD_TYPE_VALID_ID
                );
            }

            if(!filter_var($isSent, FILTER_VALIDATE_BOOLEAN) && $authenticatedUser->getEmail()){
                $verification->createVerificationToken($authenticatedUser, $authenticatedUser->getEmail());
                $mailer->sendEmailVerification($authenticatedUser);    
            }

            $em->flush();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Account was successfully updated.",
                "data" => array()
            ), 200);
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }

    /**
     * Send Email Verification
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Edit Profile",
     *     parameters={
     *         {"name"="access_token", "dataType"="string", "required"=true},
     *         {"name"="email", "dataType"="string", "required"=true},
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */
    public function verfiyEmailAction(Request $request)
    {
        $data = array(
            "email" => $request->get('email')
        );
        
        $this->jsonResponse = $this->get('yilinker_merchant.service.user.account_manager')->sendVerification($this->getUser(),$data);   
        return $this->jsonResponse();
    }

    /**
     * Authenticate User
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="User",
     *     parameters={
     *         {"name"="client_id", "dataType"="string", "required"=true, "description"="Oauth client ID"},
     *         {"name"="client_secret", "dataType"="string", "required"=true, "description"="Oauth client secret"},
     *         {"name"="grant_type", "dataType"="string", "required"=true, "description"="Oauth grant type"},
     *         {"name"="email", "dataType"="string", "required"=true, "description"="Either email or contact number."},
     *         {"name"="password", "dataType"="string", "required"=true, "description"="Minimum of 8 atleast 1 number."},
     *         {"name"="refresh_token", "dataType"="string", "required"=false, "description"="Only use if using refresh_token grant type"},
     *     },
     *     views = {"user", "default", "v2"}
     * )
     */
    public function tokenAction(Request $request)
    {
        $oauthServer = $this->get('fos_oauth_server.server');
        try {
            $request->request->set('version', $request->get('version'));

            $response = $oauthServer->grantAccessToken($request);
            $content = $response->getContent();
            $jsonContent = json_decode($content, true);
            $token = $jsonContent['access_token'];

            $accessToken = $oauthServer->verifyAccessToken($token);

            return $response;
        } catch (OAuth2ServerException $e) {
            return $e->getHttpResponse();
        }
    }
}
