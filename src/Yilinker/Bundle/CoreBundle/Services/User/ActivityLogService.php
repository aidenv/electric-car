<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

use Symfony\Component\Yaml\Parser;
use Yilinker\Bundle\CoreBundle\Entity\OrderProductStatus;
use Yilinker\Bundle\CoreBundle\Entity\Store;

class ActivityLogService
{
    protected $em;
    protected $container;
    protected $config;

    public function __construct($em, $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->config = $this->initConfig();
    }

    public function getTablesToLog()
    {
        if (array_key_exists('tables', $this->config)) {
            return array_keys($this->config['tables']);
        }

        return array();
    }

    public function isLoggableEntity($entity, $method = null)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $table = $metadata->table['name'];
        $hasTableConfig = array_search($table, $this->getTablesToLog()) > -1;
        if (!is_null($method) && $hasTableConfig) {
            $tableConfig = $this->getEntityConfig($entity);
            $actionConfig = $this->getActionConfig($tableConfig, $method);
            if ($actionConfig && array_key_exists('loggable', $actionConfig)) {
                $loggable = $actionConfig['loggable'];
                if (is_string($loggable) && method_exists($entity, $loggable)) {
                    $loggable =  call_user_func_array(array($entity, $loggable), array());
                }
                else if(is_string($loggable) && method_exists($this, $loggable)) {
                    $loggable =  call_user_func_array(array($this, $loggable), array($entity));
                }

                return $loggable;
            }
        }

        return $hasTableConfig;
    }

    public function isLoggableColumn($entity, $column, $method = null)
    {
        if (!$this->isLoggableEntity($entity, $method)) {
            return false;
        }

        $entityConfig = $this->getEntityConfig($entity);
        if (array_key_exists('fields', $entityConfig) && 
            array_key_exists($column, $entityConfig['fields']) &&
            array_key_exists('loggable', $entityConfig['fields'][$column])) {
            return $entityConfig['fields'][$column]['loggable'];
        }
        else {
            return true;
        }
    }

    public function getInsertTemplate($activity)
    {
        $tableName = $activity->getAffectedTable();
        $tableConfig = $this->getTableConfig($tableName);
        
        $actionConfig = $this->getActionConfig($tableConfig, 'insert');
        if ($actionConfig && array_key_exists('template', $actionConfig)) {
            return $actionConfig['template'];
        }

        return null;
    }

    /**
     * @return $template - twig template that will be used when
     *                     the activity action is UPDATE
     */
    public function getUpdateTemplate($activity)
    {
        $changes = $activity->getChanges();
        $tableName = $activity->getAffectedTable();
        $template = null;
        foreach ($changes as $column => $change) {
            $columnConfig = $this->getColumnConfig($tableName, $column);
            if ($columnConfig && array_key_exists('template', $columnConfig)) {
                $template = $columnConfig['template'];
                continue;
            }
        }

        return $template;
    }

    public function getTemplate($activity)
    {
        $mysqlAction = strtolower($activity->getMysqlAction());
        $template = null;
        if ($mysqlAction == 'insert') {
            $template = $this->getInsertTemplate($activity);
        }
        elseif ($mysqlAction == 'update') {
            $template = $this->getUpdateTemplate($activity);
        }

        if (!$template) {
            $tableName = $activity->getAffectedTable();
            $tableConfig = $this->getTableConfig($tableName);
            if (array_key_exists('template', $tableConfig)) {
                $template = $tableConfig['template'];
            }
            elseif (array_key_exists('template', $this->config)) {
                $template = $this->config['template'];
            }
        }

        return $template;
    }

    public function getEntityConfig($entity)
    {
        $metadata = $this->em->getClassMetadata(get_class($entity));
        $tableName = $metadata->table['name'];

        return $this->getTableConfig($tableName);
    }


    public function getColumnConfig($entityOrTableName, $column)
    {
        if (is_string($entityOrTableName)) {
            $entityConfig = $this->getTableConfig($entityOrTableName);
        }
        else {
            $entityConfig = $this->getEntityConfig($entityOrTableName);
        }

        if (array_key_exists('fields', $entityConfig) && array_key_exists($column, $entityConfig['fields'])) {
            return $entityConfig['fields'][$column];
        }

        return array();
    }

    public function getTableConfig($tableName)
    {
        return array_key_exists($tableName, $this->config['tables']) ? $this->config['tables'][$tableName]: array();
    }

    public function getActionConfig($tableConfig, $method)
    {
        $actionKey = '__'.strtolower($method);
        if (array_key_exists($actionKey, $tableConfig)) {
            $actionConfig = $tableConfig[$actionKey];
            return $actionConfig;
        }

        return null;
    }

    public function activityView($activity)
    {
        $tableName = $activity->getAffectedTable();
        $tableConfig = $this->getTableConfig($tableName);
        $tableConfig['activity'] = $activity;
        $template = $this->getTemplate($activity);

        return $this->container->get('twig')->render($template, $tableConfig);
    }

    public function getActivitiesOfUser($userId, $page = null, $perPage = 10, $minimalist = false)
    {

        $tbUserActivity = $this->em->getRepository('YilinkerCoreBundle:UserActivityHistory');
        $activities = $tbUserActivity->createActivitiesQuery(
                                            $userId, 
                                            $page, 
                                            $perPage
                                        )
                                     ->getResult();
        if ($minimalist) {
            foreach ($activities as &$activity) {
                $activity = $this->minimalistActivity($activity);
            }
        }

        return $activities;
    }

    public function minimalistActivity($activity)
    {
        $template = $this->getTemplate($activity);
        $data = array();
        $data['date'] = $activity->getDateAdded();
        $activityData = $activity->getActivityData();
        $assetHelper = $this->container->get('templating.helper.assets');
        if ($template == 'YilinkerCoreBundle:ActivityLog:login.html.twig') {
            $data['type'] = 'user_login';
            $data['text'] = 'Logged in';
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:logout.html.twig') {
            $data['type'] = 'user_logout';
            $data['text'] = 'Logged out';
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:user_primary.html.twig') {
            $data['type'] = 'change_primary';
            $data['text'] = 'Changed Profile Photo';
            $primaryImageChanges = $activityData['__changes']['primaryImage'];
            $beforePrimaryImage = array_shift($primaryImageChanges);
            $afterPrimaryImage = array_shift($primaryImageChanges);

            $data['beforePrimaryImage'] = $beforePrimaryImage['userImageId'].'/'.$beforePrimaryImage['imageLocation'];
            $data['afterPrimaryImage'] = $afterPrimaryImage['userImageId'].'/'.$afterPrimaryImage['imageLocation'];
            $data['beforePrimaryImage'] = $assetHelper->getUrl($data['beforePrimaryImage'], 'user');
            $data['afterPrimaryImage'] = $assetHelper->getUrl($data['afterPrimaryImage'], 'user');
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:checkout.html.twig') {
            $data['type'] = 'checkout';
            $paymentMethod = array_key_exists('paymentMethod', $activityData) ? $activityData['paymentMethod']['name'] : '';
            $data['text'] = 'Checked out item(s) through '.$paymentMethod;
            $data['products'] = array();
            if (array_key_exists('orderProducts', $activityData) && is_array($activityData['orderProducts'])) {
                foreach ($activityData['orderProducts'] as $orderProduct) {
                    $orderProductTxt = '(x'.$orderProduct['quantity'].') '.$orderProduct['productName'];
                    $attributes = json_decode($orderProduct['attributes'], true);
                    if ($attributes) {
                        $orderProductTxt .= ' - ';
                        $attributeTxts = array();
                        foreach ($attributes as $attrName => $attrValue) {
                            $attributeTxts[] = $attrName.': '.$attrValue;
                        }
                        $orderProductTxt .= implode(', ', $attributeTxts);
                    }
                    if (array_key_exists('seller', $orderProduct)) {
                        $orderProductTxt .= ' from ';
                        if (array_key_exists('store', $orderProduct['seller'])) {
                            $orderProductTxt .= $orderProduct['seller']['store']['storeName'];
                        }
                        else {
                            $orderProductTxt .= $orderProduct['seller']['firstName'].' '.$orderProduct['seller']['lastName'];
                        }
                    }
                    $data['products'][] = $orderProductTxt;
                }
            }
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:follow.html.twig') {
            $data['type'] = 'follow';
            $data['text'] = ($activityData['isFollow'] ? 'Followed': 'Unfollowed').' '.$activityData['followee']['firstName'].' '.$activityData['followee']['lastName'];
            $data['primaryImage'] = array_key_exists('imageLocation', $activityData['followee']['primaryImage']) ? 
                                    $activityData['followee']['userId'].'/'.$activityData['followee']['primaryImage']['imageLocation']: '';
            $data['primaryImage'] = $assetHelper->getUrl($data['primaryImage'], 'user');
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:product.html.twig') {
            $data['type'] = 'product';
            $mysqlAction = $activity->getMysqlAction();
            $data['text'] = '';
            if ($mysqlAction == 'INSERT') {
                $data['text'] = 'Added';
            }
            elseif ($mysqlAction == 'UPDATE') {
                $data['text'] = 'Modified';
            }
            elseif ($mysqlAction == 'DELETE') {
                $data['text'] = 'Removed';
            }
            $data['text'] .= ' Product into your listing';
            $data['changes'] = array();
            if (array_key_exists('__changes', $activityData) && is_array($activityData['__changes'])) {
                foreach ($activityData['__changes'] as $column => $change) {
                    $before = array_shift($change);
                    $after = array_shift($change);
                    $data['changes'][$column] = array();
                    if ($before && !is_array($before) && $after && !is_array($after)) {
                        $data['changes'][$column]['before'] = $before;
                        $data['changes'][$column]['after'] = $after;
                    }
                }
            }
            $data['primaryImage'] = $assetHelper->getUrl($activityData['primaryImageLocation'], 'product');
            $data['name'] = $activityData['name'];
            $data['price'] = number_format($activityData['defaultPrice'], 2);
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:ship_item.html.twig') {
            $this->minimalistShipItem($activity, $data);
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:dispute.html.twig') {
            $this->minimalistDispute($activity, $data);
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:bank_account.html.twig') {
            $this->minimalistBankAccount($activity, $data);
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:user_address.html.twig') {
            $this->minimalistUserAddress($activity, $data);
        }
        elseif ($template == 'YilinkerCoreBundle:ActivityLog:order_product_cancellation.html.twig') {
            $this->minimalistOrderProductCancellation($activity, $data);
        }
        else {
            $data['type'] = 'default';
            $tableName = $activity->getAffectedTable();
            $tableConfig = $this->getTableConfig($tableName);
            $mysqlAction = $activity->getMysqlAction();
            $data['text'] = '';
            if ($mysqlAction == 'INSERT') {
                $data['text'] = 'Created ';
            }
            elseif ($mysqlAction == 'UPDATE') {
                $data['text'] = 'Updated ';
            }
            elseif ($mysqlAction == 'DELETE') {
                $data['text'] = 'Deleted ';
            }
            $data['text'] .= array_key_exists('__noun', $tableConfig) ? $tableConfig['__noun']: $tableName;
            $data['changes'] = array();
            if (array_key_exists('__changes', $activityData) && is_array($activityData['__changes'])) {
                foreach ($activityData['__changes'] as $column => $change) {
                    $before = array_shift($change);
                    $after = array_shift($change);
                    $data['changes'][$column] = array();
                    if ($before && !is_array($before) && $after && !is_array($after)) {
                        $data['changes'][$column]['before'] = $before;
                        $data['changes'][$column]['after'] = $after;
                    }
                }
            }
        }

        return $data;
    }

    public function minimalistShipItem($activity, &$data)
    {
        $activityData = $activity->getActivityData();

        $data['type'] = 'ship_item';
        $data['text'] = 'Shipped Items #'.$activityData['waybillNumber'];
        $data['products'] = array();

        if (array_key_exists('packageDetails', $activityData) && is_array($activityData['packageDetails'])) {
            foreach ($activityData['packageDetails'] as $packageDetail) {
                $product = 'x'.$packageDetail['orderProduct']['quantity'].' '.$packageDetail['orderProduct']['productName'];
                $attributes = json_decode($packageDetail['orderProduct']['attributes'], true);
                if ($attributes) {
                    $product .= ' - ';
                    $attributeTxts = array();
                    foreach ($attributes as $attrName => $attrValue) {
                        $attributeTxts[] = $attrName.': '.$attrValue;
                    }
                    $product .= implode(', ', $attributeTxts);
                }
                $data['products'][] = $product;
            }
        }
    }

    public function minimalistDispute($activity, &$data)
    {
        $data['type'] = 'dispute';

        $disputeType = 'Dispute';
        $activityData = $activity->getActivityData();
        if (array_key_exists('orderProductStatus', $activityData['dispute'])) {
            if ($activityData['dispute']['orderProductStatus']['orderProductStatusId'] == OrderProductStatus::STATUS_ITEM_REFUND_REQUESTED) {
                $disputeType = 'Refund';
            }
            elseif ($activityData['dispute']['orderProductStatus']['orderProductStatusId'] == OrderProductStatus::STATUS_ITEM_REPLACEMENT_REQUESTED) {
                $disputeType = 'Replacement';
            }
        }
        $data['text'] = 'Filed a '.$disputeType.' for';
        $orderProduct = array_key_exists('orderProduct', $activityData) ? $activityData['orderProduct']: null;
        if ($orderProduct) {
            $data['text'] .= ' x'.$orderProduct['quantity'].' '.$orderProduct['productName'];
            $invoiceNumber = array_key_exists('order', $orderProduct) ? $orderProduct['order']['invoiceNumber']: null;
            if ($invoiceNumber) {
                $data['text'] .= ' on Transaction #'.$invoiceNumber;
            }
        }
    }

    public function minimalistOrderProductCancellation($activity, &$data)
    {
        $data['type'] = 'order_product_cancellation';
        $data['text'] = 'Filed Cancel Request for';
        $activityData = $activity->getActivityData();

        $orderProduct = array_key_exists('orderProduct', $activityData) ? $activityData['orderProduct']: null;
        if ($orderProduct) {
            $data['text'] .= ' x'.$orderProduct['quantity'].' '.$orderProduct['productName'];
            $invoiceNumber = array_key_exists('order', $orderProduct) ? $orderProduct['order']['invoiceNumber']: null;
            if ($invoiceNumber) {
                $data['text'] .= ' on Transaction #'.$invoiceNumber;
            }
        }
    }

    public function minimalistUserAddress($activity, &$data)
    {
        $data['type'] = 'user_address';
        $data['text'] = '';
        $activityData = $activity->getActivityData();
        $data['address'] = $activityData['addressString'];
        if (array_key_exists('__changes', $activityData) && is_array($activityData['__changes'])) {
            foreach ($activityData['__changes'] as $column => $change) {
                $before = array_shift($change);
                $after = array_shift($change);
                if ($column == 'isDelete') {
                    $data['text'] = 'Deleted ';
                }
                elseif ($before || $after) {
                    $data['changes'][$column] = array();
                    if ($column == 'isDefault') {
                        if ($activityData['isDefault']) {
                            $data['phrase'] = 'set as default';
                        }
                        else {
                            $data['phrase'] = 'removed as default';
                        }
                    }
                    if ($before && !is_array($before) && $after && !is_array($after)) {
                        $data['changes'][$column]['before'] = $before;
                        $data['changes'][$column]['after'] = $after;
                    }
                    elseif ($column == 'location') {
                        $data['changes'][$column]['before'] = $before['location'];
                        $data['changes'][$column]['after'] = $after['location'];
                    }
                }
            }
        }

        if (!$data['text']) {
            $mysqlAction = $activity->getMysqlAction();
            if ($mysqlAction == 'INSERT') {
                $data['text'] = 'Created ';
            }
            elseif ($mysqlAction == 'UPDATE') {
                $data['text'] = 'Updated ';
            }
            elseif ($mysqlAction == 'DELETE') {
                $data['text'] = 'Deleted ';
            }
        }

        $data['text'] .= 'Address '.$activityData['title'];   
    }

    public function minimalistBankAccount($activity, &$data)
    {
        $data['type'] = 'bank_account';
        $data['text'] = '';
        $activityData = $activity->getActivityData();
        if (array_key_exists('__changes', $activityData) && is_array($activityData['__changes'])) {
            foreach ($activityData['__changes'] as $column => $change) {
                $before = array_shift($change);
                $after = array_shift($change);
                if ($column == 'isDelete') {
                    $data['text'] = 'Deleted ';
                }
                else {
                    $data['changes'][$column] = array();
                    if ($column == 'isDefault') {
                        if ($activityData['isDefault']) {
                            $data['phrase'] = 'set as default';
                        }
                        else {
                            $data['phrase'] = 'removed as default';
                        }
                    }
                    if ($before && !is_array($before) && $after && !is_array($after)) {
                        $data['changes'][$column]['before'] = $before;
                        $data['changes'][$column]['after'] = $after;
                    }
                    elseif ($column == 'bank') {
                        $data['changes'][$column]['before'] = $before['bankName'];
                        $data['changes'][$column]['after'] = $after['bankName'];
                    }
                }
            }
        }

        if (!$data['text']) {
            $mysqlAction = $activity->getMysqlAction();
            if ($mysqlAction == 'INSERT') {
                $data['text'] = 'Created ';
            }
            elseif ($mysqlAction == 'UPDATE') {
                $data['text'] = 'Updated ';
            }
            elseif ($mysqlAction == 'DELETE') {
                $data['text'] = 'Deleted ';
            }
        }

        $data['text'] .= 'Bank Account '.$activityData['accountTitle'];
    }

    public function includedAssociations($tableName, $justKeys = true)
    {
        $config = $this->getTableConfig($tableName);

        if (array_key_exists('associations', $config)) {
            if ($justKeys) {
                return array_keys($config['associations']);
            }
            else {
                return $config['associations'];
            }
        }

        return array();
    }

    public function getIncludedValues($entity)
    {
        $config = $this->getEntityConfig($entity);
        $includedValues = array();
        if (array_key_exists('include', $config)) {
            foreach ($config['include'] as $key => $params) {
                if (method_exists($entity, $key)) {
                    $params = is_array($params) ? $params: array($params);
                    $includedValues[$key] = call_user_func_array(array($entity, $key), $params);
                }
                elseif (method_exists($entity, 'get'.ucfirst($key))) {
                    $params = is_array($params) ? $params: array($params);
                    $includedValues[$key] = call_user_func_array(array($entity, 'get'.ucfirst($key)), $params);
                }
            }
        }

        return $includedValues;
    }

    public function getAwayUser($entity)
    {
        $config = $this->getEntityConfig($entity);
        if (array_key_exists('away_user', $config)) {
            $entityService = $this->container->get('yilinker_core.service.entity');
            $user = $entityService->getValue($entity, $config['away_user']);
            
            return $user;
        }

        return false;
    }

    public function isStoreLoggable($entity)
    {
        $isLoggable = false;
        $authenticatedUser= $this->container
                                 ->get('security.context')
                                 ->getToken()
                                 ->getUser();

        if($authenticatedUser && ($entity instanceof Store)){
            $isLoggable = $entity->getUser()->getUserId() === $authenticatedUser->getUserId();
        }

        return $isLoggable;  
    }
    
    private function initConfig()
    {
        $path = __DIR__.'/../../Resources/config/activity_logging.yml';

        $yaml = new Parser;
        $config = $yaml->parse(file_get_contents($path));

        return $config;
    }
}