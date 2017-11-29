<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Yilinker\Bundle\CoreBundle\Controller\Custom\CustomController as Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\PaymentMethod;
use Yilinker\Bundle\CoreBundle\Entity\PaymentPostbackLog;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Yilinker\Bundle\CoreBundle\Exception\YilinkerException;
use Yilinker\Bundle\CoreBundle\Controller\Custom\UserVerifiedController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response as BuzzReponse;
use Buzz\Client\Curl;

class CheckoutController extends Controller implements UserVerifiedController
{
    const EASYSHOP_TIMEOUT_SEC = 10; 

    protected $checkoutService = null;

    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->checkoutService = $this->get('yilinker_front_end.service.checkout');
        $this->redirectURL = $this->generateUrl('checkout_type');
    }

    public function allowUnverifiedActions()
    {
        return array();
    }

    public function unverifiedAction()
    {
        return $this->render('YilinkerFrontendBundle:Checkout:unverified_user.html.twig');
    }

    public function loginAction(Request $request)
    {
        $authenticationUtils = $this->get('security.authentication_utils');
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastEmail = $authenticationUtils->getLastUsername();
        $token = $this->get('form.csrf_provider')->generateCsrfToken('user_forgot_password_code');

        $data = compact('lastEmail', 'error', 'token');
        
        return $this->render('YilinkerFrontendBundle:Checkout:login.html.twig', $data);
    }

    public function buynowAction(Request $request)
    {
        $productId = $request->get('productId');
        $unitId = $request->get('unitId');
        $quantity = $request->get('quantity');
        $sellerId = $request->get('sellerId');
        
        $success = $this->checkoutService->buynow($productId, $unitId, $quantity, $sellerId);
        if ($success) {
            //return $this->redirect($this->generateUrl('checkout_type'));
            return $this->forward('YilinkerFrontendBundle:Checkout:type', array('request' => $request));
        }
        else {
            return $this->redirectBack();
        }
    }

    /**
    * Render Checkout Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function typeAction(Request $request)
    {
        if ($request->isMethod('POST') && $request->get('cart')) {
            $this->checkoutService->setSelectedOnCart($request->get('cart'));
        }

        $this->checkoutService->throwError = false;
        $this->checkoutService->clearConsignee();
        $authorizationChecker = $this->get('security.authorization_checker');
        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            return $this->redirectResponse($this->generateUrl('checkout_summary'));
            //return $this->redirect($this->generateUrl('checkout_summary'));
        }

        $user = $this->checkoutService->getCheckoutUser();
        $cart = $this->checkoutService->getSelectedOnCart();
        $form = $this->createForm('user_guest', $user);
        $form->handleRequest($request);
        if ($form->isValid()) {
            $user = $form->getData();
            if ($user) {
                $user = $this->checkoutService->saveGuestUser($user);
                //return $this->redirect($this->generateUrl('checkout_summary'));
                return $this->redirectResponse($this->generateUrl('checkout_summary'));
            }
        }
        $form = $form->createView();

        $data = compact(
            'user',
            'cart',
            'request',
            'form'
        );

        return $this->render('YilinkerFrontendBundle:Checkout:type.html.twig', $data);
    }


    protected function redirectResponse($url)
    {
        $response = new RedirectResponse($url, 302); 
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);
        
        return $response;
    }

    /**
    * Render Summary Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function summaryAction(Request $request)
    {
        try {
            $user = $this->checkoutService->getCheckoutUser();
            $this->checkoutService->catchConsignee();
            $this->checkoutService->throwError = false;
            $cart = $this->checkoutService->getSelectedOnCart();
            $this->checkIfOutOfStock($cart);

            $form = $this->createForm('user_address', null, array('user' => $this->getUser()));
            $form->handleRequest($request);
            if ($form->isValid()) {
                $address = $form->getData();
                $this->checkoutService->addAddress($address);
                $form = $this->createForm('user_address', null, array('user' => $this->getUser()));
                $checkoutUser = $this->checkoutService->getCheckoutUser();
                if (!($this->getUser() instanceof User) && $checkoutUser->getIsMobileVerified()) {
                    $this->checkoutService->setDeliveryAddress($address->getUserAddressId());

                    return $this->redirect($this->generateUrl('checkout_payment'));
                }
            }
            $form = $form->createView();

            $selectedAddress = $this->checkoutService->getDeliveryAddress();
            $addresses = $user->getAddressesSortedBy();
            $messages = $this->getUnreadMessages();

            extract($this->getNotificationSettings());

            $data = compact(
                'addresses',
                'cart',
                'form',
                'selectedAddress',
                'messages',
                'token',
                'baseUri',
                'nodePort',
                'user'
            );        

            return $this->render('YilinkerFrontendBundle:Checkout:summary.html.twig', $data);
            
        } catch (YilinkerException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectBack();
        }
    }

    public function addressListAction()
    {
        $user = $this->checkoutService->getCheckoutUser();
        $addresses = $user->getAddressesSortedBy();
        $selectedAddress = $this->checkoutService->getDeliveryAddress();
        $data = compact('addresses', 'selectedAddress');

        return $this->render('YilinkerFrontendBundle:Checkout:address_list.html.twig', $data);
    }

    public function deleteAddressAction($addressId)
    {
        $em = $this->getDoctrine()->getEntityManager();
        $tbUserAddress = $em->getRepository('YilinkerCoreBundle:UserAddress');
        $userAddress = $tbUserAddress->find($addressId);
        if ($userAddress) {
            $em->remove($userAddress);
            $em->flush();
        }

        return $this->redirectBack();
    }

    public function validateVoucherAction(Request $request)
    {
        $code = $request->get('code');
        $em = $this->getDoctrine()->getEntityManager();
        $tbVoucherCode = $em->getRepository('YilinkerCoreBundle:VoucherCode');

        try {
            $data = $this->checkoutService->setVoucherCode($code, true);
            if ($data) {
                $this->jsonResponse['data'] = $data;
                $this->jsonResponse['isSuccessful'] = true;
            }
            else {
                $this->jsonResponse['message'] = $tbVoucherCode->getInactiveMessage($code);
            }
        } catch (YilinkerException $e) {
            $this->jsonResponse['message'] = $e->getMessage();
        }

        $response = $this->jsonResponse();
    
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('no-store', true);           

        return $response;
        
    }

    /**
     * Render Payment Markup
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function paymentAction(Request $request)
    {
        $voucherCode = $request->get('voucherCode', null);

        try {
            if ($request->isMethod('POST') && $request->get('address')) {
                $address = $request->get('address');
                $this->checkoutService->setDeliveryAddress($address);
            }

            $user = $this->checkoutService->getCheckoutUser();
            $address = $this->checkoutService->getDeliveryAddress();
            $consigneeName = '';
            $consigneeContactNumber = '';

            $em = $this->getDoctrine()->getManager();
            
            if ($request->isMethod('POST') && $request->get('paymentType')) {

                if($voucherCode){
                    $voucherCodeRepository = $em->getRepository('YilinkerCoreBundle:VoucherCode');
                    $isValid = $voucherCodeRepository->getActiveVoucherCode($voucherCode);

                    if(!$isValid){
                        throw new YilinkerException("Voucher code is either inactive or invalid.");
                    }
                }

                $paymentType = $request->get('paymentType');
                $url = null;
                if ($paymentType == PaymentMethod::PAYMENT_METHOD_PESOPAY) {
                    $url = $this->checkoutService->paymentPesopay();
                }
                elseif ($paymentType == PaymentMethod::PAYMENT_METHOD_DRAGONPAY) {
                    $url = $this->checkoutService->paymentDragonpay();
                }
                elseif ($paymentType == PaymentMethod::PAYMENT_METHOD_COD) {
                    $userOrder = $this->checkoutService->paymentCOD();
                    return $this->redirectToRoute('checkout_overview', array('Ref' => $userOrder->getOrderId()));
                }

                if ($url) {
                    return $this->redirect($url);
                }
            }
            else {
                $cosignee = $this->checkoutService->catchConsignee(true);
                extract($cosignee);
            }

            $messages = $this->getUnreadMessages();
            $cart = $this->checkoutService->getSelectedOnCart();

            //check if cart can be paid through COD
            $cartService = $this->get('yilinker_front_end.service.cart');
            $canCOD = $cartService->canCOD($cart);
            $hasFlashSaleItem = $this->checkoutService->hasFlashSaleItem();
            
            extract($this->getNotificationSettings());

            $data = compact(
                'user',
                'address',
                'cart',
                'canCOD',
                'messages',
                'token',
                'baseUri',
                'nodePort',
                'consigneeName',
                'consigneeContactNumber',
                'hasFlashSaleItem'
            );

            return $this->render('YilinkerFrontendBundle:Checkout:payment.html.twig', $data);
        } catch (YilinkerException $e) {
            $errormsg = $e->getMessage();
            if ($errormsg == 'There are no items to checkout') {
                return $this->redirectToRoute('checkout_overview');
            }

            $errormsgs = explode('<br>', $errormsg);
            foreach ($errormsgs as $errormsg) {
                if (!$errormsg) {
                    continue;
                }
                $this->addFlash('error', $errormsg);
            }

            return $this->redirectBack();
        }
    }

    /**
    * Render Payment Success Markup
    * @return \Symfony\Component\HttpFoundation\Response
    */
    public function overviewAction(Request $request)
    {
        $error = '';
        $status = '';
        $ref = $request->get('Ref');
        if (!$ref) {
            $ref = $request->get('txnid');
            if ($ref) {
                $status = $request->get('status');
                if (strtoupper($status) == 'F') {
                    $error = urldecode($request->get('message'));
                    $this->checkoutService->paymentFailed($ref);
                }
            }
        }
        $userOrder = null;

        if (strtoupper($status) != 'F') {
            try {
                if (!($this->getUser() instanceof User)) {
                    if ($request->isMethod('POST')) {
                        $this->checkoutService->loadPreviousCheckoutSession();
                    }
                }

                $userOrder = $this->checkoutService->getUserOrder($ref);
                $this->checkoutService->throwError = false;
                $this->checkoutService->clearSelectedOnCart();
                $this->checkoutService->clearSession();
            } catch (YilinkerException $e) {
                $error = $e->getMessage();
            }
        }

        $messages = $this->getUnreadMessages();

        extract($this->getNotificationSettings());
        $continueShoppingURL = $this->checkoutService->continueShoppingURL($userOrder);

        $data = compact(
            'userOrder',
            'error',
            'messages',
            'token',
            'baseUri',
            'nodePort',
            'continueShoppingURL'
        );

        return $this->render('YilinkerFrontendBundle:Checkout:overview.html.twig', $data);
    }


    /**
     * Dragonpay Return Controller Action
     */
    public function returnDragonpayAction(Request $request)
    {
        $getParams = $request->query->all();

        $easyshopConfig = $this->container->getParameter('easyshop');
        $dragonpayConfig = $this->container->getParameter('payment_gateways')['dragonpay'];

        $client = isset($getParams['param1']) ? strtolower(trim($getParams['param1'])) : $dragonpayConfig['clientname'];
        if($client === $dragonpayConfig['clientname']){
            $overviewUrl = $this->get('router')->generate('checkout_overview', $getParams);            
            return $this->redirect($overviewUrl);
        }
        else if($client === $easyshopConfig['clientname']){
            $easyshopReturnUrl = $easyshopConfig['base_url'].'/'.$easyshopConfig['dragonpay_return'];
            return new RedirectResponse($easyshopReturnUrl.'?'.http_build_query($getParams));
        }
    }

    /**
     * Dragonpay Postback Controller Action
     */
    public function postbackDragonpayAction(Request $request)
    {
        if($request->isMethod('POST')){
            $method = FormRequest::METHOD_POST;
            $getParams = $request->request->all();
        }
        else{
            $method = FormRequest::METHOD_GET;
            $getParams = $request->query->all();
        }

        /**
         * Log postback data
         */
        $loggableData = $getParams;
        $loggableData['method']  = $method;
        $em = $this->getDoctrine()->getEntityManager();
        $paymentMethod = $em->getReference('YilinkerCoreBundle:PaymentMethod', PaymentMethod::PAYMENT_METHOD_DRAGONPAY);
        $postbackLog = new PaymentPostbackLog();
        $postbackLog->setDateAdded(new \DateTime('now'));
        $postbackLog->setData(json_encode($loggableData));
        $postbackLog->setPaymentMethod($paymentMethod);
        $em->persist($postbackLog);
        $em->flush();
       
        $easyshopConfig = $this->container->getParameter('easyshop');
        $dragonpayConfig = $this->container->getParameter('payment_gateways')['dragonpay'];
        $client = isset($getParams['param1']) ? strtolower(trim($getParams['param1'])) : $dragonpayConfig['clientname'];
        if($client === $dragonpayConfig['clientname']){
            $this->checkoutService->throwError = false;
            $this->checkoutService->postbackDragonpay();            
        }
        else if($client === $easyshopConfig['clientname']){
            $request = new FormRequest($method, '/'.$easyshopConfig['dragonpay_postback'], $easyshopConfig['base_url']);
            $request->setFields($getParams);
            $response = new BuzzReponse();
            $client = new Curl();
            $client->setTimeout(self::EASYSHOP_TIMEOUT_SEC);
            $client->send($request, $response);            
        }

        return new Response();
    }

    /**
     * Pesopay Postback Controller Action
     */
    public function postbackPesopayAction(Request $request)
    {        
        if($request->isMethod('POST')){
            $method = FormRequest::METHOD_POST;
            $getParams = $request->request->all();
        }
        else{
            $method = FormRequest::METHOD_GET;
            $getParams = $request->query->all();
        }
        
        /**
         * Log postback data
         */
        $loggableData = $getParams;
        $loggableData['method']  = $method;
        $em = $this->getDoctrine()->getEntityManager();
        $paymentMethod = $em->getReference('YilinkerCoreBundle:PaymentMethod', PaymentMethod::PAYMENT_METHOD_PESOPAY);
        $postbackLog = new PaymentPostbackLog();
        $postbackLog->setDateAdded(new \DateTime('now'));
        $postbackLog->setData(json_encode($loggableData));
        $postbackLog->setPaymentMethod($paymentMethod);
        $em->persist($postbackLog);
        $em->flush();

        $easyshopConfig = $this->container->getParameter('easyshop');
        $pesopayConfig = $this->container->getParameter('payment_gateways')['pesopay'];
        $client = isset($getParams['remark']) ? strtolower(trim($getParams['remark'])) : $pesopayConfig['clientname'];
        if($client === $pesopayConfig['clientname']){
            $this->checkoutService->throwError = false;
            $this->checkoutService->postbackPesopay();
        }
        else if($client === $easyshopConfig['clientname']){
            $request = new FormRequest($method, '/'.$easyshopConfig['pesopay_postback'], $easyshopConfig['base_url']);
            $request->setFields($getParams);
            $response = new BuzzReponse();
            $client = new Curl();
            $client->setTimeout(self::EASYSHOP_TIMEOUT_SEC);
            $client->send($request, $response);
        }

        return new Response();
    }

    public function orderSummaryAction(Request $request)
    {
        $session = $request->getSession();
        $voucherCode = $session->get('voucherCode');
        $voucherData = $this->checkoutService->setVoucherCode($voucherCode);
        $cart = $this->checkoutService->getSelectedOnCart();
        //check if cart can be paid through COD
        $cartService = $this->get('yilinker_front_end.service.cart');
        $canCOD = $cartService->canCOD($cart);
        $data = compact('voucherData', 'cart', 'canCOD');

        return $this->render('YilinkerFrontendBundle:Checkout:order_summary.html.twig', $data);
    }

    private function getUnreadMessages()
    {
        $messages = 0;
        $authorizationChecker = $this->get('security.authorization_checker');

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {

            $entityManager = $this->get("doctrine.orm.entity_manager");

            $messages = $entityManager->getRepository('YilinkerCoreBundle:Message')
                                      ->getCountUnonepenedMessagesByUser($this->getAuthenticatedUser());
        }

        return $messages;
    }

    private function getNotificationSettings()
    {
        $authorizationChecker = $this->get('security.authorization_checker');
        $token = null;

        if (
            $authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY') ||
            $authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')
        ) {
            $authenticatedUser = $this->getAuthenticatedUser();
            $jwtService = $this->get("yilinker_core.service.jwt_manager");
            $token = $jwtService->encodeToken(array("userId" => $authenticatedUser->getUserId()));
        }

        $baseUri = $this->getParameter('frontend_hostname');
        $nodePort = $this->getParameter('node_messaging_port');

        return compact('token', 'baseUri', 'nodePort');
    }

    private function getAuthenticatedUser()
    {
        return $this->container->get('security.token_storage')->getToken()->getUser();
    }

    /**
     * Render payment receipt web view
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function webReceiptAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Checkout:receipt_web.html.twig');

        return $response;
    }

    /**
     * Render payment receipt printout
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function printReceiptAction()
    {
        $response = $this->render('YilinkerFrontendBundle:Checkout:receipt_print.html.twig');

        return $response;
    }

    /**
     * check if product quantity is out of stock
     */
    private function checkIfOutOfStock($cart)
    {
        foreach ($cart as $item) {
            if (isset($item['productUnits'][$item['unitId']])) {
                $quantity = $item['productUnits'][$item['unitId']]['quantity'];
                if ($quantity  == 0) {
                    $this->addFlash('error', 'One of the selected item is out of stock');
                }
            }
        }
    }

}
