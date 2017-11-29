<?php

namespace Yilinker\Bundle\CoreBundle\Command\Report;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\ORM\Query\ResultSetMapping;

class ProductStockReportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('yilinker-report:product:stock')
            ->setDescription('Emails product stocks')
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED + InputOption::VALUE_IS_ARRAY,
                'Email Recipient'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filepath = $this->getFilePath();
        $handle = fopen($filepath, 'w+');
        fputcsv($handle, array('Date', 'Product Name', 'SKU', 'Product URL', 'Inventory', 'Supplier'));

        $stmt = $this->getSQLAffiliate();
        $this->record($stmt, $handle);

        $stmt = $this->getSQLSeller();
        $this->record($stmt, $handle);
        fclose($handle);

        $this->mail($input, $output);
    }

    private function record($stmt, $handle)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $rsm = $this->getResultMap();
        $offset = 0;
        $limit = 3000;
        $result = array();
        do {
            foreach ($result as $row) {
                $row['date_last_modified'] = date('M d, Y h:i A', strtotime($row['date_last_modified']));
                fputcsv($handle, $row);
            }

            $query = $em->createNativeQuery("$stmt LIMIT $limit OFFSET $offset", $rsm);
            $result = $query->getResult();
            $offset += $limit;
        } while ($result);
    }

    private function mail($input, $output)
    {
        $mailer = $this->getContainer()->get('mailer');
        $mailerEmail = $this->getContainer()->getParameter('mailer_user');
        $ccDeveloper = $this->getContainer()->getParameter('reports_dev_email');
        $filepath = $this->getFilePath();
        $emailRecipients = $input->getOption('email', array());

        $message = \Swift_Message::newInstance();
        $message
            ->setSubject('Inventory Alert as of '.date('M d, Y'))
            ->setFrom($mailerEmail)
            ->addCc($ccDeveloper)
            ->setTo($emailRecipients)
            ->attach(\Swift_Attachment::fromPath($filepath))
        ;
        $mailer->send($message);

        $output->writeln("Inventory Stock Report emailed to ".implode($emailRecipients, ', '));
    }

    private function getFilePath()
    {
        $path = 'files/'.date('Ymd');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $filepath = $path.'/inventory-stock-'.date('Ymd').'.csv';

        return $filepath;
    }

    private function getResultMap()
    {
        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('date_last_modified', 'date_last_modified');
        $rsm->addScalarResult('name', 'name');
        $rsm->addScalarResult('sku', 'sku');
        $rsm->addScalarResult('slug', 'slug');
        $rsm->addScalarResult('quantity', 'quantity');
        $rsm->addScalarResult('Supplier', 'Supplier');

        return $rsm;
    }

    private function getSQLAffiliate()
    {
        $sql = "
            SELECT 
                `ManufacturerProductUnit`.`date_last_modified`,
                `Product`.`name`, 
                `ManufacturerProductUnit`.`sku`, 
                CONCAT('http://www.yilinker.com/item/', `Product`.`slug`) AS 'slug', 
                `ManufacturerProductUnit`.`quantity`, 
                `Manufacturer`.`name` AS 'Supplier' 
            FROM 
                `ManufacturerProductUnit` 
            INNER JOIN 
                `ManufacturerProduct` 
            ON 
                `ManufacturerProduct`.`manufacturer_product_id` = `ManufacturerProductUnit`.`manufacturer_product_id` 
            INNER JOIN 
                `Manufacturer` 
            ON 
                `Manufacturer`.`manufacturer_id` = `ManufacturerProduct`.`manufacturer_id` 
            INNER JOIN 
                `ManufacturerProductMap` 
            ON 
                `ManufacturerProductMap`.`manufacturer_product_id` = `ManufacturerProduct`.`manufacturer_product_id` 
            INNER JOIN 
                `Product` 
            ON 
                `Product`.`product_id` = `ManufacturerProductMap`.`product_id` 
            INNER JOIN 
                `ProductCountry` 
            ON 
                `Product`.`product_id` = `ProductCountry`.`product_id` 
            WHERE 
                `ManufacturerProductUnit`.`quantity` <= 2 
            AND 
                `ProductCountry`.`status` IN (2,6) 
            GROUP BY 
                `ManufacturerProductUnit`.`sku`
            ORDER BY
                `ManufacturerProductUnit`.`date_last_modified` DESC
        ";

        return $sql;
    }

    private function getSQLSeller()
    {
        $sql = "
            SELECT 
                `ProductUnit`.`date_last_modified`, 
                `Product`.`name`, 
                `ProductUnit`.`sku`, 
                CONCAT('http://www.yilinker.com/item/', `Product`.`slug`) AS 'slug', 
                `ProductUnitWarehouse`.`quantity`, 
                CONCAT(`User`.`first_name`, ' ', `User`.`last_name`) AS 'Supplier'
            FROM 
                `ProductUnit` 
            LEFT JOIN 
                `ManufacturerProductUnitMap` 
            ON 
                `ProductUnit`.`product_unit_id` = `ManufacturerProductUnitMap`.`product_unit_id` 
            INNER JOIN 
                `Product` 
            ON 
                `Product`.`product_id` = `ProductUnit`.`product_id` 
            INNER JOIN 
                `ProductCountry` 
            ON 
                `Product`.`product_id` = `ProductCountry`.`product_id` 
            AND 
                `ProductCountry`.`status` IN (2,6) 
            INNER JOIN 
                `User` 
            ON 
                `Product`.`user_id` = `User`.`user_id` 
            INNER JOIN 
                `ProductUnitWarehouse` 
            ON 
                `ProductUnit`.`product_unit_id` = `ProductUnitWarehouse`.`product_unit_id` 
            INNER JOIN 
                `UserWarehouse` 
            ON 
                `ProductUnitWarehouse`.`user_warehouse_id` = `UserWarehouse`.`user_warehouse_id` 
            INNER JOIN 
                `ProductWarehouse` 
            ON 
                `ProductWarehouse`.`user_warehouse_id` = `UserWarehouse`.`user_warehouse_id` 
            WHERE 
                `ManufacturerProductUnitMap`.`manufacturer_product_unit_map_id` IS NULL 
            AND 
                `ProductUnitWarehouse`.`quantity` <= 2 
            AND 
                `ProductWarehouse`.`priority` = 1 
            GROUP BY 
                `ProductUnit`.`product_unit_id` 
            ORDER BY 
                `ProductUnit`.`product_id`
        ";

        return $sql;
    }
}