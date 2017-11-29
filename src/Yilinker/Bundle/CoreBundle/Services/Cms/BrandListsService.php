<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;
use Symfony\Component\Filesystem\Filesystem;
use Yilinker\Bundle\BackendBundle\Services\Cms\CmsManager;

/**
 * Class brandListsService
 * @package Yilinker\Bundle\CoreBundle\Services\Cms
 */
class BrandListsService
{

    private $container;

    public $totalBrandCount;

    /**
     * @param $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function homePageBrands()
    {
        $resource = $this->container->get('yilinker_core.service.xml_resource_service');
        $homeXmlObject = $resource->fetchXML('home', 'v2', 'web');
        $xmlArray = json_decode(json_encode($homeXmlObject), true);
        $topBrands = isset($xmlArray['topBrands']) ? $xmlArray['topBrands'] : null;
        $nodes = array();

        if (!is_null($topBrands)) {
            $xmlBrands = isset($topBrands['brands']['brandId']) ? array($topBrands['brands']) : $topBrands['brands'];
            foreach ($xmlBrands as $brand) {
                $nodes[] = $brand['brandId'];
            }
        }

        return $nodes;
    }

    /**
     * Get brands in temp file
     *
     * @return array|mixed
     */
    public function getTempRows()
    {
        $fs = new Filesystem();
        $cmsManager = $this->container->get('yilinker_backend.cms_manager');

        $tempDirectoryFiles = $cmsManager->getCoreTempDirectory();

        if (!$fs->exists($tempDirectoryFiles)) {
            $fs->mkdir($tempDirectoryFiles, 0777);
        }

        $brandIds = array();
        $filePath = "{$tempDirectoryFiles}/" . CmsManager::TOP_BRANDS_JSON_FILE_NAME . '.json';

        if ($fs->exists($filePath)) {

            $jsonFile = file_get_contents($filePath);
            if (json_decode($jsonFile, true) !== null) {
                $brands = json_decode($jsonFile, true);
                foreach ($brands as $brand) {
                    $brandIds[] = $brand['brand'];
                }
            }

        }

        return $brandIds;
    }

    public function updateBrandsByTemp(&$node)
    {
        $tempRows = $this->getTempRows();
        $tempRowCount = 0;


        $this->totalProductLists += $tempRowCount;
    }

    /**
     * Get brands by page
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getHomePageBrands($page = 1, $perPage = 10)
    {
        $offset = $page > 0 ? $page - 1 : 0;
        $offset *= $perPage;

        $homePageBrands = $this->homePageBrands();
        $tempBrands = $this->getTempRows();
        $updatedBrands = array_unique(array_merge($tempBrands, $homePageBrands));
        $this->totalBrandCount = count($updatedBrands);
        $updatedBrands = array_slice($updatedBrands, $offset, $perPage);
        $em = $this->container->get('doctrine.orm.entity_manager');
        $brands = $em->getRepository('YilinkerCoreBundle:Brand')->findByBrandId($updatedBrands);

        return $brands;
    }
}
