<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api;

use Exception;
use Carbon\Carbon;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Entity\Location;
use Yilinker\Bundle\CoreBundle\Entity\LocationType;

class BroadcastApiController extends Controller
{
    private $jwtManager;

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createUserAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{

            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){

            $query = false;
        }

        if(!$query){

            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();

        try{

            $isExisting = $em->getRepository('YilinkerCoreBundle:User')->findOneByEmail($query["email"]);

            if($isExisting){
                throw new Exception("User {$query['email']} already exist.");
            }

            $user = new User();
            $user->setEmail($query["email"])
                 ->setPassword($query["password"])
                 ->setFirstName($query["firstName"])
                 ->setLastName($query["lastName"])
                 ->setIsEmailVerified($query["isEmailVerified"])
                 ->setIsSocialMedia($query["isSocialMedia"])
                 ->setAccountId($query["userId"])
                 ->setIsActive(true)
                 ->setUserType(User::USER_TYPE_BUYER)
                 ->setDateAdded(!is_null($query["dateAdded"])?
                    Carbon::createFromFormat("Y-m-d H:i:s", $query["dateAdded"]) : null)
                 ->setDateLastModified(!is_null($query["dateLastModified"])?
                    Carbon::createFromFormat("Y-m-d H:i:s", $query["dateLastModified"]) : null);

            $em->persist($user);
            $em->flush();

            if($query["isSocialMedia"]){
                $this->mergeAccounts($user, $query);
            }

            $em->commit();
        }
        catch(Exception $e){
            $em->rollback();
            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    public function updateUserAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{
            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){
            $query = false;
        }

        if(!$query){
            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();

        try{

            if(!$query){
                throw new Exception("Invalid request.");
            }

            $users = $em->getRepository('YilinkerCoreBundle:User')->findBy(array("accountId" => $query["userId"]));

            if(!$users){
                throw new Exception("User not found");
            }

            foreach ($users as $user) {
                $user->setEmail($query["email"])
                     ->setPassword($query["password"])
                     ->setFirstName($query["firstName"])
                     ->setLastName($query["lastName"])
                     ->setIsEmailVerified($query["isEmailVerified"])
                     ->setIsSocialMedia($query["isSocialMedia"])
                     ->setAccountId($query["userId"])
                     ->setDateLastModified(!is_null($query["dateLastModified"])?
                        Carbon::createFromFormat("Y-m-d H:i:s", $query["dateLastModified"]) : null);


                if($query["isSocialMedia"]){
                    $this->mergeAccounts($user, $query);
                }
            }

            $em->flush();
            $em->commit();
        }
        catch(Exception $e){

            $em->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    public function createLocationAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{
            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){
            $query = false;
        }

        if(!$query){
            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();

        try{

            $locationType = $em->getRepository('YilinkerCoreBundle:LocationType')->findOneByLookupId($query["locationTypeId"]);

            if(!$locationType){
                throw new Exception("Error Processing Request", 1);
            }

            $location = new Location();
            $location->setLocation($query["location"])
                     ->setIsActive($query["isActive"])
                     ->setLookupId($query["locationId"])
                     ->setLocationType($locationType);

            $em->persist($location);

            if(!is_null($query["parentId"])){
                $parent = $em->getRepository('YilinkerCoreBundle:Location')->findOneByLookupId($query["parentId"]);

                if(!$parent){
                    throw new Exception("Error Processing Request", 1);
                }

                $location->setParent($parent);
            }

            $em->flush();
            $em->commit();
        }
        catch(Exception $e){

            $em->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    public function updateLocationAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{
            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){
            $query = false;
        }

        if(!$query){
            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();


        try{

            $locationRepository = $em->getRepository('YilinkerCoreBundle:Location');
            $location = $locationRepository->findOneByLookupId($query["locationId"]);
            $locationType = $em->getRepository('YilinkerCoreBundle:LocationType')->findOneByLookupId($query["locationTypeId"]);

            if(!$locationType){
                throw new Exception("Error Processing Request", 1);
            }

            $location->setLocation($query["location"])
                     ->setIsActive($query["isActive"])
                     ->setLookupId($query["locationId"])
                     ->setLocationType($locationType);

            if(!is_null($query["parentId"])){
                $parent = $locationRepository->findOneByLookupId($query["parentId"]);

                if(!$parent){
                    throw new Exception("Error Processing Request", 1);
                }

                $location->setParent($parent);
            }

            $em->flush();
            $em->commit();
        }
        catch(Exception $e){

            $em->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    public function createLocationTypeAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{
            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){
            $query = false;
        }

        if(!$query){
            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();

        try{

            $locationType = new LocationType();
            $locationType->setName($query["name"])
                        ->setLookupId($query["locationTypeId"]);

            $em->persist($locationType);
            $em->flush();
            $em->commit();
        }
        catch(Exception $e){

            $em->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    public function updateLocationTypeAction(Request $request)
    {
        $jwtManager = $this->get("yilinker_core.service.jwt_manager")->setKey("ylo_secret_key");

        try{
            $query = $jwtManager->setKey("ylo_secret_key")->decodeToken($request->get("request"));
        }
        catch(Exception $e){
            $query = false;
        }

        if(!$query){
            return new JsonResponse(array(
                "isSuccessful" => false
            ), 400);
        }

        $em = $this->getDoctrine()->getEntityManager();
        $em->beginTransaction();

        try{

            $locationType = $em->getRepository('YilinkerCoreBundle:LocationType')->findOneByLookupId($query["locationTypeId"]);

            if(!$locationType){
                throw new Exception("Error Processing Request", 1);
            }

            $locationType->setName($query["name"]);

            $em->flush();
            $em->commit();
        }
        catch(Exception $e){

            $em->rollback();

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => $e->getMessage()
            ), 400);
        }

        return new JsonResponse(array(
            "isSuccessful" => true,
        ), 200);
    }

    private function mergeAccounts(User $user, $query)
    {
        $oauthProviderIds = array();
        $oauthProviders = array();

        if(is_array($query) && array_key_exists("socialMediaAccounts", $query)){

            foreach ($query["socialMediaAccounts"] as $account) {
                !in_array($account["oauthProvider"], $oauthProviderIds)? array_push($oauthProviderIds, $account["oauthProvider"]) : null;
            }

            if(!empty($oauthProviderIds)){
                $em = $this->getDoctrine()->getManager();

                $oauthProviderRepository = $em->getRepository('YilinkerCoreBundle:OauthProvider');
                $oauthProviders = $oauthProviderRepository->getOauthProvidersIn($oauthProviderIds);
            }

            if(!empty($oauthProviders)){
                $socialMediaManager = $this->get('yilinker_front_end.service.social_media.social_media_manager');

                foreach ($query["socialMediaAccounts"] as $account) {
                    if(array_key_exists($account["oauthProvider"], $oauthProviders)){
                        $socialMediaManager->mergeAccount($user, $account['socialMediaId'], $oauthProviders[$account['oauthProvider']]);
                    }
                }
            }
        }
    }
}
