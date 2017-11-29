<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\UploaderInterface;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\Uploader;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocument;
use Yilinker\Bundle\CoreBundle\Entity\LegalDocumentType;
use Carbon\Carbon;

class LegalDocumentUploader extends Uploader implements UploaderInterface
{
    private $uploadDirectory = null;

    private $fileName = null;

    private $documentPath = null;

    private $tmpPath = null;

    private $isTmp = true;

    public function __construct($isTmp = true)
    {
        $this->isTmp = $isTmp;
    }

    public function createDirectories()
    {
        $this->tmpPath = "assets/legal_documents/tmp/".$this->owner->getUserId();
        $this->documentPath = "assets/legal_documents/".$this->owner->getUserId();

        $this->uploadDirectory = $this->isTmp? $this->tmpPath : $this->documentPath;

        $this->manageDir();

        return $this;
    }

    public function createImageSizes()
    {
        return $this;
    }

    public function upload()
    {
        if($this->isTmp){

            $imageName = trim($this->owner->getUserId(). 
                         '_'.rand(1, 9999).'_'.LegalDocumentType::TYPE_VALID_ID. 
                         '_'.strtotime(Carbon::now()));
            
            $this->fileName = $imageName.".".$this->image->getClientOriginalExtension();
            $this->image->move($this->uploadDirectory, $this->fileName);
        }
        else{

            $imagePath = $this->tmpPath.DIRECTORY_SEPARATOR.$this->image;
            $accreditationApplication = $this->owner->getAccreditationApplication();
            
            if(file_exists($imagePath) && $accreditationApplication){
                $image = new File($imagePath);
                $image->move($this->documentPath, $this->image);

                $legalDocumentType = $this->em
                                          ->getRepository("YilinkerCoreBundle:LegalDocumentType")
                                          ->find($this->getIdByString());

                $legalDocument = $accreditationApplication->getLegalDocumentByType($legalDocumentType);

                if(!$legalDocument){
                    $legalDocument = new LegalDocument();
                    $legalDocument->setAccreditationApplication($accreditationApplication)
                                  ->setLegalDocumentType($legalDocumentType)
                                  ->setName($this->image)
                                  ->setDateAdded(Carbon::now())
                                  ->setDateLastModified(Carbon::now())
                                  ->setIsApproved(false)
                                  ->setIsEditable(false);

                     $accreditationApplication->addLegalDocument($legalDocument);

                     $this->em->persist($legalDocument);
                }
                elseif($legalDocument->getIsEditable()){
                    $legalDocument->setName($this->image)
                                  ->setDateLastModified(Carbon::now())
                                  ->setIsEditable(false);
                }

                $this->em->flush();
                $this->entity = $legalDocument;
            }
            else{
                return null;
            }
        }

        return $this;
    }

    public function getEntity()
    {
        return array(
            "fileName" => $this->isTmp? $this->fileName : $this->entity->getName()
        );
    }

    public function manageDir()
    {
        if(!file_exists("assets/legal_documents".($this->isTmp? "/tmp":""))){
            mkdir("assets/legal_documents".($this->isTmp? "/tmp":""), 0777);
        }

        if(!file_exists($this->uploadDirectory)){
            mkdir($this->uploadDirectory, 0777);
        }
    }

    public function getIdByString()
    {
        switch($this->type){
            case "dti_sec_permit":
                return LegalDocumentType::TYPE_DTI_SEC_PERMIT;
            case "mayors_permit":
                return LegalDocumentType::TYPE_MAYORS_PERMIT;
            case "bir_permit":
                return LegalDocumentType::TYPE_BIR_PERMIT;
            case "form_m11501":
                return LegalDocumentType::TYPE_FORM_M11501;
            case "others":
                return LegalDocumentType::TYPE_OTHERS;
            case "sss":
                return LegalDocumentType::TYPE_SSS;
            case "pagibig":
                return LegalDocumentType::TYPE_PAG_IBIG;
            case "postal":
                return LegalDocumentType::TYPE_POSTAL;
            case "passport":
                return LegalDocumentType::TYPE_PASSPORT;
            case "drivers_license":
                return LegalDocumentType::TYPE_DRIVERS_LICENSE;
            case "prc":
                return LegalDocumentType::TYPE_PRC;
            case "voters_id":
                return LegalDocumentType::TYPE_VOTERS_ID;
            case "school_id":
                return LegalDocumentType::TYPE_SCHOOL_ID;
            case "tin":
                return LegalDocumentType::TYPE_TIN;
            case "valid_id":
                return LegalDocumentType::TYPE_VALID_ID;
        }
    }
}