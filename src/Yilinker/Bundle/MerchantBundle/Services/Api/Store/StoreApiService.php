<?php
namespace Yilinker\Bundle\MerchantBundle\Services\Api\Store;

use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Yilinker\Bundle\CoreBundle\Services\QrCode\Generator;
use Yilinker\Bundle\CoreBundle\Entity\User;
use Yilinker\Bundle\CoreBundle\Form\Validator\Constraints\ValidSlug;

class StoreApiService
{

    /**
     * @var \Doctrine\ORM\EntityManager|Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var  Generator
     */
    private $qrCodeGenerator;

    /**
     * @var  AssetsHelper
     */
    private $assetsHelper;

    private $container;
    /**
     * @param EntityManager $entityManager
     * @param UploadService $uploadService
     */
    public function __construct(
        EntityManager $entityManager, 
        Generator $qrCodeGenerator,
        AssetsHelper $assetsHelper,
        $container
    )
    {
        $this->em = $entityManager;
        $this->qrCodeGenerator = $qrCodeGenerator;
        $this->assetsHelper = $assetsHelper;
        $this->container = $container;
    }

    public function setUpStore(User $user, $data)
    {
        $entityManager = $this->em;

        $entityManager->getConnection()->beginTransaction();
        
        $validSlug = new ValidSlug();
        $v = $this->container->get('validator');
        $errors = array();

        $store = $user->getStore();
        
        try{
            //storename
            if(array_key_exists("storeName", $data) && $store->getIsEditable()){

                $duplicateStore = $entityManager->getRepository("YilinkerCoreBundle:Store")->getStoreByStoreName($data["storeName"], $user);
                if(!empty($duplicateStore)){
                    array_push($errors, "Store name already in use.");
                }

                if($data["storeName"] == ""){
                    array_push($errors, "Store name is required.");
                }
                else{
                    if ($store->getIsEditable()) { 
                        $store->setStoreName($data["storeName"]);
                    }
                }
            }

            // description
            if(array_key_exists("storeDescription", $data)){
                if($data["storeDescription"] == ""){
                    array_push($errors, "Store description is required.");
                }
                else{
                    $store->setStoreDescription($data["storeDescription"]);
                }
            }

            
            if(array_key_exists("storeSlug", $data)) {

                $error = $v->validate($data['storeSlug'],$validSlug)->getIterator()->current();
                if ($error) {
                    array_push($errors, 'StoreSlug is not a valid slug');
                } else {
                    if ($store->getStoreSlug() != $data["storeSlug"] && $store->getIsEditable() && !$store->getSlugChanged()) {
                        $this->qrCodeGenerator->generateStoreQrCode($store, $data["storeSlug"]);
                        $store->setStoreSlug($data["storeSlug"]);
                        $store->setSlugChanged(true);    
                    }                
                }

            }

            
            if(!empty($errors)){
                throw new Exception("Invalid transaction.");
            }

            $store->setIsEditable(false);

            $entityManager->persist($store);
            $entityManager->flush();
            $entityManager->getConnection()->commit();


            return array(
                "isSuccessful" => true,
                "message" => "Info successfully updated.",
                "data" => array(
                  "storeName" => $store->getStoreName(),
                  "storeDescription" => $store->getStoreDescription(),
                  "storeSlug" => $store->getStoreSlug(),
                  "qrCodeLocation" => $this->assetsHelper->getUrl($store->getQrCodeLocation(), 'qr_code')
                )
            );
        }
        catch (Exception $e){
            $entityManager->getConnection()->rollback();

            return array(
                "isSuccessful" => false,
                "message" => $e->getMessage(),
                "data" => array(
                    "errors" => $errors,
                )
            );
        }
    }

    
}
