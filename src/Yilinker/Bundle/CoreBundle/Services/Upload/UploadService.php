<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload;

use Gaufrette\Adapter\AwsS3;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Yilinker\Bundle\CoreBundle\Model\SimpleImage;

class UploadService
{
    /**
     * Set File Upload Directory
     * @var string
     **/
    public $uploadDirectory = 'assets/images/uploads';

    /**
     * @var Gaufrette\Filesystem
     */
    private $filesystem;

    /**
     * The constructor
     *
     * @param Gaufrette\Filesystem $filesystem
     **/
    public function __construct($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Types : user, cms, message
     *
     * @param string $type
     * @param null $id
     */
    public function setType($type = "cms", $id = null)
    {
        switch($type){
            case "message" :
                $this->uploadDirectory = "assets/images/uploads/chats";
                break;
            case "user" :
                $this->uploadDirectory = "assets/images/uploads/users/".$id;
                $this->manageDir("assets/images/uploads/users/", $id);
                break;
            case "qr_code" :
                $this->uploadDirectory = "assets/images/uploads/qr_code/".$id;
                $this->manageDir("assets/images/uploads/qr_code/", $id);
                break;
            default:
                $this->uploadDirectory = "assets/images/uploads/cms";
                break;
        }
    }

    /**
     * @param string $uploadDirectory
     */
    public function setUploadDirectory($uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    public function getUploadDirectory()
    {
        return $this->uploadDirectory;
    }

    /**
     * Upload File
     * @param File $file
     * @return null|string
     */
    public function uploadFile(File $file)
    {
        $imageName = sha1(uniqid().time().uniqid());

        $imageLocation = null;
        $fileName = $imageName.'.'.$file->getClientOriginalExtension();

        if ($file instanceof UploadedFile && $this->moveUploadedFile($file, $this->uploadDirectory, $fileName)) {
            return $fileName;
        }

        return false;
    }

    /**
     * Upload Files
     * @param array $files
     * @return null|string
     */
    public function uploadFiles(array $files)
    {
        $fileNames = array();

        foreach($files as $file){
            $imageName = sha1(uniqid().time().uniqid());

            $imageLocation = null;
            $fileName = $imageName.'.'.$file->getClientOriginalExtension();

            if ($file instanceof UploadedFile && $this->moveUploadedFile($file, $this->uploadDirectory, $fileName)) {
                array_push($fileNames, $fileName);
            }
            else{
                array_push($fileNames, false);
            }
        }

        return $fileNames;
    }

    /**
     * Move Uploaded File to Upload File Directory
     *
     * @param UploadedFile $file
     * @param $uploadDirectory
     * @param $fileWithExtension
     * @return string
     */
    public function moveUploadedFile(UploadedFile $file, $uploadDirectory, $fileWithExtension)
    {
        $isFileCreated = false;

        $movedFile = $file->move($uploadDirectory, $fileWithExtension);

        $adapter = $this->filesystem->getAdapter();
        if($adapter instanceof AwsS3){
            $adapter->setMetadata($movedFile->getPathname(), array('contentType' => $movedFile->getMimeType()));
            $adapter->write($movedFile->getPathname(), file_get_contents($movedFile->getPathname()));
        }

        return $fileWithExtension;
    }

    public function manageDir($mainDir, $folder)
    {
        if(!file_exists($mainDir)){
            mkdir($mainDir, 0777);
        }

        if(!file_exists($mainDir.$folder)){
            mkdir($mainDir.$folder, 0777);
        }

        if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."raw")){
            mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."raw", 0777);
        }

        if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."large")){
            mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."large", 0777);
        }

        if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."medium")){
            mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."medium", 0777);
        }

        if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."small")){
            mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."small", 0777);
        }

        if(!file_exists($mainDir.$folder.DIRECTORY_SEPARATOR."thumbnail")){
            mkdir($mainDir.$folder.DIRECTORY_SEPARATOR."thumbnail", 0777);
        }
    }
}
