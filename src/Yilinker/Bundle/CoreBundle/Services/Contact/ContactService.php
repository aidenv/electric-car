<?php

namespace Yilinker\Bundle\CoreBundle\Services\Contact;

use Exception;
use Carbon\Carbon;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Store;
use Yilinker\Bundle\CoreBundle\Entity\Contact;
use Yilinker\Bundle\CoreBundle\Services\Predis\PredisService;

/**
 * Class ContactService
 * @package Yilinker\Bundle\CoreBundle\Services\Message
 */
class ContactService
{
    /**
     * @var \Doctrine\ORM\EntityManager\Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var Predis
     */
    private $predisService;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, PredisService $predisService)
    {
        $this->entityManager = $entityManager;
        $this->predisService = $predisService;
    }

    /**
     * Fetch the users connected to the authenticated user
     *
     * @return array
     */
    public function getContacts(User $user, $keyword = null, $limit = null, $offset = null)
    {
        $contactRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Contact");
        $contacts = $contactRepository->getUserContacts($user, $keyword, $limit, $offset);

        $users = array();

        foreach($contacts as $contact){
            $requestor = $contact->getRequestor();
            $requestee = $contact->getRequestee();

            if(
                $requestor != $user && 
                $requestor->getUserType() != User::USER_TYPE_GUEST
            ){
                array_push($users, $requestor);
            }
            elseif( 
                $requestee != $user && 
                $requestee->getUserType() != User::USER_TYPE_GUEST
            ){
                array_push($users, $requestee);
            }
        }

        return $users;
    }

    /**
     * add to contact if not in contact list
     * @param User $requestor 
     * @param User $requestee 
     */
    public function addToContact(User $requestor, User $requestee)
    {
        try{

            if($requestor->isSeller() && $requestor->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER){
                throw new Exception("Error Processing Request", 1);
            }

            if($requestee->isSeller() && $requestee->getStore()->getStoreType() == Store::STORE_TYPE_RESELLER){
                throw new Exception("Error Processing Request", 1);
            }

            $contactRepository = $this->entityManager->getRepository("YilinkerCoreBundle:Contact");

            $contactEntry = $contactRepository->getUserContact($requestor, $requestee);

            if(
                is_null($contactEntry) && 
                $requestor->getUserType() != User::USER_TYPE_GUEST &&
                $requestee->getUserType() != User::USER_TYPE_GUEST
            ){
                $contact = new Contact();
                $contact->setRequestor($requestor)
                        ->setRequestee($requestee)
                        ->setDateAdded(Carbon::now());

                $this->entityManager->persist($contact);
                $this->entityManager->flush();

                $this->predisService->publishContact($contact);
            }
        }
        catch(Exception $e){

        }
    }
}
