<?php

namespace Yilinker\Bundle\CoreBundle\Doctrine\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\EntityManager;
use Yilinker\Bundle\CoreBundle\Entity\UserIdentificationCard;
use Gaufrette\Adapter\AwsS3;

/**
 * Implementation taken from: http://symfony.com/doc/current/cookbook/doctrine/file_uploads.html
 */
class UserIdentificationCardListener
{
    const MAX_IMAGE_WIDTH_PX = 1200;

    const MAX_IMAGE_HEIGHT_PX = 800;

    private $webRootDirectory;

    private $imageManipulator;

    /**
     * @var Gaufrette\Filesystem
     */
    private $filesystem;

    public function setKernelRootDirectory($kernelRootDirectory)
    {
        $this->webRootDirectory = $kernelRootDirectory.'/../../web';
    }
    
    public function setImageManipulationService($imageManipulator)
    {
        $this->imageManipulator = $imageManipulator;
    }

    public function setFilesystem($filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof UserIdentificationCard) {
            $this->preUpload($entity);
        }
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof UserIdentificationCard) {
            $this->preUpload($entity);
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof UserIdentificationCard) {
            $this->upload($entity);
        }
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof UserIdentificationCard) {
            $this->upload($entity);
        }
    }

    private function preUpload($entity)
    {
        if(null !== $entity->getFile()){
            $uniqueFilename = sha1(uniqid(mt_rand(), true));
            $entity->setFileName($uniqueFilename.'.'.$entity->getFile()->guessExtension());
        }
    }

    private function upload($entity)
    {
        if (null === $entity->getFilename()) {
            return;
        }
        
        /**
         * if there is an error when moving the file, an exception will
         * be automatically thrown by move(). This will properly prevent
         * the entity from being persisted to the database on error
         */
        $userDocumentsDirectory = $this->webRootDirectory.'/'.$entity->getUploadDir();
        $userDirectory = $userDocumentsDirectory. '/'. $entity->getUser()->getUserId();

        if (!file_exists($userDocumentsDirectory)) {
            mkdir($userDocumentsDirectory, 0777);   
        }

        if (!file_exists($userDirectory)) {
            mkdir($userDirectory, 0777);   
        }       

        $entity->getFile()->move($userDirectory, $entity->getFilename());
        $entity->setFile(null);

        /**
         * Resize the image
         */
        $imagewebPath = $entity->getWebPath();
        $imageInfo = getimagesize($imagewebPath);
        $this->imageManipulator->writeThumbnail($imagewebPath, $imagewebPath, array(
            "filters" => array(
                "thumbnail" => array(
                    "size" => array(self::MAX_IMAGE_WIDTH_PX, self::MAX_IMAGE_HEIGHT_PX),
                ),
            ),
        ));

        /**
         * Upload to the cloud
         */
        $adapter = $this->filesystem->getAdapter();
        if($adapter instanceof AwsS3){
            $adapter->setMetadata($entity->getWebPath(), array('contentType' => $imageInfo['mime']));
            $adapter->write($entity->getWebPath(), file_get_contents($entity->getWebPath()));
        }
    }

}
