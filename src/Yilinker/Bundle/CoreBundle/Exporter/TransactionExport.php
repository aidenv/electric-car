<?php

namespace Yilinker\Bundle\CoreBundle\Exporter;

use Carbon\Carbon;

class TransactionExport
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function export($options)
    {
        $container = $this->container;

        $em = $container->get('doctrine')->getEntityManager();

        $datefrom = date('Y-m-d', strtotime($options["datefrom"]));
        $dateto = date('Y-m-d', strtotime($options['dateto']));

        $conn = $em->getConnection();

        $sql = "
            SELECT
                DISTINCT
                UserOrder.`date_added`,
                PK.`waybill_number`,
                PKS.`name` AS 'Waybill_Status',
                Product.`name`  as 'product_name',
                CONCAT('https://www.yilinker.com/item/', Product.`slug`) AS 'url',
                OrderStatus.`name` AS `order_status`,
                OrderProductStatus.`name`                                                 AS 'order_product_status',
                PaymentMethod.`name` AS `payment_type`,
                PaymentMethod.`payment_method_id` AS `payment_type_id`,
                OrderStatus.`order_status_id` AS `order_status_id`,
                OrderVoucher.value as `totalVoucherAmount`,

                OrderProduct.`order_product_id`,
                OrderProduct.`sku`,
                OrderProduct.`attributes`,
                OrderProduct.`quantity`,
                OrderProduct.`orig_price`,
                OrderProduct.`unit_price`,
                OrderProduct.`total_price`,
                UserOrder.`order_id`,
                UserOrder.`buyer_id`,
                UserOrder.`order_status_id`,
                UserOrder.`payment_method_id`,
                UserOrder.`invoice_number`,
                UserOrder.`net`,
                UserOrder.`total_price`,
                UserOrder.`additional_cost`,
                UserOrder.`yilinker_charge`,
                UserOrder.`handling_fee`,
                UserOrder.`consignee_name`,
                UserOrder.`additional_cost` + UserOrder.`yilinker_charge` + UserOrder.`handling_fee` as `total_charges`,
                UserOrder.`consignee_contact_number`,
                UserOrderFlagged.`flag_reason`,
                UserOrderFlagged.`status` AS `flag_status`,
                UserOrderFlagged.`remarks` AS `flag_remarks`,
                UserOrderFlagged.`user_order_flagged_id` AS `flag_id`,
                CONCAT(`flagUser`.`firstname`, ' ', `flagUser`.`lastname`) AS `flag_user`,
                UserOrderFlagged.`date_remarked` AS `flag_remark_date`,
                

                CONCAT(User.`first_name`, ' ', User.`last_name`) AS `buyerName`,
                User.`email` AS 'Buyer_Email',
                UserOrder.`address`,
                User.contact_number AS `contactNumber`,

                u.`email`                                                               AS 'SellerEmail',
                u.`contact_number`                                                      AS 'SellerContact',
                u.`first_name`                                                          AS 'SellerFName',
                u.`last_name`                                                           AS 'SellerLName',

                PC.`name`                                                                 AS 'currentcategoryname',
                  IF(PC3.`product_category_id` IS NOT NULL, PC3.`name`,
                     IF(PC2.`product_category_id` IS NOT NULL, PC2.`name`,
                        IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`)))  AS 'grandparentcategory',
                  IF(PC2.`product_category_id` IS NOT NULL, PC2.`name`,
                     IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`))      AS 'parentcategory',
                  IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`)          AS 'childcategory',
                  PC.`name`                                                                 AS 'grandchildcategory'

                
            FROM UserOrder
            INNER JOIN OrderStatus
                ON OrderStatus.`order_status_id` = UserOrder.`order_status_id`
            INNER JOIN PaymentMethod
                ON PaymentMethod.`payment_method_id` = UserOrder.`payment_method_id`
            INNER JOIN User
              ON User.`user_id` = UserOrder.`buyer_id`
            LEFT JOIN UserOrderFlagged
              ON UserOrder.`user_order_flagged_id` = `UserOrderFlagged`.`user_order_flagged_id`
            LEFT JOIN AdminUser AS `flagUser`
              ON `flagUser`.`admin_user_id` = UserOrderFlagged.`admin_user_id`
            INNER JOIN OrderProduct
                ON OrderProduct.order_id = UserOrder.order_id

            LEFT JOIN `Package` AS PK ON PK.`order_id` = OrderProduct.`order_id`
            LEFT JOIN `PackageDetail` AS PKD ON PKD.`package_id` = PK.`package_id`
                                                                AND PKD.`order_product_id` = OrderProduct.`order_product_id`
            LEFT JOIN `PackageStatus` AS PKS ON PK.`package_status_id` = PKS.`package_status_id`
            LEFT JOIN `PackageHistory` AS PH
            ON PH.`package_id` = PK.`package_id` AND PH.`package_status_id` = 90
  
            INNER JOIN Product
                ON OrderProduct.product_id = Product.product_id
            
            LEFT JOIN `ProductCategory` AS PC ON PC.`product_category_id` = Product.`product_category_id`
            LEFT JOIN `ProductCategory` AS PC1 ON PC1.`product_category_id` = PC.`parent_id`
                                                                  AND PC.`product_category_id` > 1
            LEFT JOIN `ProductCategory` AS PC2 ON PC2.`product_category_id` = PC1.`parent_id`
                                                                  AND PC1.`product_category_id` > 1
            LEFT JOIN `ProductCategory` AS PC3 ON PC3.`product_category_id` = PC2.`parent_id`
                                                                  AND PC2.`product_category_id` > 1

            LEFT JOIN OrderProductStatus
                ON OrderProduct.order_product_status_id = OrderProductStatus.order_product_status_id
            LEFT JOIN OrderVoucher
                ON OrderVoucher.order_id = UserOrder.order_id

            JOIN User u on u.user_id = OrderProduct.seller_id
            WHERE
                UserOrder.order_id > 0
                
        ";
     
        if ($datefrom !== null) {
            $sql .= " AND UserOrder.date_added >= '$datefrom'";
        }

        if ($dateto !== null) {
            $sql .= " AND UserOrder.date_added <= '$dateto'";
        }

      
        $sql .= "
            GROUP BY UserOrder.order_id
            ORDER BY PC.`name` ASC
        ";


        try {
            $transactions = $conn->fetchAll($sql);

            $report = $this->convertToXls($transactions);

            return $report;
        } catch (\Exception $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }

    }

    public function convertToXls($transactions)
    {
        $container = $this->container;
        $phpExcelObject = $container->get('phpexcel')
                                    ->createPHPExcelObject();
        
        $path = $container->get('kernel')->locateResource('@YilinkerCoreBundle/Resources/reports/marketing');
        
        $writer = $container->get('phpexcel')
                            ->createWriter($phpExcelObject, 'Excel5');

        $title = "transaction-report-".time();
        $phpExcelObject->getProperties()
                       ->setSubject("Marketing Report")
                       ->setDescription("Marketing Report")
                       ->setTitle($title);

        $rowCounter = "3";
        $phpExcelObject->setActiveSheetIndex(0)
                       ->setCellValue('A1', "Report generated on: ")
                       ;

        $phpExcelObject->setActiveSheetIndex(0)
                           ->setCellValue('A2', 'DATE ADDED')
                           ->setCellValue('B2', 'WAYBILL NUMBER')
                           ->setCellValue('C2', 'WAYBILL STATUS')
                           ->setCellValue('D2', 'INVOICE NUMBER')
                           ->setCellValue('Z2', 'PRODUCT NAME')
                           ->setCellValue('E2', 'URL')
                           ->setCellValue('F2', 'ORDER STATUS')
                           ->setCellValue('G2', 'ORDER PRODUCT STATUS')
                           ->setCellValue('H2', 'PAYMENT TYPE')
                           ->setCellValue('I2', 'TOTAL VOUCHER AMOUNT')
                           ->setCellValue('J2', 'SKU')
                           ->setCellValue('K2', 'ATTRIBUTES')
                           ->setCellValue('L2', 'QUANTITY')
                           ->setCellValue('M2', 'ORIGINAL PRICE')
                           ->setCellValue('N2', 'UNIT PRICE')
                           ->setCellValue('O2', 'TOTAL PRICE')
                           ->setCellValue('P2', 'ADDITIONAL COST')
                           ->setCellValue('Q2', 'YILINKER CHARGE')
                           ->setCellValue('R2', 'HANDLING FEE')
                           ->setCellValue('S2', 'CONSIGNEE NAME')
                           ->setCellValue('T2', 'TOTAL CHARGES')
                           ->setCellValue('U2', 'consignee_contact_number')
                           ->setCellValue('V2', 'FLAG REASON')
                           ->setCellValue('W2', 'FLAG STATUS')
                           ->setCellValue('X2', 'FLAG REMARKS')
                           ->setCellValue('Y2', 'FLAG USER')
                           
                           ->setCellValue('AA2', 'buyerName')
                           ->setCellValue('AB2', 'Buyer_Email')
                           ->setCellValue('AC2', 'address')
                           ->setCellValue('AD2', 'SellerEmail')
                           ->setCellValue('AE2', 'SellerContact')
                           ->setCellValue('AF2', 'SellerFName')
                           ->setCellValue('AG2', 'SellerLName')
                           ->setCellValue('AH2', 'GRAND CHILD CATEGORY')
                           ->setCellValue('AL2', 'CURRENT CATEGORY NAME')
                           ->setCellValue('AI2', 'GRANT PARENT CATEGORY')
                           ->setCellValue('AJ2', 'PARENT CATEGORY')
                           ->setCellValue('AK2', 'CHILD CATEGORY')
                           ->setCellValue('AN2', 'Marketing Commission (MC) %')
                           ->setCellValue('AO2', 'Add’l Charges (AC) %')
                           ->setCellValue('AP2', 'Freight Charges  (FC)')
                           ->setCellValue('AR2', 'Transaction Amount')
                           ->setCellValue('AS2', 'Marketing Commission (MC)')
                           ->setCellValue('AT2', 'Addtl Charges (AC)')
                           ->setCellValue('AU2', 'Freight Charges  (FC)')
                           ->setCellValue('AV2', 'Total Charges')
                           ->setCellValue('AW2', 'Receivable')
                            ;
                           

        foreach($transactions as $key => $dataRow){

    
            $phpExcelObject->setActiveSheetIndex(0)
                           ->setCellValue('A'.$rowCounter, $dataRow['date_added'])
                           ->setCellValue('B'.$rowCounter, $dataRow['waybill_number'])
                           ->setCellValue('C'.$rowCounter, $dataRow['Waybill_Status'])
                           ->setCellValue('D'.$rowCounter, $dataRow['invoice_number'])
                           ->setCellValue('Z'.$rowCounter, $dataRow['product_name'])
                           ->setCellValue('E'.$rowCounter, $dataRow['url'])
                           ->setCellValue('F'.$rowCounter, $dataRow['order_status'])
                           ->setCellValue('G'.$rowCounter, $dataRow['order_product_status'])
                           ->setCellValue('H'.$rowCounter, $dataRow['payment_type'])
                           ->setCellValue('I'.$rowCounter, $dataRow['totalVoucherAmount'])
                           ->setCellValue('J'.$rowCounter, $dataRow['sku'])
                           ->setCellValue('K'.$rowCounter, $dataRow['attributes'])
                           ->setCellValue('L'.$rowCounter, $dataRow['quantity'])
                           ->setCellValue('M'.$rowCounter, $dataRow['orig_price'])
                           ->setCellValue('N'.$rowCounter, $dataRow['unit_price'])
                           ->setCellValue('O'.$rowCounter, $dataRow['total_price'])
                           ->setCellValue('P'.$rowCounter, $dataRow['additional_cost'])
                           ->setCellValue('Q'.$rowCounter, $dataRow['yilinker_charge'])
                           ->setCellValue('R'.$rowCounter, $dataRow['handling_fee'])
                           ->setCellValue('S'.$rowCounter, $dataRow['consignee_name'])
                           ->setCellValue('T'.$rowCounter, $dataRow['total_charges'])
                           ->setCellValue('U'.$rowCounter, $dataRow['consignee_contact_number'])
                           ->setCellValue('V'.$rowCounter, $dataRow['flag_reason'])
                           ->setCellValue('W'.$rowCounter, $dataRow['flag_status'])
                           ->setCellValue('X'.$rowCounter, $dataRow['flag_remarks'])
                           ->setCellValue('Y'.$rowCounter, $dataRow['flag_user'])
                           
                           ->setCellValue('AA'.$rowCounter, $dataRow['buyerName'])
                           ->setCellValue('AB'.$rowCounter, $dataRow['Buyer_Email'])
                           ->setCellValue('AC'.$rowCounter, $dataRow['address'])
                           ->setCellValue('AD'.$rowCounter, $dataRow['SellerEmail'])
                           ->setCellValue('AE'.$rowCounter, $dataRow['SellerContact'])
                           ->setCellValue('AF'.$rowCounter, $dataRow['SellerFName'])
                           ->setCellValue('AG'.$rowCounter, $dataRow['SellerLName'])
                           
                           ->setCellValue('AI'.$rowCounter, $dataRow['grandparentcategory'])
                           ->setCellValue('AJ'.$rowCounter, $dataRow['parentcategory'])
                           ->setCellValue('AK'.$rowCounter, $dataRow['childcategory'])
                           ->setCellValue('AH'.$rowCounter, $dataRow['grandchildcategory'])
                           ->setCellValue('AL'.$rowCounter, $dataRow['currentcategoryname'])
                           ->setCellValue('AR'.$rowCounter, $dataRow['total_price'])
                           ->setCellValue('AS'.$rowCounter, '=AR'.$rowCounter. '*(AN'.$rowCounter. '/100)')
                           ->setCellValue('AT'.$rowCounter, '=AR'.$rowCounter. '*(AO'.$rowCounter.'/100)')
                           ->setCellValue('AU'.$rowCounter, '=AP'.$rowCounter)
                           ->setCellValue('AV'.$rowCounter, '=AS'.$rowCounter. '+AT'.$rowCounter. '+AU'.$rowCounter)
                           ->setCellValue('AW'.$rowCounter, '=AR'.$rowCounter. '-AV'.$rowCounter)
                           
            ;


            $rowCounter++;
        }

        foreach (range('Z', $phpExcelObject->getActiveSheet()->getHighestDataColumn()) as $col) {
            $phpExcelObject->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
        }

        $phpExcelObject->getActiveSheet()->getColumnDimension('AA')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AB')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AC')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AD')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AE')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AF')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AG')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AH')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AI')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AJ')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AJ')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AK')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AL')->setAutoSize(true);

        $phpExcelObject->getActiveSheet()->getColumnDimension('AM')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AN')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AO')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AP')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AQ')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AR')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AS')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AT')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AU')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AV')->setAutoSize(true);
        $phpExcelObject->getActiveSheet()->getColumnDimension('AW')->setAutoSize(true);
        
        $sheet = $phpExcelObject->getActiveSheet();

        $sheet
        ->getStyle('AN2:AP2')
        ->getFill()
        ->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)
        ->getStartColor()
        ->setARGB('E05CC2');

        $filename = $path.DIRECTORY_SEPARATOR.$title.".xls";
        $writer->save($filename);
        chmod($filename, 0777);

        
        return $title.".xls";
    }



}