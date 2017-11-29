<?php

namespace Yilinker\Bundle\CoreBundle\Services\Upload\ImageHandler;

interface UploaderInterface
{
    public function createDirectories();

    public function createImageSizes();

    public function upload();
}