<?php
namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Carbon\Carbon;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\UserOccupation;
use Yilinker\Bundle\CoreBundle\Entity\BankAccount;
use Yilinker\Bundle\CoreBundle\Entity\UserAddress;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\StoreLevel;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationLevel;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplicationStatus;
use Yilinker\Bundle\CoreBundle\Entity\AccreditationApplication;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Console command to upload offline affiliate command
 *
 * Note: this does not validate the password
 */
class UploadOfflineAffiliatesCommand extends ContainerAwareCommand
{
    const CSV_MAP_KEY = 0;
    const CSV_MAP_PASSWORD = 1;
    const CSV_MAP_EMAIL = 2;
    const CSV_MAP_MOBILE = 3;    
    const CSV_MAP_FIRSTNAME = 4;    
    const CSV_MAP_LASTNAME = 5;    
    const CSV_MAP_TIN = 6;
    const CSV_MAP_STORENAME = 7;
    const CSV_MAP_STORELINK = 8;
    const CSV_MAP_BANKID = 9;
    const CSV_MAP_BANKACCOUNTNAME = 10;
    const CSV_MAP_BANKACCOUNTNUMBER = 11;
    const CSV_MAP_DOCUMENTTYPEID = 12;
    const CSV_MAP_DOCUMENTFILELOCATION = 13;
    const CSV_MAP_SCHOOLNAME = 14;
    const CSV_MAP_SCHOOLLEVEL = 15;

    const DEFAULT_BANK_NAME = "China Banking Corporation";
    const DEFAULT_LOCATION_ID = 45632;                            
    
    protected function configure()
    {
        $this
            ->setName('yilinker:offline:upload-affiliates')
            ->setDescription('Upload offline affiliates')
            ->addOption(
                 'accreditOnly',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Accredit only existing email addresses'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = fopen("user-list.csv","r");
        
        $dateNow = new \DateTime('now');
        $container = $this->getContainer();

        $log = new Logger('offline-affiliate');
        $kernelDir = $container->getParameter("kernel.root_dir");
        $log->pushHandler(new StreamHandler($kernelDir.DIRECTORY_SEPARATOR.'affiliate-offline.log', Logger::EMERGENCY));
        $isAccreditOnly = $input->getOption('accreditOnly') === 'true' ? true : false;        
        
        $router = $container->get("router");
        $entityManager = $container->get('doctrine')->getManager();
        $routeCollection = $router->getRouteCollection()->all();        
        $registeredRoutes = array();
        foreach ($routeCollection as $route) {
            array_push($registeredRoutes, $route->getPath());
        }

        $result = array(
            'successful' => 0,
            'failed'     => 0,
        );

        while(! feof($file))
        {
            $array = fgetcsv($file);
            if(strlen($array[self::CSV_MAP_PASSWORD]) > 0 && strlen($array[self::CSV_MAP_EMAIL]) > 0){
                
                if($isAccreditOnly === false){
                    $response = $this->createNewUser(
                        $array[self::CSV_MAP_FIRSTNAME],
                        $array[self::CSV_MAP_LASTNAME],
                        $array[self::CSV_MAP_PASSWORD],
                        $array[self::CSV_MAP_EMAIL],
                        $array[self::CSV_MAP_MOBILE]
                    );
                }
                else{
                    $response['data']['user'] = $entityManager->getRepository('YilinkerCoreBundle:User')
                                                      ->findOneBy(array(
                                                          'email'    => $array[self::CSV_MAP_EMAIL],
                                                          'userType' => User::USER_TYPE_SELLER,
                                                      ));
                    
                    $response['isSuccessful'] = false;
                    $response['data']['store'] = $response['data']['user'] ? $response['data']['user']->getStore() : null;
                    if($response['data']['store'] && $response['data']['store']->getAccreditationLevel() !== null){
                        $response['data']['errors'] = "Affiliate is already accredited";
                    }
                    else{
                        $response['isSuccessful'] = $response['data']['store'] && (int) $response['data']['store']->getStoreType() === Store::STORE_TYPE_RESELLER;
                        $response['data']['errors'] = $response['isSuccessful'] ? "" : "Affiliate store not found";                        
                    }
                }

                if(isset($response['isSuccessful']) && $response['isSuccessful']){
                    $user = $response['data']['user'];
                    $store = $response['data']['store'];

                    if($user instanceof User && $store instanceof Store){

                        if($isAccreditOnly === false){
                            $user->setTin($array[self::CSV_MAP_TIN]);
                            $store->setStoreName($array[self::CSV_MAP_STORENAME]);
                            $storeSlug = $array[self::CSV_MAP_STORELINK];
                            $duplicateSlug = $entityManager->getRepository('YilinkerCoreBundle:Store')
                                           ->getStoreByStoreSlug($storeSlug, $user);
                            $isInvalidSlug = !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $storeSlug, $matches) ||
                                           in_array(DIRECTORY_SEPARATOR.$storeSlug, $registeredRoutes);

                            if(count($duplicateSlug) === 0 && $isInvalidSlug === false){
                                $store->setStoreSlug($storeSlug);
                            }
                            else{
                                $store->setStoreSlug($store->getStoreNumber());
                            }
                    
                            $occupation = new UserOccupation;
                            $occupation->setUser($user);
                            $occupation->setName($array[self::CSV_MAP_SCHOOLNAME]);
                            $occupation->setJob($array[self::CSV_MAP_SCHOOLNAME]);
                            $occupation->setDateAdded($dateNow);
                            $entityManager->persist($occupation);
                        }
                    
                        $bank = $entityManager->getRepository('YilinkerCoreBundle:Bank')
                              ->find($array[self::CSV_MAP_BANKID]);
                        if($bank){
                            $bankAccount = new BankAccount();
                            $bankAccount->setBank($bank);
                            $bankAccount->setAccountTitle("Main Bank Account");
                            $bankAccount->setAccountName($array[self::CSV_MAP_BANKACCOUNTNAME]);
                            $bankAccount->setAccountNumber($array[self::CSV_MAP_BANKACCOUNTNUMBER]);
                            $bankAccount->setUser($user);
                            $bankAccount->setIsDefault(true);
                            $entityManager->persist($bankAccount);
                        }
                        else{
                            /**
                             * Create default bank
                             */
                            $bank = $entityManager->getRepository('YilinkerCoreBundle:Bank')
                                  ->findOneBy(array("bankName" => self::DEFAULT_BANK_NAME));
                            if($bank){
                                $bankAccount = new BankAccount();
                                $bankAccount->setBank($bank);
                                $bankAccount->setAccountTitle("Default Bank Account");
                                $bankAccount->setAccountName("Yilinker Online - Affiliate");
                                $bankAccount->setAccountNumber("YLO-A");
                                $bankAccount->setUser($user);
                                $bankAccount->setIsDefault(true);
                                $entityManager->persist($bankAccount);
                            }
                        }
                        
                        /**
                         * Create default address
                         */
                        $location = $entityManager->getRepository('YilinkerCoreBundle:Location')
                                  ->findOneBy(array(
                                      "locationId" => self::DEFAULT_LOCATION_ID,
                                  ));
                        if($location){
                            $address = new UserAddress();
                            $address->setUser($user);
                            $address->setBuildingName("Five E-com Center");
                            $address->setStreetName("Palm Coast Ave");
                            $address->setZipCode("1300");
                            $address->setDateAdded($dateNow);
                            $address->setLocation($location);
                            $address->setTitle("Yilinker Office - PH");
                            $address->setIsDefault(true);
                            $entityManager->persist($address);
                        }

                        /**
                         * Create Accreditation Application
                         */
                        $accreditationStatus = $entityManager->getRepository('YilinkerCoreBundle:AccreditationApplicationStatus')
                                             ->findOneBy(array(
                                                 "accreditationApplicationStatusId" => AccreditationApplicationStatus::STATUS_CLOSE,
                                             ));
                        $accreditationLevel = $entityManager->getRepository('YilinkerCoreBundle:AccreditationLevel')
                                                            ->findOneBy(array(
                                                                "accreditationLevelId" => AccreditationLevel::TYPE_LEVEL_ONE,
                                                            ));
                        $accreditationApplication = new AccreditationApplication();
                        $accreditationApplication->setUser($user);
                        $accreditationApplication->setAccreditationApplicationStatus($accreditationStatus);
                        $accreditationApplication->setSellerType(Store::STORE_TYPE_RESELLER);
                        $accreditationApplication->setDateAdded($dateNow);
                        $accreditationApplication->setLastModifiedDate($dateNow);
                        $accreditationApplication->setBusinessWebsiteUrl("https://www.yilinker.com");
                        $accreditationApplication->setAccreditationLevel($accreditationLevel);
                        $accreditationApplication->setIsBusinessApproved(true);
                        $accreditationApplication->setIsBankApproved(true);
                        $accreditationApplication->setIsBusinessEditable(false);
                        $accreditationApplication->setIsBankEditable(false);
                        $entityManager->persist($accreditationApplication);
                        
                        $store->setAccreditationLevel($accreditationLevel);
                        $store->setStoreType(Store::STORE_TYPE_RESELLER);
                        
                        $log->addEmergency(json_encode(array(
                            "email"   => $array[self::CSV_MAP_EMAIL],
                            "message" => $isAccreditOnly ? "Acceditation successful" : "Registration successful",
                        )));
                                        
                        $entityManager->flush();                        
                        $result['successful'] = $result['successful'] + 1;
                        $output->writeln("User successfully ". ($isAccreditOnly ? "accredited" : "registered") .": ". $array[self::CSV_MAP_EMAIL]);
                    }
                    else{
                        $log->addEmergency(json_encode(array(
                            "email"   => $array[self::CSV_MAP_EMAIL],
                            "message" => "Affiliate not found",
                        )));

                        $output->writeln("Affiliate not found: ". $array[self::CSV_MAP_EMAIL]);
                    }
                }
                else{
                    $log->addEmergency(json_encode(array(
                        "email"   => $array[self::CSV_MAP_EMAIL],
                        "message" => $response['data']['errors'],
                    )));
                    $result['failed'] = $result['failed'] + 1;
                    $output->writeln("User registration for ".$array[self::CSV_MAP_EMAIL]." failed:".json_encode($response['data']['errors']));
                }
            }
        }

        fclose($file);
        $output->writeln(json_encode($result));
    }

    /**
     * Submits form
     *
     * @param $formType
     * @param $entity
     * @param $request
     * @return \Symfony\Component\Form\Form
     * @internal param $postData
     */
    private function transactForm($formType, $entity, $request, $options = array())
    {
        $container = $this->getContainer();
        $formFactory = $container->get('form.factory');
        $form = $formFactory->create($formType, $entity, $options);
        $form->submit($request);

        return $form;
    }
    
    private function createNewUser($firstName, $lastName, $password, $email, $contactNumber, $storeType = Store::STORE_TYPE_RESELLER)
    {
        $container = $this->getContainer();
        $formErrorService = $container->get('yilinker_core.service.form.form_error');

        $formData = array(
            'firstName'     => $firstName,
            'lastName'      => $lastName,
            "plainPassword" => array(
                "first"  => $password,
                "second" => $password,
            ),
            'email' => $email,
            'contactNumber' => $contactNumber,           
        );

        
        $form = $this->transactForm('core_v1_user_add', new User(), $formData, array(
            'csrf_protection'  => false,
            'validatePassword' => false,
            'userType'         => User::USER_TYPE_SELLER,
        ));

        if ($form->isValid()) {
            $entityManager = $container->get('doctrine')->getManager();
            $entityManager->beginTransaction();

            try{
                $accountManager = $container->get('yilinker_core.service.account_manager');
                $user = $form->getData();
                
                $user->setUserType(User::USER_TYPE_SELLER);
                $user = $accountManager->registerUser($user, false);
                
                $storeService = $container->get('yilinker_core.service.entity.store');
                $store = $storeService->createStore($user, $storeType);

                $jwtService = $container->get("yilinker_core.service.jwt_manager");
                $request = $jwtService->setKey("ylo_secret_key")->encodeUser($user)->encodeToken(null);

                $ylaService = $container->get("yilinker_core.service.yla_service");
                $ylaService->setEndpoint(false);

                $ylaResponse = $ylaService->sendRequest("user_create", "post", array("request" => $request));

                if(!is_array($ylaResponse) || !array_key_exists("isSuccessful", $ylaResponse) || !$ylaResponse["isSuccessful"]){
                    $response['data']['errors'] = "An error occured. Please try again later.";
                    
                    return $response;
                }

                $store->setStoreNumber($storeService->generateStoreNumber($store));
                $user->setAccountId($ylaResponse["data"]["userId"]);
                $entityManager->flush();
                
                $authService = $container->get('yilinker_core.security.authentication');

                if($storeType == Store::STORE_TYPE_MERCHANT){
                    $authService->authenticateUser($user, 'seller', array('ROLE_UNACCREDITED_MERCHANT'));
                }
                elseif($storeType == Store::STORE_TYPE_RESELLER){
                    $authService->authenticateUser($user, 'affiliate', array('ROLE_UNACCREDITED_MERCHANT'));
                }

                $response['message'] = 'Registration successful';
                $response['isSuccessful'] = true;
                $response['data'] = array(
                    'user'  => $user,
                    'store' => $store,
                );

                $entityManager->commit();
            }
            catch(Exception $e){
                $entityManager->rollback();
                $response['data']['errors'] = $e->getMessage();
            }
        }
        else{
            $errors = $formErrorService->throwInvalidFields($form);
            $response['data']['errors'] = $errors;
        }

        return $response;
    }

    
}