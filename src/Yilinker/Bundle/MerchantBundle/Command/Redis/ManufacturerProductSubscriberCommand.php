<?php

namespace Yilinker\Bundle\MerchantBundle\Command\Redis;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Predis;
use Yilinker\Bundle\CoreBundle\Services\Predis\PredisService;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Class for syncing products with manufacturer products  & product units with manufacturer product units
 *
 * 
 * NOTE: Execute this command within the root directory of the application only for image syncing to work (this is
 * because of LiipBundle's security where you are only allowed to manipulate images within the application root:
 * Error: Source image was searched out side of the defined root path)
 *
 */
class ManufacturerProductSubscriberCommand extends ContainerAwareCommand
{
    protected $predis;

    /**
     * Configure step
     */
    protected function configure()
    {
        $this
            ->setName('yilinker:redis:manufacturer-product-subscriber')
            ->setDescription('Manufacturer Product Redis Subscriber')
            ->addArgument(
                'all',
                InputArgument::OPTIONAL,
                'Update all existing products. Execute this command within the root directory of the application only for image syncing to work.'
            )
            ->addOption(
                'syncImages',
                null,
                InputOption::VALUE_REQUIRED,
                'Sync the images. This takes up a lot of resources. Use with caution.'
            )
            ->addOption(
                'mids',
                'mids',
                InputOption::VALUE_OPTIONAL + InputOption::VALUE_IS_ARRAY,
                'ManufacturerProductIds to sync'
            )
         ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updateAll = $input->getArgument('all');
        $syncImages = $input->getOption('syncImages') == 'true';
        
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();

        $productFileUploader = $container->get('yilinker_merchant.service.product_file_uploader');
        $manufacturerProductMapRepository = $em->getRepository('YilinkerCoreBundle:ManufacturerProductMap');
        $uploadDirectory = $productFileUploader->getUploadDirectory();

        $predisClient = new Predis\Client(array(
            "scheme" => "tcp",
            "host" => $container->getParameter('redis_host'),
            "port" => $container->getParameter('redis_port'),
            "password" => $container->getParameter('redis_password'),
            "read_write_timeout" => 0
        ));
        
        if (is_null($updateAll)) {
            $resellerUploader = $container->get('yilinker_merchant.service.reseller_uploader');

            $dataCount = $predisClient->llen(PredisService::MANUFACTURER_PRODUCT_CHANNEL);

            if ($dataCount > 0) {
                $collection = $predisClient->lrange(PredisService::MANUFACTURER_PRODUCT_CHANNEL, 0, -1);
                foreach ($collection as $key => $data) {
                    $originalData = $data;
                    if ($data !== null && json_decode($data, true) !== null) {
                        $data = json_decode($data, true);
                        if (isset($data['manufacturerProduct']) && isset($data['product'])) {
                            $productMap = $manufacturerProductMapRepository->findOneBy(array(
                                              'manufacturerProduct' => $data['manufacturerProduct'],
                                              'product' => $data['product']
                                          ));

                            if ($productMap) {
                                $syncData = $resellerUploader->syncProduct($productMap->getProduct(), $uploadDirectory, $syncImages);
                                if ((bool) $syncData['isSuccessful']) {
                                    $predisClient->lrem(PredisService::MANUFACTURER_PRODUCT_CHANNEL, -1, $originalData);
                                    $output->writeln("<info>Product: {$data['product']} synced to Manufacturer Product: {$data['manufacturerProduct']}</info>");
                                }
                                else {
                                    $output->writeln("<error>Error while syncing Manufacturer Product: {$data['manufacturerProduct']} to Product: {$data['product']}</error>");
                                    $output->writeln("<error>Error: {$syncData['error']}");
                                }
                            }
                            else {
                                $output->writeln("<error>No product map found for Product: {$data['product']}!</error>");
                            }
                        }
                        else {
                            $output->writeln('<error>Invalid json data.</error>');
                        }
                    }
                    else {
                        $output->writeln('<error>Invalid json data unable to decode.</error>');
                    }
                }
            }
            else {
                $output->writeln('<error>No record found.</error>');
            }
        }
        else {
            $rsm = new ResultSetMapping();
            $rsm->addScalarResult('manufacturer_product_id', 'manufacturer_product_id');
            $rsm->addScalarResult('product_id', 'product_id');

            $sql = "SELECT
                        product_id, manufacturer_product_id
                    FROM
                        ManufacturerProductMap";

            $manufacturerProductIds = $input->getOption('mids');
            $manufacturerProductIds = implode(',', $manufacturerProductIds);
            if ($manufacturerProductIds) {
                $sql .= " WHERE manufacturer_product_id IN ($manufacturerProductIds)";
            }

            $results = $em->createNativeQuery($sql, $rsm)->getResult();

            try {
                foreach ($results as $value) {
                        $predisClient->rpush(PredisService::MANUFACTURER_PRODUCT_CHANNEL, json_encode(array(
                            'product' => $value['product_id'],
                            'manufacturerProduct' => $value['manufacturer_product_id'],
                        )));
                }
            }
            catch (Exception $e) {}
        }
    }
}
