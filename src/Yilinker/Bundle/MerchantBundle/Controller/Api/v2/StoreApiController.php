<?php

namespace Yilinker\Bundle\MerchantBundle\Controller\Api\v2;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Entity\UserImage;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use stdClass;


class StoreApiController extends YilinkerBaseController
{
    use FormHandler;

    /**
     * Setup store
     *
     * @param Request $request
     * @return JsonResponse
     * @ApiDoc(
     *     section="Store",
     *     statusCodes={
     *         200={"Success"},
     *         400={"Field errors or oauth errors."}
     *     },
     *     parameters={
     *         {"name"="profilePhoto", "dataType"="string", "required"=false},
     *         {"name"="coverPhoto", "dataType"="string", "required"=false},
     *         {"name"="storeSlug", "dataType"="string", "required"=true},
     *         {"name"="storeName", "dataType"="string", "required"=true},
     *         {"name"="storeDescription", "dataType"="string", "required"=true},
     *     },
     *     views = {"store", "default", "v2"}
     * )
     */
    public function setupStoreAction(Request $request)
    {
        $postData = array(
            "profilePhoto" => $request->get("profilePhoto", null),
            "coverPhoto" => $request->get("coverPhoto", null),
            "storeName" => $request->get("storeName", null),
            "storeSlug" => $request->get("storeSlug", null),
            "storeDescription" => $request->get("storeDescription", null),
        );

        $authenticatedUser = $this->getUser();
        $form = $this->transactForm(
                    "update_store_info_v2", 
                    null, 
                    $postData, 
                    array(
                        "csrf_protection" => false,
                        "user" => $authenticatedUser
                    )
        );

        if($form->isValid()){

            $em = $this->get("doctrine.orm.entity_manager");
            $userImageRepository = $em->getRepository("YilinkerCoreBundle:UserImage");

            $data = $form->getData();
            $store = $authenticatedUser->getStore();

            if($store->getIsEditable()){
                $store->setStoreName($data["storeName"])
                      ->setStoreSlug($data["storeSlug"])
                      ->setIsEditable(false);
            }

            $store->setStoreDescription($data["storeDescription"]);

            if($data["profilePhoto"]){
                $profilePhoto = $userImageRepository->loadUserImageByName(
                                        $data["profilePhoto"],
                                        $authenticatedUser,
                                        UserImage::IMAGE_TYPE_AVATAR,
                                        false
                                   );

                $authenticatedUser->setPrimaryImage($profilePhoto);
            }

            if($data["coverPhoto"]){
                $coverPhoto = $userImageRepository->loadUserImageByName(
                                        $data["coverPhoto"],
                                        $authenticatedUser,
                                        UserImage::IMAGE_TYPE_BANNER,
                                        false
                                   );

                $authenticatedUser->setPrimaryCoverPhoto($coverPhoto);
            }

            $em->flush();

            return new JsonResponse(array(
                "isSuccessful" => true,
                "message" => "Successfully updated store info.",
                "data" => new stdClass()
            ));
        }

        return new JsonResponse(array(
            "isSuccessful" => false,
            "message" => $this->getErrors($form, false),
            "data" => array(
                "errors" => $this->getErrors($form, true)
            )
        ), 400);
    }
}
