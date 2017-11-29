<?php

namespace Yilinker\Bundle\CoreBundle\Services\Cms;

use Symfony\Component\Filesystem\Filesystem;
use Yilinker\Bundle\CoreBundle\Services\Cms\PagesService;

class ProductListsService
{
    private $container;
    public $totalProductLists;

    public function setContainer($container)
    {
        $this->container = $container;
    }

    public function homepageNodes($page = 1, $offset = 4)
    {
        $resource = $this->container->get('yilinker_core.service.xml_resource_service');
        $productsXML = $resource->fetchXML('products', 'v2', 'web');
        $homepageXML = $resource->fetchXML('home', 'v2', 'web');
        $homepageLists = $homepageXML->xpath('//productList[@productListNodeId]');
        $nodes = array();
        foreach ($homepageLists as $homepageNode) {
            $productListId = (string)$homepageNode['productListNodeId'];
            if ($productListId) {
                $productNode = $productsXML->xpath('//list[@id = "'.$productListId.'"]');
                if ($productNode) {
                    $productNode = array_shift($productNode);
                    $nodes[] = array(
                        'type'          => 'Homepage Row',
                        'title'         => (string)$homepageNode->name,
                        'productIds'    => (array)$productNode->productId,
                        'productListId' => $productListId
                    );
                }
            }
        }

        $itemsYouMayLike = $productsXML->xpath('//list[@id = "itemYouMayLike"]');
        $itemsYouMayLike = array_shift($itemsYouMayLike);
        if ($itemsYouMayLike) {
            $nodes[] = array(
                'type'          => 'Homepage Row',
                'title'         => 'Items you May Like',
                'productIds'    => (array)$itemsYouMayLike->productId,
                'productListId' => (string)$itemsYouMayLike['id']
            );
        }

        return $nodes;
    }

    public function getTempRows()
    {
        $fs = new Filesystem();
        $cmsManager = $this->container->get('yilinker_backend.cms_manager');

        $tempDirectoryFiles = $cmsManager->getCoreTempDirectory();

        if (!$fs->exists($tempDirectoryFiles)) {
            $fs->mkdir($tempDirectoryFiles, 0777);
        }

        $files = scandir($tempDirectoryFiles);
        $rows = array();

        foreach ($files as $key => $file) {
            $filePath = "{$tempDirectoryFiles}/{$file}";

            if ($file != "." && $file != ".." && $fs->exists($filePath)) {

                $jsonFile = file_get_contents($filePath);
                if (json_decode($jsonFile, true) !== null) {
                    $data = json_decode($jsonFile, true);
                    if (isset($data['sectionId'])) {
                        $title = $data['sectionId'] == PagesService::NODE_ID_PRODUCT_LIST
                                 ? 'Custom Page'
                                 : 'Homepage Row';
                        $rows[] = array(
                            'type' => $title,
                            'productIds' => $data['products'],
                            'productListId' => $data['title'],
                            'title' => $data['title'],
                        );
                    }
                }
            }
        }

        return $rows;
    }

    public function updateProductListByTemp(&$node)
    {
        $tempRows = $this->getTempRows();
        $tempRowCount = 0;

        $tempArray = array();

        // replace existing nodes from temp file
        foreach ($tempRows as $row) {
            $productListId = $row['productListId'];
            $key = array_search($productListId, array_map(function($element) {
              return $element['productListId'];
            }, $node));

            if ($key) {
                $node[$key]['productIds'] = $row['productIds'];
            }
            else {
                $tempArray[] = $row;
                $tempRowCount ++;
            }
        }

        foreach ($tempArray as $data) {
            $node[] = $data;
        }

        $this->totalProductLists += $tempRowCount;
    }

    public function page($page = 1, $perPage = 10)
    {
        $offset = $page > 0 ? $page - 1 : 0;
        $offset *= $perPage;

        $productListFilter = array();
        $nodes = $this->homepageNodes();

        foreach ($nodes as $node) {
            $productListFilter[] = '@id != "'.$node['productListId'].'"';
        }
        $productListFilter = $productListFilter ? '['.implode(' and ', $productListFilter).']' : '';
        $resource = $this->container->get('yilinker_core.service.xml_resource_service');
        $xml = $resource->fetchXML('products', 'v2', 'web');

        $productLists = $xml->xpath('/lists/list'.$productListFilter);
        foreach ($productLists as $productList) {
            $nodes[] = array(
                'type'          => 'Custom Page',
                'isCustomPage'  => true,
                'title'         => (string)$productList['id'],
                'productIds'    => (array)$productList->productId,
                'productListId' => (string)$productList['id']
            );
        }

        $this->totalProductLists = count((array)$xml->xpath('/lists/list'));

        $this->updateProductListByTemp($nodes);

        return array_slice($nodes, $offset, $perPage);
    }
}
