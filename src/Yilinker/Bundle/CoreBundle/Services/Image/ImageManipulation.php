<?php

namespace Yilinker\Bundle\CoreBundle\Services\Image;

/**
 * Class ImageManipulation
 *
 * Tightly coupled with LiipImagineBundle
 */
class ImageManipulation
{

    private $imagineDataManager;

    private $imagineFilterManager;

    /**
     * Constructor
     *
     * @param Liip\ImagineBundle\Imagine\Data\DataManager $imagineDataManager
     * @param Liip\ImagineBundle\Imagine\Filter\FilterManager $imagineFilterManager
     * @param string $rootDirectory
     */
    public function __construct($imagineDataManager, $imagineFilterManager, $rootDirectory)
    {
        $this->imagineDataManager = $imagineDataManager;
        $this->imagineFilterManager = $imagineFilterManager;
        $this->rootDirectory = $rootDirectory;
    }

    /**
     * Write a thumbnail image using the LiipImagineBundle
     * 
     * @param Document $sourceImageWebPath path where full size upload is stored e.g. uploads/attachments
     * @param string $targetWebPath full absolute path to attachment directory
     * @param mixed $runTimeConfiguration
     * @param string $filter
     */
    public function writeThumbnail($sourceImageWebPath, $targetWebPath, $runTimeConfiguration = null,  $filter = 'image') 
    {
        $dataManager = $this->imagineDataManager;
        $filterManager = $this->imagineFilterManager;
        $image = $dataManager->find($filter, $sourceImageWebPath);
        $response = $filterManager->applyFilter($image, $filter, $runTimeConfiguration);

        $targetAbsolutePath = $this->rootDirectory . DIRECTORY_SEPARATOR . '..' .DIRECTORY_SEPARATOR. '..' . 
                              DIRECTORY_SEPARATOR .'web' . DIRECTORY_SEPARATOR . $targetWebPath;

        $thumb = $response->getContent();
        $f = fopen($targetAbsolutePath, 'w');
        fwrite($f, $thumb);
        fclose($f);        
    }

}
