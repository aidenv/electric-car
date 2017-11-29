<?php

namespace Yilinker\Bundle\FrontendBundle\Oauth\GrantExtension;

use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;
use Yilinker\Bundle\CoreBundle\Traits\ContactNumberHandler;

/**
 * Seller Grant Extension
 */
class BuyerGrantExtension implements GrantExtensionInterface
{
    use ContactNumberHandler;

    const GRANT_URI = 'http://yilinker-online.com/grant/buyer';
    
    /**
     * Password Encoder
     *
     * @var Symfony\Component\Security\Core\Encoder\UserPasswordEncoder
     */
    private $passwordEncoder;

    /**
     * User Repository
     *
     * @var Yilinker\Bundle\CoreBundle\Repository\UserRepository
     */
    private $userRepository;

    /**
     * Country Repository
     *
     * @var Yilinker\Bundle\CoreBundle\Repository\CountryRepository
     */
    private $countryRepository;

    /**
     * Ignore password
     *
     * @var boolean
     */
    private $ignorePassword = false;

    /**
     * Constructor
     *
     * @param Yilinker\Bundle\CoreBundle\Repository\UserRepository $userRepository
     * @param Symfony\Component\Security\Core\Encoder\UserPasswordEncoder $passwordEncoder
     */
    public function __construct(
        EntityRepository $userRepository, 
        EntityRepository $countryRepository, 
        UserPasswordEncoder $passwordEncoder
    ){
        $this->userRepository = $userRepository;
        $this->countryRepository = $countryRepository;
        $this->passwordEncoder = $passwordEncoder;
    }

    /*
     * {@inheritdoc}
     */
    public function checkGrantExtension(IOAuth2Client $client, array $inputData, array $authHeaders)
    {   
        $areaCode = array_key_exists("areaCode", $inputData)? $inputData["areaCode"] : "63";

        $country = $this->countryRepository->findOneByAreaCode($areaCode);

        if(preg_match('/^\d+$/', $inputData['email'])){
            $inputData['email'] = $this->formatContactNumber($country->getCode(), $inputData['email']);
        }

        // Check that the input data is correct
        if (!isset($inputData['email']) || !isset($inputData['password'])) {
            return false;
        }

        $user = $this->userRepository->loadUserByContactOrEmail($inputData['email']);
        
        if($this->ignorePassword === false){
            if($user === null || $this->passwordEncoder->isPasswordValid($user, $inputData['password']) === false){
                $errmessage = filter_var($inputData['email'], FILTER_VALIDATE_EMAIL) ? 'Invalid email' : 'Invalid mobile number';

                throw new OAuth2ServerException(OAuth2::HTTP_BAD_REQUEST, OAuth2::ERROR_INVALID_REQUEST, "$errmessage/password credentials");
            }
        }

        if($user->getUserType() !== User::USER_TYPE_BUYER){
            throw new OAuth2ServerException(OAuth2::HTTP_BAD_REQUEST, OAuth2::ERROR_INVALID_REQUEST, 'User is not a buyer');
        }

        if($user->getIsActive() === false){
            throw new OAuth2ServerException(OAuth2::HTTP_BAD_REQUEST, OAuth2::ERROR_INVALID_REQUEST, 'This account has been disabled');
        }

        return array(
            'data' => $user
        );
    }

    public function setIgnorePassword($ignorePassword)
    {
        $this->ignorePassword = $ignorePassword;
    }
    
}
