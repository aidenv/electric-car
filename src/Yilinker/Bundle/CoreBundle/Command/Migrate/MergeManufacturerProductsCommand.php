<?php

namespace Yilinker\Bundle\CoreBundle\Command\Migrate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MergeManufacturerProductsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('yilinker:merge:manufacturer-products')
             ->setDescription('Merge old 1 by 1 manufcturer products to the new merge manufacturer product from trading')
             ->addOption(
                'userIds',
                null,
                InputOption::VALUE_REQUIRED,
                'User Ids'
             )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $userIds = $input->getOption('userIds');
        $container = $this->getContainer();
        $em = $container->get('doctrine')->getManager();
        $tbUser = $em->getRepository('YilinkerCoreBundle:User');
        $tbManufacturerProduct = $em->getRepository('YilinkerCoreBundle:ManufacturerProduct');
        $storeService = $container->get('yilinker_core.service.entity.store');
        $resellerUploader = $container->get('yilinker_merchant.service.reseller_uploader');

        $conn = $em->getConnection();
        $sql = "SELECT `Product`.`user_id`, `ManufacturerProduct`.`manufacturer_product_id` FROM `Product` INNER JOIN `ProductUnit` ON `Product`.`product_id` = `ProductUnit`.`product_id` INNER JOIN `ManufacturerProductUnit` ON `ProductUnit`.`sku` = `ManufacturerProductUnit`.`sku` INNER JOIN `ManufacturerProduct` ON `ManufacturerProductUnit`.`manufacturer_product_id` = `ManufacturerProduct`.`manufacturer_product_id` WHERE `ProductUnit`.`status` = 8 AND `ManufacturerProductUnit`.`status` != 10 AND `ManufacturerProduct`.`status` = 0";

        if ($userIds) {
            $sql .= " AND `Product`.`user_id` IN (".$userIds.")";
        }

        $sql .= " GROUP BY `user_id`, `manufacturer_product_id`";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        foreach ($results as $result) {
            $user = $tbUser->find($result['user_id']);
            if (!$user) {
                continue;
            }

            $manufacturerProductIds = array($result['manufacturer_product_id']);

            $manufacturerProducts = $tbManufacturerProduct->getActiveManufacturerProductsByIds($manufacturerProductIds);
            // $numberOfAvailableUploads = $storeService->getNumberOfAvailableUploads($user);
            $store = $user->getStore();

            if ($store->getIsInhouse()) {
                if(count($manufacturerProducts) > 0){
                    foreach($manufacturerProducts as $manufacturerProduct){
                        $uploadResult = $resellerUploader->uploadProduct($user, $manufacturerProduct);
                        if($uploadResult['isSuccessful']){
                            $output->writeln('Selected manufacturer product #'.$result['manufacturer_product_id'].' for '.$user->getFullName());
                        }
                    }
                }
            }
            // else {
            //     $output->writeln('You can only upload ' .$numberOfAvailableUploads.' product(s)');
            // }

        }
        
        $output->writeln('Merge complete!');
    }

}
