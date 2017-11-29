<?php
namespace Yilinker\Bundle\CoreBundle\Controller\Api\v3;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Yilinker\Bundle\CoreBundle\Controller\YilinkerBaseController;
use Yilinker\Bundle\CoreBundle\Traits\FormHandler;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Carbon\Carbon;

class ImageApiController extends YilinkerBaseController
{
    use FormHandler;

    /**
     * Handles Image Uploads
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @ApiDoc(
     *     section="Image",
     *     statusCodes={
     *         200={
     *              "profile, cover : {
                        userImageId (int),
                        fileName (string),
                        raw (string),
                        thumbnail (string),
                        small (string),
                        medium (string),
                        large (string)
                    }",
     *              "valid_id : {
                        fileName (string)
                   }",
     *         },
     *         400={
     *             "Field errors, failed upload or oauth errors."
     *         },
     *     },
     *     parameters={
     *         {"name"="image", "dataType"="file", "required"=true, "description"="Allowed types : jpg, png"},
     *         {"name"="type", "dataType"="string", "required"=true, "description"="Available types atm : profile, cover, valid_id, product "},
     *     },
     *     views = {"image", "product", "default", "v3"}
     * )
     */
    public function uploadAction (Request $request)
    {
        $image = $request->files->get('image', null);
        $type = $request->get('type', null);

        $form = $this->transactForm('user_image', null, array(
            "image" => $image
        ), array(
            "csrf_protection" => false
        ));

        if($form->isValid()){

            $data = $form->getData();

            switch ($type) {
                case ImageUploader::UPLOAD_TYPE_PROFILE_PHOTO:
                case ImageUploader::UPLOAD_TYPE_COVER_PHOTO:
                case ImageUploader::UPLOAD_TYPE_VALID_ID:
                case ImageUploader::UPLOAD_TYPE_PRODUCT:
                    $owner = $this->getUser();
                    break;
                default:
                    $owner = $this->getUser();
                    break;
            }

            $imageUploader = $this->get("yilinker_core.service.image_uploader");
            $details = $imageUploader->upload($owner, $type, $data["image"]);

            if(!is_null($details)){
                return new JsonResponse(array(
                    "isSuccessful" => true,
                    "message" => "Details",
                    "data" => $details
                ), 200);
            }

            return new JsonResponse(array(
                "isSuccessful" => false,
                "message" => "Image upload failed.",
                "data" => array()
            ), 400);
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
