<?php

namespace Yilinker\Bundle\CoreBundle\Services\Mailer;

use Swift_Image;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserVerificationToken;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Mailer
{
    /**
     * @var Container
     */
    private $container;
    /**
     * @var TwigEngine
     */
    private $twig;
    /**
     * @var AssetsHelper
     */
    private $assetsHelper;
    /**
     * @var Swift_Message
     */
    private $message;

    public function __construct($container, TwigEngine $twig, AssetsHelper $assetsHelper, $mailerEmail)
    {
        $this->container = $container;
        $this->twig = $twig;
        $this->assetsHelper = $assetsHelper;
        $this->message = Swift_Message::newInstance();
        $this->message->setFrom($mailerEmail);
    }
    /**
     * Sends email verification to the user
     * @param User $user
     */
    public function sendEmailVerification(User $user)
    {
        $message = $this->message;
        $rightImage = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('image17.jpg', 'email')));
        extract($this->getSocialMediaImages($message));
        $twigParams = array(
            "token"      => $user->getVerificationToken(UserVerificationToken::TYPE_EMAIL),
            "user"       => $user,
            "rightImage" => $rightImage,
            "logoMain"   => $logoMain,
            "facebook"   => $facebook,
            "twitter"    => $twitter,
            "googlePlus" => $googlePlus,
            "rss"        => $rss,
            "vimeo"      => $vimeo,
            "pinterest"  => $pinterest,
            "linkedIn"   => $linkedIn,
            "instagram"  => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:verify_account.html.twig', $twigParams);
        $message->setSubject("Yilinker Account Confirmation")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');

                
        return $this->container->get("swiftmailer.mailer.verification")->send($message);
    }
    /**
     * Sends email verification success to the user
     * @param User $user
     */
    public function sendSuccessVerification(User $user)
    {
        $message = $this->message;
        $mascot = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('mascot.png', 'email')));
        extract($this->getSocialMediaImages($message));
        $twigParams = array(
            "user" => $user,
            "mascot" => $mascot,
            "logoMain" => $logoMain,
            "facebook" => $facebook,
            "twitter" => $twitter,
            "googlePlus" => $googlePlus,
            "rss" => $rss,
            "vimeo" => $vimeo,
            "pinterest" => $pinterest,
            "linkedIn" => $linkedIn,
            "instagram" => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:success_verification.html.twig', $twigParams);
        $message->setSubject("Yilinker Account Verification Success")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');

        return $this->container->get("swiftmailer.mailer.default")->send($message);
    }
    /**
     * Sends email forgot password link
     * @param User $user
     */
    public function sendForgotPassword(User $user)
    {
        $message = $this->message;
        extract($this->getSocialMediaImages($message));
        $twigParams = array(
            "token" => $user->getForgotPasswordToken(),
            "user" => $user,
            "logoMain" => $logoMain,
            "facebook" => $facebook,
            "twitter" => $twitter,
            "googlePlus" => $googlePlus,
            "rss" => $rss,
            "vimeo" => $vimeo,
            "pinterest" => $pinterest,
            "linkedIn" => $linkedIn,
            "instagram" => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:forgot_password.html.twig', $twigParams);
        $message->setSubject("Yilinker Reset Password.")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');
        return $this->container->get("swiftmailer.mailer.account")->send($message);
    }
    /**
     * Sends Merge Account Verification
     * @param $url
     * @param $email
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function sendMergeAccountVerification($url, $email)
    {
        $message = $this->message;
        extract($this->getSocialMediaImages($message));
        $twigParams = array(
            "url" => $url,
            "email" => $email,
            "logoMain" => $logoMain,
            "facebook" => $facebook,
            "twitter" => $twitter,
            "googlePlus" => $googlePlus,
            "rss" => $rss,
            "vimeo" => $vimeo,
            "pinterest" => $pinterest,
            "linkedIn" => $linkedIn,
            "instagram" => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:verify_merge_account.html.twig', $twigParams);
        $message->setSubject("Yilinker Merge Account.")
                ->setTo($email)
                ->setBody($mailTemplate, 'text/html');
        return $this->container->get("swiftmailer.mailer.account")->send($message);
    }

    /**
     * Send Plain auto generated password
     *
     * @param User $user
     * @param $plainPassword
     * @return mixed
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function sendAutoGeneratedPassword (User $user, $plainPassword)
    {
        $message = $this->message;
        extract($this->getSocialMediaImages($message));
        $twigParams = array (
            "user"          => $user,
            "plainPassword" => $plainPassword,
            "logoMain"      => $logoMain,
            "facebook"      => $facebook,
            "twitter"       => $twitter,
            "googlePlus"    => $googlePlus,
            "rss"           => $rss,
            "vimeo"         => $vimeo,
            "pinterest"     => $pinterest,
            "linkedIn"      => $linkedIn,
            "instagram"     => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:auto_generated_password.html.twig', $twigParams);
        $message->setSubject("Yilinker Auto Generated Password.")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');

        return $this->container->get("swiftmailer.mailer.account")->send($message);
    }

    /**
     * Send accreditation application notification
     *
     * @param User $user
     * @param bool $isApproved
     * @param array $merchantUrls
     * @return mixed
     * @throws \Exception
     * @throws \Twig_Error
     */
    public function sendEmailNotification (User $user, $merchantUrls = array(), $isApproved = true)
    {
        $message = $this->message;
        extract($this->getSocialMediaImages($message));
        $twigParams = array (
            "user"           => $user,
            "merchantUrls"   => $merchantUrls,
            "logoMain"       => $logoMain,
            "facebook"       => $facebook,
            "twitter"        => $twitter,
            "googlePlus"     => $googlePlus,
            "rss"            => $rss,
            "vimeo"          => $vimeo,
            "pinterest"      => $pinterest,
            "linkedIn"       => $linkedIn,
            "instagram"      => $instagram
        );

        if ($isApproved) {
            $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:verified_accreditation.html.twig', $twigParams);
        }
        else {
            $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:rejected_accreditation.html.twig', $twigParams);
        }

        $message->setSubject("Yilinker Accreditation Notification")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');

        return $this->container->get("swiftmailer.mailer.account")->send($message);
    }

    public function shareViaEmail($recipients, $sellerMessage, $baseUri, $store)
    {
        $message = $this->message;
        $qrCodeLocation = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl($store->getQrCodeLocation(), 'qr_code')));

        extract($this->getSocialMediaImages($message));


        $twigParams = array(
            "message" => $sellerMessage,
            "baseUri" => $baseUri,
            "store" => $store,
            "qrCodeLocation" => $qrCodeLocation,
            "logoMain" => $logoMain,
            "facebook" => $facebook,
            "twitter" => $twitter,
            "googlePlus" => $googlePlus,
            "rss" => $rss,
            "vimeo" => $vimeo,
            "pinterest" => $pinterest,
            "linkedIn" => $linkedIn,
            "instagram" => $instagram
        );

        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:share-email-qr-code.html.twig', $twigParams);

        $message->setSubject($store->getStoreName()." 's Store.")
                ->setBcc($recipients)
                ->setBody($mailTemplate, 'text/html');
        $this->container->get("swiftmailer.mailer.default")->send($message);
    }
    /**
     * Send email deactivation notice
     *
     * @param Yilinker\Bundle\CoreBundle\Entity\User
     */
    public function sendDeactivationNotice(User $user)
    {
        $message = $this->message;
        extract($this->getSocialMediaImages($message));
        $rightImage = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('image17.jpg', 'email')));
        $twigParams = array(
            "reactivationToken" => $user->getReactivationCode(),
            "user" => $user,
            "rightImage" => $rightImage,
            "logoMain" => $logoMain,
            "facebook" => $facebook,
            "twitter" => $twitter,
            "googlePlus" => $googlePlus,
            "rss" => $rss,
            "vimeo" => $vimeo,
            "pinterest" => $pinterest,
            "linkedIn" => $linkedIn,
            "instagram" => $instagram
        );
        $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:account-deactivation.html.twig', $twigParams);
        $message->setSubject("Yilinker Account Deactivation")
                ->setTo($user->getEmail())
                ->setBody($mailTemplate, 'text/html');
        return $this->container->get("swiftmailer.mailer.account")->send($message);
    }

    public function sendEmailVoucher($disputeDetails, $voucher)
    {
        $voucherCode = $voucher->getVoucherCodes()->first();
        $dispute = reset($disputeDetails)->getDispute();
        $disputer = $dispute->getDisputer();

        if ($disputer->getIsEmailVerified() && $disputer->getEmail()) {

            $mailer = $this->container->get('swiftmailer.mailer.transaction');
            $email = $disputer->getEmail();

            $msgProducts = '';
            foreach ($disputeDetails as $disputeDetail) {
                $orderProduct = $disputeDetail->getOrderProduct();
                $msgProducts .= '    x'.$orderProduct->getQuantity().' '.$orderProduct->getProductName().'</br>';
            }
            $loginUrl = $this->container->getParameter('frontend_hostname').'/login';

            $message = $this->message;
            extract($this->getSocialMediaImages($message));
            $rightImage = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('image17.jpg', 'email')));
            $mailTemplate = $this->twig->render('YilinkerCoreBundle:Email:voucher_return.html.twig', array(
                "dispute" => $dispute,
                'voucherCode' => $voucherCode,
                'disputeDetails' => $disputeDetails,
                'voucherAmount' => $voucher->getValue(),
                "user" => $disputer,
                "rightImage" => $rightImage,
                "logoMain" => $logoMain,
                "facebook" => $facebook,
                "twitter" => $twitter,
                "googlePlus" => $googlePlus,
                "rss" => $rss,
                "vimeo" => $vimeo,
                "pinterest" => $pinterest,
                "linkedIn" => $linkedIn,
                "instagram" => $instagram
            ));

            $message->setSubject('Yilinker Dispute Case ID: '.$dispute->getTicket())
                    ->setTo($email)
                    ->setBody($mailTemplate, 'text/html');
            return $this->container->get("swiftmailer.mailer.transaction")->send($message);
        }

        return false;
    }

    private function getSocialMediaImages(&$message)
    {
        $logoMain = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('logo-main.png', 'email')));
        $facebook = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-facebook-color.png', 'email')));
        $twitter = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-twitter-color.png', 'email')));
        $googlePlus = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-googleplus-color.png', 'email')));
        $rss = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-rss-color.png', 'email')));
        $vimeo = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-vimeo-color.png', 'email')));
        $pinterest = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-pinterest-color.png', 'email')));
        $linkedIn = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-linkedIn-color.png', 'email')));
        $instagram = $message->embed(Swift_Image::fromPath($this->assetsHelper->getUrl('icon-instagram-color.png', 'email')));
        return compact('logoMain', 'facebook', 'twitter', 'googlePlus', 'rss', 'vimeo', 'pinterest', 'linkedIn', 'instagram');
    }


    public function sendError($exception)
    {
        if ($exception instanceof NotFoundHttpException) {
            return;
        }

        $re = Request::createFromGlobals();

        $content = sprintf("%s","<pre> 
            <style>
                .exception-red {
                    color: rgb(204, 0, 0);
                }
            </style>
            <ul>");

        foreach ($re->server->all() as $key => $server) {
            $content .= sprintf("<li> %s => <span class='exception-red'> %s </span> </li>", $key, $server);
        }

        $content .= sprintf("<li> %s => <span class='exception-red'> %s </span></li>", 'post_params' , json_encode($re->request->all()));
        $content .= sprintf("<li> %s => <span class='exception-red'> %s </span></li>", 'get_params' , json_encode($re->query->all()));
        $content .= sprintf("<li> %s => <span class='exception-red'> %s </span></li>", 'FILE' , $exception->getFile());
        $content .= sprintf("<li> %s => <span class='exception-red'> %s </span></li>", 'MESSAGE' , $exception->getMessage());
        $content .= sprintf("<li> %s => <span class='exception-red'> %s </span></li>", 'LINE' , $exception->getLine());

        $content .= sprintf("%s", "</ul> </pre>");

        $mailer = $this->container->get('mailer');
        $mailerEmail = $this->container->getParameter('mailer_user');
        $to = $this->container->getParameter('reports_dev_email');
        $message = Swift_Message::newInstance();
       
        $message->setSubject("ERROR LOG: (" . date('Y-m-d') . ") :". $exception->getMessage() )
                ->setFrom($mailerEmail)
                ->setBody($content, 'text/html')
                ->setTo($to);    

        $mailer->send($message);
        
    }
}
