<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class DocumentUploader
 * @package Yilinker\Bundle\MerchantBundle\Services\FileUpload
 */
class DocumentUploader
{

    const UPLOAD_DIR = 'assets/legal_documents/';

    /**
     * Upload File
     * @param File $file
     * @param $folderName
     * @param $fileName
     * @return null|string
     */
    public function uploadFile (File $file, $folderName, $fileName)
    {
        $fileLocation = null;
        $fileWithExtension = $fileName . '.' . $file->getClientOriginalExtension();
        $fullPath = self::UPLOAD_DIR . DIRECTORY_SEPARATOR . $folderName;

        if ($file instanceof UploadedFile &&
            $this->moveUploadedFile($file, $fullPath, $fileWithExtension)) {
            $fileLocation = $fileWithExtension;
        }

        return $fileLocation;
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
        $file->move($uploadDirectory, $fileWithExtension);

        if (file_exists($uploadDirectory)) {
            $isFileCreated = true;
        }

        return $isFileCreated;
    }

}
