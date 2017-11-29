<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class JoinAffiliateBuyerCommand extends ContainerAwareCommand
{
    const USERS_PER_ITERATION = 100;
    
    protected function configure()
    {
        $this
            ->setName('yilinker:fix:join-affiliate-buyer')
            ->setDescription('Join affiliates and buyer by contact number')
        ;
    }
 
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();        
        $em = $container->get('doctrine')->getManager();
        $jwtService = $container->get('yilinker_core.service.jwt_manager');
        $ylaService = $container->get('yilinker_core.service.yla_service');
        $accountManager = $container->get('yilinker_core.service.account_manager');
        $storeService = $container->get('yilinker_core.service.entity.store');

        $syncedData = array();
        
        /**
         * Sync accounts with the same contact number
         */
        $limit = self::USERS_PER_ITERATION;
        $offset = 0;
        $userCount = 0;

        do{
            $groupedUsers = $em->getRepository('YilinkerCoreBundle:User')
                               ->getAffiliateBuyerGroupedByContactNumber(1, $offset, $limit);
            $userCount = count($groupedUsers);
            $offset += $userCount; 
            
            foreach($groupedUsers as $contactNumber => $group){
                /**
                 * Sanity check to ensure that the accounts are of type BUYER and AFFILIATE and are unsynced
                 */
                $expr = Criteria::expr();
                $criteria = Criteria::create()->where($expr->eq('userType', User::USER_TYPE_BUYER));
                $buyer = $group->matching($criteria)->first();            
                $criteria = Criteria::create()->where($expr->eq('userType', User::USER_TYPE_SELLER));
                $affiliate = $group->matching($criteria)->first();           
                if(!($buyer && $affiliate && (int) $affiliate->getStore()->getStoreType() === Store::STORE_TYPE_RESELLER) ||
                   ($buyer->getAccountId() === $affiliate->getAccountId() || strlen(trim($contactNumber)) === 0)
                ){
                    continue;
                }

                $criteria = Criteria::create()->orderBy(array('isMobileVerified' => Criteria::DESC));
                $orderedGroup = $group->matching($criteria);
                $priorityUserByMobile = $group->first();

                $criteria = Criteria::create()->orderBy(array('isEmailVerified' => Criteria::DESC));
                $orderedGroup = $group->matching($criteria);
                $priorityUserByEmail = $group->first();

                if($priorityUserByMobile && $priorityUserByEmail && $priorityUserByMobile->getContactNumber()  && $priorityUserByEmail->getEmail()){            
                    foreach($group as $user){
                        $user->setEmail($priorityUserByEmail->getEmail());
                        $user->setIsEmailVerified($priorityUserByEmail->getIsEmailVerified());
                        $user->setIsMobileVerified($priorityUserByMobile->getIsMobileVerified());
                        $user->setIsActive($priorityUserByMobile->getIsActive());
                        $user->setAccountId($priorityUserByMobile->getAccountId());
                        $user->setFirstName($priorityUserByMobile->getFirstName());
                        $user->setLastName($priorityUserByMobile->getLastName());
                        $em->flush();
                    }

                    $request = $jwtService->setKey("ylo_secret_key")
                             ->encodeUser($priorityUserByMobile)
                             ->encodeToken(null);
                    $ylaService->setEndpoint(false);            
                    $response = $ylaService->sendRequest("user_update", "post", array("request" => $request));
                    if(!is_array($response) || !array_key_exists("isSuccessful", $response) || !$response["isSuccessful"]){
                        $output->writeln("Account syncing unsuccessful for contact number:".$priorityUserByMobile->getContactNumber());
                    }
                    else{
                        $output->writeln("Accounts synced for contact number:".$priorityUserByMobile->getContactNumber());
                        $syncedData[] = array(
                            'email'            => $user->getEmail(),
                            'mobileNumber'     => $user->getContactNumber(),
                            'isEmailVerified'  => $user->getIsEmailVerified(),
                            'isMobileVerified' => $user->getIsMobileVerified(),
                        );
                    }
                }
            }
        }
        while($userCount > 0);

        $offset = 0;
        $userCount = 0;
        do{
            $usersWithNoPair = $em->getRepository('YilinkerCoreBundle:User')
                                  ->getAffiliateBuyerWithNoPair($offset, $limit);
            $userCount = count($usersWithNoPair);
            $offset += $userCount;
            
            $usersWithNoPair = new ArrayCollection($usersWithNoPair);
            $criteria = Criteria::create()
                      ->andWhere(Criteria::expr()->eq("userType", User::USER_TYPE_BUYER));
            $uniqueBuyers = $usersWithNoPair->matching($criteria);
            $criteria = Criteria::create()
                      ->andWhere(Criteria::expr()->eq("userType", User::USER_TYPE_SELLER));
            $uniqueAffiliates = $usersWithNoPair->matching($criteria);
            foreach($uniqueBuyers as $buyer){
                $newUser = new User();
                $newUser->setEmail($buyer->getEmail());
                $newUser->setContactNumber($buyer->getContactNumber());
                $newUser->setAccountId($buyer->getAccountId());
                $newUser->setPassword($buyer->getPassword());
                $newUser->setFirstName($buyer->getFirstName());
                $newUser->setLastName($buyer->getLastName());
                $newUser->setUserType(User::USER_TYPE_SELLER);
                $accountManager->registerUser($newUser, false, $buyer->getIsMobileVerified(), $buyer->getIsEmailVerified());
                $store = $storeService->createStore($newUser, STORE::STORE_TYPE_RESELLER);
                $store->setStoreNumber($storeService->generateStoreNumber($store));
                $em->flush();
                $output->writeln("Created corresponding affiliate for contact number:".$newUser->getContactNumber());
            }

            foreach($uniqueAffiliates as $affiliate){
                $newUser = new User();
                $newUser->setEmail($affiliate->getEmail());
                $newUser->setContactNumber($affiliate->getContactNumber());
                $newUser->setAccountId($affiliate->getAccountId());
                $newUser->setPassword($affiliate->getPassword());
                $newUser->setFirstName($affiliate->getFirstName());
                $newUser->setLastName($affiliate->getLastName());
                $newUser->setUserType(User::USER_TYPE_BUYER);
                $accountManager->registerUser($newUser, false, $affiliate->getIsMobileVerified(), $affiliate->getIsEmailVerified());
                $output->writeln("Created corresponding buyer for contact number:".$newUser->getContactNumber());
            }
        }
        while($userCount > 0);

        $output->writeln("[DONE] Account syncing completed");
    }
    
}