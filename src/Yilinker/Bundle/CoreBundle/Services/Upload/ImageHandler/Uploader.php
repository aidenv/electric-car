<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler;

use Yilinker\Bundle\CoreBundle\Services\Upload\ImageUploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\ProfilePhotoUploader;
use Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler\CoverPhotoUploader;

abstract class Uploader
{
    protected $em;

    protected $forTemp = true;

    protected $type = null;

    protected $image = null;

    protected $owner = null;

    protected $entity = null;

    protected $fileSystem = null;
    
    protected $manipulator = null;

    protected $assetsHelper = null;

    abstract function getEntity();

    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function setFileSystem($fileSystem)
    {
        $this->fileSystem = $fileSystem;

        return $this;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    public function setManipulator($manipulator)
    {
        $this->manipulator = $manipulator;

        return $this;
    }

    public function setAssetsHelper($assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;

        return $this;
    }

    public function setEntityManager($em)
    {
        $this->em = $em;

        return $this;
    }
}
