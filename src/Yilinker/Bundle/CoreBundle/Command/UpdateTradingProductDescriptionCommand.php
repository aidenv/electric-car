<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\CoreBundle\Entity\ManufacturerProduct;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections;
use Yilinker\Bundle\CoreBundle\Entity\OrderStatus;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Exception\RequestException;
use DomDocument;

class UpdateTradingProductDescriptionCommand extends ContainerAwareCommand
{
    private $defaultPerPage = 10;

    protected function configure()
    {
        $this->setName('yilinker:synchronize:product-description')
             ->setDescription('Synchronize Product Description');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getEntityManager();
        $tradingService = $container->get('yilinker_core.import_export.yilinker.trading');
        $config = $tradingService->getConfig();

        $request = new FormRequest(
            FormRequest::METHOD_GET, '/'.$config['routes']['get_product_detail']."/api_key/".$config['api_key'], $config['baseurl']
        );

        $manufacturerProducts = $em->getRepository('YilinkerCoreBundle:ManufacturerProduct')
                                   ->findBy(array(
                                       'status' => ManufacturerProduct::STATUS_ACTIVE,
                                   ));
        $updateResults = array();
        foreach($manufacturerProducts as $manufacturerProduct){
            $parameters = array(
                'productId' => $manufacturerProduct->getReferenceNumber(),
            );
            $request->setFields($parameters);
            $buzzResponse = new Response();
            $client = new Curl();        
            $client->send($request, $buzzResponse);

            if ($buzzResponse->isSuccessful()) {
                $apiResponse = json_decode($buzzResponse->getContent(), true);
                if($apiResponse['isSuccessful']){

                    $description = html_entity_decode($apiResponse['data']['description']);
                    if($description){
                        $dom = new DomDocument;
                        @$dom->loadHTML($description);
                        $images = $dom->getElementsByTagName('img');
                        foreach ($images as $image){
                            $src = $image->getAttribute('src');
                            $url = parse_url($src);
                            $image->setAttribute('src', $config['appurl'].$url['path']);
                        }
                        $description = $dom->saveHTML();
                    }

                    $shortDescription = $apiResponse['data']['short_desc'];
                    
                    
                    $manufacturerProduct->setDescription($description);
                    $manufacturerProduct->setShortDescription($shortDescription);


                    $maps = $manufacturerProduct->getManufacturerProductMaps() ? $manufacturerProduct->getManufacturerProductMaps() : array();
                    $productCount = 0;
                    $manufacturerProductData = array(
                        'referenceNumber' => $manufacturerProduct->getReferenceNumber(),
                        'productCount'    => 0,
                    );
                    foreach($maps as $map){
                        $product = $map->getProduct();
                        if($product){
                            $productCount++;
                            $product->setDescription($description);
                            $product->setShortDescription($shortDescription);
                        }
                    }
                    $manufacturerProductData['productCount'] = $productCount;
                    $updateResults[] = $manufacturerProductData;
                }
            }
        }
        $em->flush();

        echo json_encode($updateResults);
    }
}
