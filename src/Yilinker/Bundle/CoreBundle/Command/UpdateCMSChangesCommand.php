<?php

namespace Yilinker\Bundle\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yilinker\Bundle\BackendBundle\Services\Cms\CmsManager;

class UpdateCMSChangesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker:cms:update-changes')
            ->setDescription('Update Changes in CMS');
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Update starting.');
        $this->updateProducts($output);
        $this->updateMainBanner($output);
        $this->updateStores($output);
        $this->updateBrands($output);
        $output->writeln('Update done');
    }

    /**
     * Update Products
     *
     * @param OutputInterface $output
     */
    private function updateProducts (OutputInterface $output)
    {
        $container = $this->getContainer();
        $cmsManager = $container->get('yilinker_backend.cms_manager');
        $file = $cmsManager->getTempJsonFile(CmsManager::PRODUCTS_JSON_FILE_NAME);
        $tempProducts = json_decode($file, true);
        $formFactory = $container->get('form.factory');

        if (!is_null($tempProducts)) {
            $output->writeln("Updating products...");

            foreach ($tempProducts as $tempProduct) {
                $form = $formFactory->create('core_cms_product_detail')
                                    ->submit($tempProduct);
                $cmsManager->saveProductList($form->getData(), true);
            }

            $output->writeln("Product is now updated.");
        }
        else {
            $output->writeln("Product: Nothing to update.");
        }

    }

    /**
     * Update Main banner
     *
     * @param OutputInterface $output
     */
    public function updateMainBanner(OutputInterface $output)
    {
        $container = $this->getContainer();
        $cmsManager = $container->get('yilinker_backend.cms_manager');
        $file = $cmsManager->getTempJsonFile(CmsManager::TOP_BANNERS_JSON_FILE_NAME);
        $tempBanners = json_decode($file, true);

        if (!is_null($tempBanners)) {
            $output->writeln("Updating main banner...");

            $cmsManager->saveMainBanners($tempBanners, true);

            $output->writeln("Main banner is now updated.");
        }
        else {
            $output->writeln("Main banner: Nothing to update.");
        }

    }

    /**
     * Update Stores
     *
     * @param OutputInterface $output
     */
    public function updateStores(OutputInterface $output)
    {
        $container = $this->getContainer();
        $cmsManager = $container->get('yilinker_backend.cms_manager');
        $file = $cmsManager->getTempJsonFile(CmsManager::SELLER_JSON_FILE_NAME);
        $tempStores = json_decode($file, true);

        if (!is_null($tempStores)) {
            $output->writeln("Updating store...");

            foreach ($tempStores as $storeListNodeId => $storeDetails) {

                foreach ($storeDetails as $store) {
                    $cmsManager->saveStore(
                        $store['storeId'],
                        $storeListNodeId,
                        $store['productIds'],
                        true,
                        $store['storeId']
                    );
                }
            }

            $output->writeln("Stores are now updated.");
        }
        else {
            $output->writeln("Store: Nothing to update.");
        }

    }

    /**
     * Update Brands
     *
     * @param OutputInterface $output
     */
    public function updateBrands(OutputInterface $output)
    {
        $container = $this->getContainer();
        $cmsManager = $container->get('yilinker_backend.cms_manager');
        $file = $cmsManager->getTempJsonFile(CmsManager::TOP_BRANDS_JSON_FILE_NAME);
        $tempBrands = json_decode($file, true);

        if (!is_null($tempBrands)) {
            $output->writeln("Updating brands...");

            foreach ($tempBrands as $brands) {
                $cmsManager->saveBrand($brands, true);
            }

            $output->writeln("Top brands are now updated.");
        }
        else {
            $output->writeln("Brands: Nothing to update.");
        }

    }

}