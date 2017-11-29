<?php

namespace Yilinker\Bundle\FrontendBundle\Services\SocialMedia;

use Exception;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Yilinker\Bundle\CoreBundle\Entity\OauthProvider;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\UserMerge;
use Yilinker\Bundle\CoreBundle\Services\Yilinker\Account;
use Yilinker\Bundle\CoreBundle\Services\Jwt\JwtManager;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class SocialMediaAuthenticationService
 * @package Yilinker\Bundle\FrontendBundle\Services\SocialMedia
 */
class SocialMediaManager implements OAuthAwareUserProviderInterface
{

    public $user;
    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    private $jwtManager;

    private $ylaService;

    private $container;

    /**
     * @param EntityManager $entityManager
     * @param Account $ylaService
     * @param JwtManager $jwtManager
     */
    public function __construct(EntityManager $entityManager, Account $ylaService, JwtManager $jwtManager, $container)
    {
        $this->em = $entityManager;
        $this->ylaService = $ylaService;
        $this->jwtManager = $jwtManager;
        $this->container = $container;
    }

    /**
     * Authenticate Account.
     * Register Account.
     * returns false if email exists
     * @param UserResponseInterface $response
     * @return bool|null|object|User
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $responseData = $response->getResponse();
        $buyer = false;

        if (isset($responseData['email'])) {
            $responseOauthProvider = 0;

            if ($response->getResourceOwner()->getName() === 'google') {
                $responseOauthProvider = OauthProvider::OAUTH_PROVIDER_GOOGLE;
            }
            else if ($response->getResourceOwner()->getName() === 'facebook') {
                $responseOauthProvider = OauthProvider::OAUTH_PROVIDER_FACEBOOK;
            }

            if ($responseOauthProvider !== 0) {

                $socialMediaId = $responseData['id'];
                $oauthProvider = $this->em->getRepository('YilinkerCoreBundle:OauthProvider')
                                          ->find($responseOauthProvider);
                $isOauthClientRegistered = $this->em->getRepository('YilinkerCoreBundle:UserMerge')
                                                    ->findOneBy(array(
                                                        'socialMediaId' => $socialMediaId,
                                                        'oauthProvider' => $oauthProvider->getOauthProviderId()
                                                    ));

                if ($isOauthClientRegistered instanceof UserMerge) {
                    $buyer = $this->em->getRepository('YilinkerCoreBundle:User')
                                       ->find($isOauthClientRegistered->getUser()->getUserId());
                }
                else {
                    $registeredUser = $this->em->getRepository('YilinkerCoreBundle:User')
                                              ->findOneBy(array(
                                                  'email'    => $responseData['email'],
                                                  'userType' => User::USER_TYPE_BUYER
                                              ));

                    if (!($registeredUser instanceof User)) {

                        $this->em->beginTransaction();
                        try{

                            $lastname = "";
                            $firstname = "";

                            $storeService = $this->container->get("yilinker_core.service.entity.store");
                            $accreditationApplication = $this->container->get("yilinker_core.service.accreditation_application_manager");

                            if(isset($responseData['name'])){
                                $explodedFullname = explode(' ', $responseData['name']);
                                $lastname = array_pop($explodedFullname);
                                $firstname = implode(' ', $explodedFullname);
                            }

                            $plainPassword = 'YILINKER' . '-' . rand(1, 999) . '-' . strtotime(Carbon::now());

                            $buyer = $this->registerAccount($responseData['email'], $firstname, $lastname, $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_BUYER);
                            $affiliate = $this->registerAccount($responseData['email'], $firstname, $lastname, $socialMediaId, $oauthProvider, $plainPassword, User::USER_TYPE_SELLER);

                            $request = $this->jwtManager->setKey("ylo_secret_key")->encodeUser($buyer)->encodeToken(null);
                            $this->ylaService->setEndpoint(false);
                            $mailer = $this->container->get('yilinker_core.service.user.mailer');
                            $mailer->sendAutoGeneratedPassword ($buyer, $plainPassword);

                            $response = $this->ylaService->sendRequest("user_create", "post", array("request" => $request));

                            $buyer->setAccountId($response["data"]["userId"]);
                            $affiliate->setAccountId($response["data"]["userId"]);

                            $store = $storeService->createStore($affiliate, Store::STORE_TYPE_RESELLER);
                            $accreditationApplication->createApplication($affiliate, '', Store::STORE_TYPE_RESELLER, true);

                            $store->setStoreNumber($storeService->generateStoreNumber($store));

                            $this->em->flush();
                            $this->em->commit();
                        }
                        catch(Exception $e){
                            $this->em->rollback();
                            $buyer = false;
                        }
                    }
                    else {
                        $session = new Session();
                        $session->set('userId', $registeredUser->getUserId());
                        $session->set('userEmail', $responseData['email']);
                        $session->set('socialMediaId', $socialMediaId);
                        $session->set('oauthProviderId', $oauthProvider->getOauthProviderId());
                        $session->save();
                    }

                }

            }

        }
        else {
            throw new UsernameNotFoundException();
        }

        return $buyer;
    }

    /**
     * Register Account
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $socialMediaId
     * @param OauthProvider $oauthProvider
     * @param string $password
     * @return User
     */
    public function registerAccount(
        $email, 
        $firstname, 
        $lastname, 
        $socialMediaId, 
        OauthProvider $oauthProvider, 
        $password,
        $userType = User::USER_TYPE_BUYER,
        $ylaPersist = true
    ){
        $user = new User();
        $user->setDateAdded(Carbon::now());
        $user->setDateLastModified(Carbon::now());
        $user->setFirstName($firstname);
        $user->setLastName($lastname);
        $user->setPlainPassword($password);
        $user->setGender('M');
        $user->setEmail($email);
        $user->setIsActive(true);
        $user->setIsMobileVerified(false);
        $user->setIsEmailVerified(true);
        $user->setIsBanned(false);
        $user->setUserType($userType);
        $user->setIsSocialMedia(true);

        $this->em->persist($user);

        $slug = $this->generateUniqueSlug($user);

        $user->setSlug($slug);

        if($ylaPersist){
            $jwtService = $this->container->get("yilinker_core.service.jwt_manager");
            $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

            $ylaService = $this->container->get("yilinker_core.service.yla_service");
            $ylaService->setEndpoint(false);

            $response = $ylaService->sendRequest("user_create", "post", array("request" => $request));

            $user->setAccountId($response["data"]["userId"]);
        }

        if($userType == User::USER_TYPE_BUYER){
            $this->mergeAccount($user, $socialMediaId, $oauthProvider);
        }

        $this->em->flush();

        $this->container->get('yilinker_core.service.account_manager')
                        ->generateReferralCode($user);

        return $user;
    }

    /**
     * Merge account
     * @param $user
     * @param $oAuthId
     * @param $oAuthProvider
     * @return User
     */
    public function mergeAccount(User $user, $oAuthId, $oAuthProvider)
    {
        $isOauthClientRegistered = $this->em->getRepository('YilinkerCoreBundle:UserMerge')
                                            ->findBy(array(
                                                'user' => $user->getUserId(),
                                                'socialMediaId' => $oAuthId,
                                                'oauthProvider' => $oAuthProvider
                                            ));

        if (!$isOauthClientRegistered) {
            $socialAccount = new UserMerge();
            $socialAccount->setUser($user);
            $socialAccount->setSocialMediaId($oAuthId);
            $socialAccount->setOauthProvider($oAuthProvider);
            $socialAccount->setDateCreated(Carbon::now());
            $this->em->persist($socialAccount);
            $this->em->flush();

            $user->addSocialMediaAccount($socialAccount);
        }

        return $user;
    }

    public function generateUniqueSlug(User $user)
    {
        $slug = substr(sha1($user->getUserId().uniqid('yilinker').time()), 0, 20);

        $userRepository = $this->em->getRepository("YilinkerCoreBundle:User");
        while(!$userRepository->isUniqueSlug($slug, null)){
            $slug = $this->generateUniqueSlug($user);
        }

        return $slug;
    }
}
