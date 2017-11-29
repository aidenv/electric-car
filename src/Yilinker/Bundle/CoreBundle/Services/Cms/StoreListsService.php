<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;
use Symfony\Component\Filesystem\Filesystem;
use Yilinker\Bundle\BackendBundle\Services\Cms\CmsManager;

/**
 * Class StoreListsService
 * @package Yilinker\Bundle\CoreBundle\Services\Cms
 */
class StoreListsService
{

    private $container;

    public $totalStoreCount;

    /**
     * @param $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function homePageStores()
    {
        $resource = $this->container->get('yilinker_core.service.xml_resource_service');
        $homeXmlObject = $resource->fetchXML('home', 'v2', 'web');
        $xmlStores = $homeXmlObject->mainList->xpath('storeList');
        $stores = json_decode(json_encode($xmlStores), true);
        $nodes = array();

        if (!is_null($stores)) {
            foreach ($stores as $storeNode) {
                if (isset($storeNode['store'])) {
                    $storeListNodeId = $storeNode['@attributes']['storeListNodeId'];
                    $nodes[$storeListNodeId]['storeListNodeId'] = $storeListNodeId;
                    $storeArray = isset($storeNode['store']['storeId']) ? array($storeNode['store']) : $storeNode['store'];
                    $formattedStore = array();

                    foreach ($storeArray as &$store) {
                        $store['slugs'] = $store['products']['slug'];
                        unset($store['products']);
                        $formattedStore[$store['storeId']] = $store;
                    }

                    $nodes[$storeListNodeId]['stores'] = $formattedStore;
                }
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

        $storeDetails = array();
        $filePath = "{$tempDirectoryFiles}/" . CmsManager::SELLER_JSON_FILE_NAME. '.json';

        if ($fs->exists($filePath)) {
            $jsonFile = file_get_contents($filePath);

            if (json_decode($jsonFile, true) !== null) {
                $storeInTemp = json_decode($jsonFile, true);
                foreach ($storeInTemp as $storeListNodeId => $tempStore) {
                    $storeDetails[$storeListNodeId]['storeListNodeId'] = $storeListNodeId;
                    $storeDetails[$storeListNodeId]['stores'] = $tempStore;
                }
            }

        }

        return $storeDetails;
    }

    public function updateStoresByTemp(&$homePageStores)
    {
        $tempStores = $this->getTempRows();

        foreach ($homePageStores as $homeStoreListNodeId => &$homePageStore) {

            if (isset($tempStores[$homeStoreListNodeId])) {
                $tempStoreDetail = $tempStores[$homeStoreListNodeId]['stores'];

                foreach ($homePageStore['stores'] as $storeId => &$store) {

                    foreach ($tempStoreDetail as $tempStoreId => $tempStore) {
                        $store = $storeId == $tempStoreId ? $tempStore : $store;
                    }

                }

            }

        }

    }

    public function formatStore($homePageStores)
    {
        $stores = array();

        foreach ($homePageStores as $storeListNodeId => $storeDetail) {

            foreach ($storeDetail['stores'] as $store) {
                $stores[] = array(
                    'storeListNodeId' => $storeListNodeId,
                    'storeId'         => $store['storeId'],
                    'productCount'    => count($store['slugs']),
                    'isQueued'        => isset($store['maxAllowableStoreProducts'])
                );
            }

        }

        return $stores;
    }

    /**
     * Get brands by page
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getHomePageStores($page = 1, $perPage = 10)
    {
        $offset = $page > 0 ? $page - 1 : 0;
        $offset *= $perPage;

        $homePageStores = $this->homePageStores();
        $this->updateStoresByTemp($homePageStores);
        $formattedStores = array_slice($this->formatStore($homePageStores), $offset, $perPage);
        $this->totalStoreCount = count($formattedStores);
        $em = $this->container->get('doctrine.orm.entity_manager');

        foreach ($formattedStores as &$store) {
            $store['storeEntity'] = $em->getRepository('YilinkerCoreBundle:Store')->find($store['storeId']);
        }

        return $formattedStores;
    }

}
