#!/usr/bin/php
<?php

if (!file_exists( __DIR__ . "/config.ini")) {
  exit("please create config.ini \n");
}

$config_file = parse_ini_file("config.ini");

$user=  $config_file['user'];
$pass=  $config_file['pass'];
$host= $config_file['host'];
$db= $config_file['db'];

$to= $config_file['to'];
$cc=$config_file['cc'];
$from= $config_file['from'];
$subject=$config_file['subject'];
$body=<<<EOF
Daily Transaction Report
EOF;

$script_path = $config_file['script_path'];

$filepath=$config_file['attachment_path'];

try {
    $dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    
    if (isset($argv[1])) { 
        $subject="Monthly_Transaction_Report";
        $body = "monthly_report";

        $prevDate = date('Y-m-d', strtotime(' -1 month'));
    } else {
        $prevDate = date('Y-m-d', strtotime(' -1 day'));
    }

    $fromdate = $prevDate. ' 00:00:00';
    $todate = $prevDate. ' 23:59:59';

    $sql = "
    SELECT
          DISTINCT
          PK.`waybill_number`,
          PKS.`name`                                                                AS 'Waybill Status',
          UO.`date_added`,
          CONCAT('\'', UO.`invoice_number`)                                         AS 'invoice_number',
          P.`name`,
          CONCAT('https://www.yilinker.com/item/', P.`slug`)                        AS 'url',
          OP.`order_product_id`,
          OP.`sku`,
          OP.`attributes`,
          OP.`quantity`,
          OP.`orig_price`,
          OP.`unit_price`,
          OP.`total_price`,
          US.`name`                                                                 AS 'order status',
          OPS.`name`                                                                AS 'order product status',
          PM.`name`                                                                 AS 'payment method',
          U_B.`email`                                                               AS 'Buyer Email',
          U_B.`contact_number`                                                      AS 'Buyer Contact',
          U_B.`first_name`                                                          AS 'Buyer FName',
          U_B.`last_name`                                                           AS 'Buyer LName',
          U_S.`email`                                                               AS 'Seller Email',
          U_S.`contact_number`                                                      AS 'Seller Contact',
          U_S.`first_name`                                                          AS 'Seller FName',
          U_S.`last_name`                                                           AS 'Seller LName',
          ST.`store_name`,
          IF(`store_type` = 0, 'Seller', 'Affiliate')                               AS 'Seller Type',
          XP.`id`                                                                   AS 'express_product_id',
          XP.`sku`                                                                  AS 'WH SKU',
          COALESCE(BP1.`current_inventory`, 0)                                      AS 'WH Inventory',
          M.`name`                                                                  AS 'Supplier',
          IF(SC.`company_name_US` = '', SC.`company_name_CN`, SC.`company_name_US`) AS 'supplier2',
          M.`contact_number`                                                        AS 'Supplier Contact',
          PC.`product_category_id`                                                  AS 'current category id',
          PC.`name`                                                                 AS 'current category name',
          IF(PC3.`product_category_id` IS NOT NULL, PC3.`name`,
             IF(PC2.`product_category_id` IS NOT NULL, PC2.`name`,
                IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`)))  AS 'grandparent category',
          IF(PC2.`product_category_id` IS NOT NULL, PC2.`name`,
             IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`))      AS 'parent category',
          IF(PC1.`product_category_id` IS NOT NULL, PC1.`name`, PC.`name`)          AS 'child category',
          PC.`name`                                                                 AS 'grandchild category',
          PC.`yilinker_charge`,
          PC.`handling_fee`,
          PC.`additional_cost`,
          PH.`date_added`                                                           AS 'buyer_received_date',
          MPU.`commission`,
          SP.`supplier_product_original_price`,
          SP.`supplier_product_discount`,
          SP.`supplier_product_final_price`,
      POI.`unit_original_price`                                                 AS 'purchase order item unit original price',
      POI.`unit_final_price`                                                    AS 'purchase order item unit final price',
          CUR.`Currency_Value_US`
        FROM
          `yilinker_online`.`OrderStatus` AS US, `yilinker_online`.`OrderProductStatus` AS OPS,
          `yilinker_online`.`PaymentMethod` AS PM,
          `yilinker_online`.`User` AS U_B, `yilinker_online`.`User` AS U_S, `yilinker_online`.`Store` AS ST,
          `yilinker_online`.`UserOrder` AS UO,
          `yilinker_online`.`OrderProduct` AS OP
          LEFT JOIN `yilinker_online`.`Package` AS PK ON PK.`order_id` = OP.`order_id`
          LEFT JOIN `yilinker_online`.`PackageDetail` AS PKD ON PKD.`package_id` = PK.`package_id`
                                                                AND PKD.`order_product_id` = OP.`order_product_id`
          LEFT JOIN `yilinker_online`.`PackageStatus` AS PKS ON PK.`package_status_id` = PKS.`package_status_id`
          LEFT JOIN `yilinker_online`.`PackageHistory` AS PH
            ON PH.`package_id` = PK.`package_id` AND PH.`package_status_id` = 90
          ,
          `yilinker_online`.`Product` AS P
          LEFT JOIN `yilinker_online`.`ManufacturerProductMap` AS MPM ON MPM.`product_id` = P.`product_id`
          LEFT JOIN `yilinker_online`.`ManufacturerProduct` AS MP
            ON MP.`manufacturer_product_id` = MPM.`manufacturer_product_id`
          LEFT JOIN `yilinker_online`.`Manufacturer` AS M ON M.`manufacturer_id` = MP.`manufacturer_id`
          LEFT JOIN `yilinker_online`.`ManufacturerProductUnit` AS MPU
            ON MPM.`manufacturer_product_id` = MPU.`manufacturer_product_id`
          LEFT JOIN `yilinker_express`.`product` AS XP ON XP.`reference_2` = MPU.`manufacturer_product_unit_id`
          LEFT JOIN `yilinker_express`.`branch_product_inventory` AS BP1 ON BP1.`product_id` = XP.`id` AND BP1.`branch_id` = 4
          LEFT JOIN `yilinker_trading`.`supplierproduct` AS SP ON MPU.`reference_id` = SP.`supplier_product_id`
          LEFT JOIN `yilinker_trading`.`purchaseorderitem` AS POI on SP.`supplier_product_id` = POI.`supplier_product_id`
      LEFT JOIN `yilinker_trading`.`supplierinfo` AS SI ON SI.`supplier_id` = SP.`supplier_id`
          LEFT JOIN `yilinker_trading`.`suppliercompany` SC ON SC.`supplier_company_id` = SI.`supplier_company_id`
          LEFT JOIN `yilinker_trading`.`currency` AS CUR ON CUR.`currency_id` = SP.`currency_id`
          LEFT JOIN `yilinker_online`.`ProductCategory` AS PC ON PC.`product_category_id` = P.`product_category_id`
          LEFT JOIN `yilinker_online`.`ProductCategory` AS PC1 ON PC1.`product_category_id` = PC.`parent_id`
                                                                  AND PC.`product_category_id` > 1
          LEFT JOIN `yilinker_online`.`ProductCategory` AS PC2 ON PC2.`product_category_id` = PC1.`parent_id`
                                                                  AND PC1.`product_category_id` > 1
          LEFT JOIN `yilinker_online`.`ProductCategory` AS PC3 ON PC3.`product_category_id` = PC2.`parent_id`
                                                                  AND PC2.`product_category_id` > 1
        WHERE OP.`order_id` = UO.`order_id`
              AND US.`order_status_id` = UO.`order_status_id`
              AND OPS.`order_product_status_id` = OP.`order_product_status_id`
              AND PM.`payment_method_id` = UO.`payment_method_id`
              AND P.`product_id` = OP.`product_id`
              AND U_B.`user_id` = UO.`buyer_id`
              AND U_S.`user_id` = OP.`seller_id`
              AND ST.`user_id` = U_S.`user_id`
              AND ((MPU.`manufacturer_product_unit_id` = OP.`manufacturer_product_unit_id`) OR
                   (MPU.`manufacturer_product_unit_id` IS NULL))
              AND ((PK.`order_id` IS NOT NULL AND PKD.`package_id` IS NOT NULL) OR
                   (PK.`order_id` IS NULL AND PKD.`package_id` IS NULL))
              AND P.`product_id` NOT IN (12290)
              AND UO.`date_added` BETWEEN '{$fromdate}' AND '$todate';";
    
   // echo ($sql);
   $sql2 = "
        SELECT * FROM `yilinker_trading`.`supplierproduct` LIMIT 1;
    ";   
 
    $stmt = $dbh->prepare($sql);

    $stmt->execute();
    
    $title = time();
    $filename = "transactionDetails_{$title}.csv";
    $attachment = $filepath.$filename;

    //$fp = fopen('php://output', 'w');
    $fp = fopen($attachment, 'w');

    //header('Content-Type: text/csv');

    fputcsv($fp, array('WAYBILL NUMBER','STATUS', 'DATE ADDED', 'INVOICE NUMBER', 'PRODUCT NAME', 'PRODUCT URL', 'ORDER PRODUCT ID', 'SKU', 'ATTRIBUTES', 'QUANTITY', 'ORIG PRICE', 'UNIT PRICE', 'TOTAL PRICE', 'ORDER STATUS', 'ORDER PRODUCT STATUS', 'PAYMENT METHOD', 'BUYER EMAIL', 'BUYER CONTACT NUMBER', 'BUYER FNAME', 'BUYER LNAME', 'SELLER EMAIL', 'SELLECT CONTACT NUMBER', 'SELLER FNAME', 'SELLER LNAME', 'STORE NAME', 'SELLER TYPE', 'EXPRESS PRODUCT ID', 'WH SKU', 'WH INVENTORY', 'SUPPLIER', 'SUPLLLIER 2', 'SUPPLIER CONTACT', 'CURRENT CATEGORY ID', 'CURRENT CATEGORY NAME', 'GRAND PARENT CATEGORY', 'PARENT CATEGORY', 'CHLD CATEGORY', 'GRANDCHILD CATEGORY', 'YILINKER CHARGE', 'HANDLING FEE', 'ADDITIONAL FEE', 'DATE ADDED', 'COMMISSION', 'SUPPLIER PRODUCT ORIGINAL PRICE', 'SUPPLIER PRODUCT DISCCOUNT', 'SUPPLIER PRODUCT FINAL PRICE','PURCHASE_ORDER_ITEM UNIT ORIGINAL PRICE','PURCHASE_ORDER_OTEM UNIT FINAL PRICE', 'CURRENCY VALUE'));   
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($fp, array_values($row));
    }

    fclose($fp);
    $subject.= "-{$prevDate}";

    $exec = "php $script_path yilinker:email:send --from=$from --to=$to --subject={$subject} --cc=$cc --attachment=$attachment --body='{$body}'";
    $h = shell_exec($exec);
    echo $h;

    if (file_exists($attachment)) {
        unlink($attachment);
    }

    $dbh = null;
} catch (\Exception $e) {
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>