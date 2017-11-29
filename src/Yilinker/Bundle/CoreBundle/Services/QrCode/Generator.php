<?php

namespace Yilinker\Bundle\CoreBundle\Services\QrCode;

use Endroid\QrCode\QrCode;
use Symfony\Component\HttpFoundation\File\File;
use Gaufrette\Adapter\AwsS3;

class Generator
{
    protected $em;
    protected $container;
    private $filesystem;

    public function __construct($container, $filesystem)
    {
        $this->container = $container;
        $this->filesystem = $filesystem;
    }

    public function generateStoreQrCode($store, $slug)
    {
        $accountManager = $this->container->get("yilinker_core.service.account_manager");
        $uploadService = $this->container->get("yilinker_core.service.upload.upload");
        $router = $this->container->get("router");

        $frontendStorePath = $router->generate("user_frontend_store");
        $frontendHostName = $this->container->getParameter("frontend_hostname");
        
        $uploadService->setType("qr_code", $store->getStoreId());
        $uploadDir = $uploadService->getUploadDirectory();
        $fileName = sha1(time().uniqid().$store->getStoreId()).".png";

        $qrCode = new QrCode();
        $qrCode->setText($frontendHostName.$frontendStorePath."/".$slug)
               ->setPadding(10)
               ->setExtension("png")
               ->setErrorCorrection('high')
               ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
               ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0))
               ->setLabelFontSize(16)
               ->save($fileName);

        if(file_exists($fileName)){
            $result = copy($fileName, $uploadDir."/".$fileName);
            unlink($fileName);
        }

        $store->setQrCodeLocation($fileName);
        
        $adapter = $this->filesystem->getAdapter();
        if($adapter instanceof AwsS3){
            $imagePath = $uploadDir."/".$fileName;
            if(file_exists($imagePath)){
                $file = new File($imagePath);
                $adapter->setMetadata($imagePath, array('contentType' => $file->getMimeType()));
                $adapter->write($imagePath, file_get_contents($file->getPathname()));
            }
        }

        return $fileName;
    }
}
