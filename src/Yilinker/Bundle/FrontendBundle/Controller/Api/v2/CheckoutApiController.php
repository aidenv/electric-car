<?php

namespace Yilinker\Bundle\FrontendBundle\Controller\Api\v2;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Country;
use Yilinker\Bundle\CoreBundle\Services\User\Verification;
use Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

class CheckoutApiController extends Controller
{
    use ContactNumberHandler;

    protected $checkoutService = null;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->checkoutService = $this->get('yilinker_front_end.service.checkout');
        $this->checkoutService->api = true;
    }

    /**
     * useGuestUser
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Checkout",
     *     parameters={
     *         {"name"="firstName", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="lastName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="contactNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="confirmationCode", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="title", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="unitNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="buildingName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="streetNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="streetName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="subdivision", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="zipCode", "dataType"="string", "required"=false, "description"=""},
     *     },

     * )
     */
    public function useGuestUserAction(Request $request)
    {
        $data = array(
            'isSuccessful'  => false,
            'data'          => new \StdClass(),
            'message'       => ''
        );

        $formErrorService = $this->get('yilinker_core.service.form.form_error');

        try {

            $firstName = $request->get("firstName", null);
            $lastName = $request->get("lastName", null);
            $contactNumber = $request->get("contactNumber", null);
            $confirmationCode = $request->get("confirmationCode", null);

            $contactNumber = $this->formatContactNumber(Country::COUNTRY_CODE_PHILIPPINES, $contactNumber);

            $existingUser = $this->getDoctrine()->getManager()->getRepository("YilinkerCoreBundle:User")
                                 ->findOneBy(array(
                                    "contactNumber" => $contactNumber,
                                    "userType" => User::USER_TYPE_BUYER
                                ));

            $options = array('csrf_protection' => false);
            $options['excludeUserId'] = $existingUser? $existingUser->getUserId() : null;

            $userGuestForm = $this->createForm('user_guest', null, $options);

            $postData = array(
                "firstName" => $firstName,
                "lastName" => $lastName,
                "contactNumber" => $contactNumber,
                "confirmationCode" => $confirmationCode.":".$contactNumber
            );

            $userGuestForm->submit($postData);
            if ($userGuestForm->isValid()) {
                $user = $userGuestForm->getData();
                $user = $this->checkoutService->saveGuestUser($user);
                $data['isSuccessful'] = true;
            }
            else {
                $errors = $formErrorService->throwInvalidFields($userGuestForm);
                $data['message'] = implode("\n", $errors);
            }

            $userAddressForm = $this->createForm('user_address', null, array(
                'csrf_protection'   => false
            ));

            $title = $request->get("title", null);
            $unitNumber = $request->get("unitNumber", null);
            $buildingName = $request->get("buildingName", null);
            $streetNumber = $request->get("streetNumber", null);
            $streetName = $request->get("streetName", null);
            $subdivision = $request->get("subdivision", null);
            $zipCode = $request->get("zipCode", null);

            $addressData = array(
                "title" => $title,
                "unitNumber" => $unitNumber,
                "buildingName" => $buildingName,
                "streetNumber" => $streetNumber,
                "streetName" => $streetName,
                "subdivision" => $subdivision,
                "zipCode" => $zipCode
            );

            $userAddressForm->submit($addressData);
            if ($userAddressForm->isValid()) {
                $address = $userAddressForm->getData();
                $this->checkoutService->addAddress($address);
            }
            else {
                $data['isSuccessful'] = false;
                $errors = $formErrorService->throwInvalidFields($userAddressForm);
                $data['message'] = implode("\n", $errors);
            }
        } catch (\Exception $e) {
            $data['isSuccessful'] = false;
            if (array_key_exists('message', $data) && !$data['message']) {
                $data['message'] = $e->getMessage();
            }
        }

        return new JsonResponse($data);
    }

    /**
     * registerGuestUser
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Checkout",
     *     parameters={
     *         {"name"="firstName", "dataType"="string", "required"=true, "description"=""},
     *         {"name"="lastName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="contactNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="confirmationCode", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="title", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="unitNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="buildingName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="streetNumber", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="streetName", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="subdivision", "dataType"="string", "required"=false, "description"=""},
     *         {"name"="zipCode", "dataType"="string", "required"=false, "description"=""},
     *     },

     * )
     */
    public function registerGuestUserAction(Request $request)
    {
        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );

        try {
            if ($this->getUser() instanceof User) {
                throw new Exception('Already a user');
            }
            $user = $this->checkoutService->getCheckoutUser();
            $userGuestForm = $this->createForm('user_guest', $user, array(
                'csrf_protection'   => false,
                'signup_completion' => true
            ));

            $params = $request->request->get('user_guest', array());
            if (!$params) {
                $password = $request->get('password');
                $referralCode = $request->get('referralCode');
                $params['plainPassword']['first'] = $password;
                $params['plainPassword']['second'] = $password;
                $params['referralCode'] = $referralCode;
                $request->request->set('user_guest', $params);
            }

            $userGuestForm->handleRequest($request);
            if ($userGuestForm->isValid()) {

                $jwtService = $this->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                $ylaService = $this->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $response = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                if(is_array($response) && array_key_exists("isSuccessful", $response) && $response["isSuccessful"]){
                    $em = $this->getDoctrine()->getEntityManager();
                    $user->setAccountId($response["data"]["userId"]);
                    $em->flush();
                }

                $data['isSuccessful'] = true;
            }
            else {
                $data['message'] = $userGuestForm->getErrors(true)->__toString();
            }
        } catch (\Exception $e) {
            $data['isSuccessful'] = false;
            if (array_key_exists('message', $data) && !$data['message']) {
                $data['message'] = $e->getMessage();
            }
        }

        return new JsonResponse($data);
    }

    /**
     * selectOnCart
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Checkout",
     *     parameters={
     *         {"name"="cart", "dataType"="string", "required"=true, "description"=""},
     *     },

     * )
     */
    public function selectOnCartAction(Request $request)
    {
        try {
            $selectedOnCart = $request->get('cart', null);
            if (is_array($selectedOnCart)) {
                $this->checkoutService->setSelectedOnCart($selectedOnCart);
            }

            $selectedCart = $this->checkoutService->getSelectedOnCart();
            foreach ($selectedCart as &$product) {
                $product['productUnits'] = array_values($product['productUnits']);
            }
        } catch (YilinkerException $e) {
            $message = $e->getMessage();
        }


        $data = array(
            'isSuccessful'  => true,
            'data'          => isset($selectedCart) ? $selectedCart: '',
            'message'       => isset($message) ? $message: ''
        );

        return new JsonResponse($data);
    }

    /**
     * setAddress
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Checkout",
     *     parameters={
     *         {"name"="address_id", "dataType"="string", "required"=true, "description"=""},
     *     },

     * )
     */
    public function setAddressAction(Request $request)
    {
        $addressId = $request->get('address_id');
        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );
        try {
            $userAddress = $this->checkoutService->setDeliveryAddress($addressId);
            $data['isSuccessful'] = true;
            $data['data'] = $userAddress->toArray();
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    public function paymentCodAction(Request $request)
    {
        $authorizationChecker = $this->get("security.authorization_checker");

        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );

        try {
            $userOrder = $this->checkoutService->paymentCOD();
            $data['isSuccessful'] = true;
            $data['data'] = $userOrder->toArray(true, !$authorizationChecker->isGranted("IS_AUTHENTICATED_FULLY"));
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    public function paymentPesopayAction(Request $request)
    {
        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );
        try {
            $paymentUrl = $this->checkoutService->paymentPesopay();
            $data['isSuccessful'] = true;
            $data['data'] = array(
                'paymentUrl'    => $paymentUrl
            );
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    public function paymentDragonpayAction(Request $request)
    {
        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );
        try {
            $paymentUrl = $this->checkoutService->paymentDragonpay();
            $data['isSuccessful'] = true;
            $data['data'] = array(
                'paymentUrl'    => $paymentUrl
            );
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    public function overviewAction(Request $request)
    {
        $authorizationChecker = $this->get("security.authorization_checker");

        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => ''
        );
        try {
            $ref = $request->get('transactionId');
            $userOrder = $this->checkoutService->getUserOrder($ref);

            if ($request->get('transactionClear', false)) {
                $this->checkoutService->throwError = false;
                $this->checkoutService->clearSelectedOnCart();
                $this->checkoutService->clearSession();
            }

            $data['isSuccessful'] = true;
            $data['data'] = $userOrder->toArray(true, !$authorizationChecker->isGranted("IS_AUTHENTICATED_FULLY"));
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }

    public function updateContactNumberAction(Request $request)
    {
        $response = array(
            'isSuccessful' => false,
            'message' => 'Contact number update is currently not available',
            'data' => array(),
        );

        $em = $this->getDoctrine()->getManager();
        $user = $this->checkoutService->getCheckoutUser();

        $form = $this->createForm('core_change_contact_number', null, array(
            'csrf_protection' => false,
            'userId'          => $user->getUserId(),
        ));
        $form->submit(array(
            'contactNumber' =>  $request->get('newContactNumber', ''),
        ));
        if($form->isValid()){
            $formData = $form->getData();
            $response = $this->get('yilinker_core.service.sms.sms_service')
                             ->sendUserVerificationCode($user, $formData['contactNumber']);

            if($response['isSuccessful']){
                $response['data'] = array(
                    'expiration_in_minutes' => Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES
                );
            }
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

    public function verifyCodeAction(Request $request)
    {
        $user = $this->checkoutService->getCheckoutUser();

        $code = $request->get('code', '');
        $isVerificationSuccessful = $this->get('yilinker_core.service.user.verification')
                                         ->confirmVerificationToken($user, $code, $type = UserVerificationToken::TYPE_CONTACT_NUMBER);

        return new JsonResponse(array(
            'isSuccessful' => $isVerificationSuccessful,
            'data' => array(),
            'message' => $isVerificationSuccessful ? "Mobile successfully verified" : "Code is either invalid or is already expired",
        ), $isVerificationSuccessful? 200:400);
    }

    public function resendMobileVerificationAction(Request $request)
    {
        $user = $this->checkoutService->getCheckoutUser();

        $response = $this->get('yilinker_core.service.sms.sms_service')
                         ->sendUserVerificationCode($user);

        // $response["data"] = json_decode($response["data"], true);
        $response["data"]["expiration_in_minutes"] = Verification::VERIFICATION_CODE_EXPIRATION_IN_MINUTES;

        return new JsonResponse($response);
    }

    public function applyVoucherCodeAction(Request $request)
    {
        $data = array(
            'isSuccessful'  => false,
            'data'          => array(),
            'message'       => 'Invalid or Expired Code'
        );

        if ($this->checkoutService->hasFlashSaleItem()) {
            return new JsonResponse($data);
        }

        $voucherCode = $request->get('voucherCode');
        try{
            $result = $this->checkoutService->setVoucherCode($voucherCode);
            if ($result) {
                $data['isSuccessful'] = true;
                $data['data'] = $result;
                $data['message'] = '';
            }
        }
        catch(YilinkerException $e){
            $data['message'] = $e->getMessage();
        }

        return new JsonResponse($data);
    }
}
