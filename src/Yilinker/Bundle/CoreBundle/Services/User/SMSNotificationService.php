<?php

namespace Yilinker\Bundle\CoreBundle\Services\User;

class SMSNotificationService
{
    private $sms;

    public function __construct($sms)
    {
        $this->sms = $sms;
    }

    public function send($table, $action, $user, $data)
    {
        $number = $user->getContactNumber();
        if ($number) {
            $method = 'notif'.$table.$action;

            if (method_exists($this, $method)) {
                $message = $this->{$method}($user, $data);

                try {
                    $this->sms->setMobileNumber($number);
                    $this->sms->setMessage($message);
                    $this->sms->sendSMS();
                } catch (\Exception $e) {}
            }
        }

    }

    public function subject($table, $action, $user, $data)
    {
        if ($table.$action == 'UserOrderUPDATE') {
            return ($user->isSeller() ? 'New Order': 'Checkout Items').' #'.$data['invoiceNumber'];
        }
        elseif ($table.$action == 'OrderProductCancellationHeadINSERT') {
            $detail = array_shift($data['orderProductCancellationDetails']);
            $order = $detail['orderProduct']['order'];

            return 'Order Product Cancellation for #'.$order['invoiceNumber'];
        }

        return 'Yilinker Notification';
    }

    public function notifOrderProductCancellationHeadINSERT($user, $data)
    {
        $message = $this->subject('OrderProductCancellationHead', 'INSERT', $user, $data).'\n';
        foreach ($data['orderProductCancellationDetails'] as $detail) {
            $orderProduct = $detail['orderProduct'];
            if (!$user->isSeller() || $orderProduct['seller']['userId'] == $user->getUserId()) {
                $message .= '    x'.$orderProduct['quantity'].' '.$orderProduct['productName'].'\n';
            }
        }

        return $message;
    }

    public function notifUserOrderUPDATE($user, $data)
    {
        $products = array();

        $totalOrderProductPrice = "0.0000";
        foreach ($data['orderProducts'] as $orderProduct) {
            if($user->isSeller()){
                if($user->getUserId() === $orderProduct['seller']['userId']){
                    $products[] = 'x'.$orderProduct['quantity'].' '.$orderProduct['productName'];
                    $orderProductAmount = bcmul($orderProduct['quantity'], $orderProduct['unitPrice'], 8);
                    $totalOrderProductPrice = bcadd($totalOrderProductPrice, $orderProductAmount, 8);
                }
            }
            else{
                $products[] = 'x'.$orderProduct['quantity'].' '.$orderProduct['productName'];
            }            
        }
        $products = implode(', ', $products);

        if ($user->isSeller()) {
            $message = "Hi, we've received an order for the products: ".$products." for P".number_format($totalOrderProductPrice, 2)."  with invoice number #".$data['invoiceNumber'];
        }
        else {
            $message = "Hi, we've received your order with invoice number #".$data['invoiceNumber']." for the following products: ".$products." for P".number_format($data['totalPrice'], 2);
        }


        return $message;
    }
}