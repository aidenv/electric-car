<?php

namespace Yilinker\Bundle\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\FrontendBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Entity\EmailNewsletterSubscription;
use Symfony\Component\HttpFoundation\JsonResponse;

class NewsletterController extends YilinkerBaseController
{

    public function subscriptionSuccessAction(Request $request)
    {
        if (!(array_key_exists('HTTP_REFERER', $_SERVER) && $_SERVER['HTTP_REFERER'])) {
            throw $this->createNotFoundException();
        }

        return $this->render('YilinkerFrontendBundle:Home:subscription_success.html.twig');
    }

    /**
     * Subscribe email to the newsletter
     *
     * @param Request $request
     */
    public function subscribeEmailNewsletterAction(Request $request)
    {   
        $response = array(
            'isSuccessful' => false,
            'message'      => 'Subscribing by email is currently unavailable',
        );
        $token = $request->get('_token', null);
        $form = $this->createForm('email_newsletter', null, array('csrf_protection' => false));
        $data = array('email' => $request->get('email'));
        
        $form->submit($data);

        if($form->isValid()){
            $formData = $form->getData();

            $dateNow = new \DateTime('now');
            $emailsNewsletterSubscription = new EmailNewsletterSubscription();
            $emailsNewsletterSubscription->setIsActive(true);
            $emailsNewsletterSubscription->setDateCreated($dateNow);
            $emailsNewsletterSubscription->setDateLastModified($dateNow);
            $emailsNewsletterSubscription->setEmail($formData['email']);
            
            $entityManager = $this->get('doctrine')->getManager();
            $entityManager->persist($emailsNewsletterSubscription);

            $entityManager->flush();
            $response['message'] = "Email successfully subscribed";
            $response['isSuccessful'] = true;
        }
        else{
            $response['message'] = $form->getErrors(true)[0]->getMessage();
        }

        return new JsonResponse($response);
    }

}
