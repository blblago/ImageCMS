<?php

(defined('BASEPATH')) OR exit('No direct script access allowed');

/**
 * Image CMS
 * Module Frame
 */
class Payment_method_liqpay extends MY_Controller {

    public $paymentMethod;

    public function __construct() {
        parent::__construct();
        $lang = new MY_Lang();
        $lang->load('payment_method_liqpay');
    }

    public function index() {
        
    }

    private function getPaymentSettings($key) {
        $ci = &get_instance();
        $value = $ci->db->where('name', $key)
                ->get('shop_settings');
        if ($value) {
            $value = $value->row()->value;
        } else {
            show_error($ci->db->_error_message());
        }               
        return unserialize($value);
    }

    public function getAdminForm($id, $payName = null) {
        $nameMethod = $payName ? $payName : $this->paymentMethod->getPaymentSystemName();
        $key = $id . '_' . $nameMethod;
        $data = $this->getPaymentSettings($key);

        return '           
            <div class="control-group">
                <label class="control-label" for="inputRecCount">' . lang('Public key', 'payment_method_liqpay') . ':</label>
                <div class="controls">
                 <input type="text" name="payment_method_liqpay[merchant_id]" value="' . $data['merchant_id'] . '"/>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="inputRecCount">' . lang('Private key', 'payment_method_liqpay') . ':</label>
                <div class="controls">
                 <input type="text" name="payment_method_liqpay[merchant_sig]" value="' . $data['merchant_sig'] . '" />
                </div>
            </div>
     
        ';
    }

    public function getForm($param) {
        $nameMethod = 'payment_method_liqpay';
        $payment_method_id = $param->getPaymentMethod();
        $key = $payment_method_id . '_' . $nameMethod;
        $paySettings = $this->getPaymentSettings($key);

        $publicKey = $paySettings['merchant_id'];
        $privateKey = $paySettings['merchant_sig'];
        $descr = 'OrderId: ' . $param->id . '; Key: ' . $param->getKey();

        $data = array(
            'public_key' => $publicKey,
            'amount' => $param->getTotalPrice(),
            'currency' => \Currency\Currency::create()->getMainCurrency()->getCode(),
            'description' => $descr,
            'order_id' => $param->id,
            'server_url' => site_url() . 'payment_method_liqpay/callback',
            'result_url' => site_url() . 'shop/order/view/' . $param->getKey(),
        );

        $inv = $privateKey . $data['amount'] . $data['currency'] . $data['public_key'] . $data['order_id'] . 'buy' . $data['description'] . $data['result_url'] . $data['server_url'];
        $inv = html_entity_decode($inv);
        $signature = base64_encode(sha1($inv, 1));

        return '<form id="paidForm" method="POST" action="https://www.liqpay.com/api/pay" 
                    accept-charset="utf-8">
                      <input type="hidden" name="public_key" value="' . $data['public_key'] . '"/>
                      <input type="hidden" name="amount" value="' . $data['amount'] . '"/>
                      <input type="hidden" name="currency" value="' . $data['currency'] . '"/>
                      <input type="hidden" name="description" value="' . $data['description'] . '"/>
                      <input type="hidden" name="order_id" value="' . $data['order_id'] . '"/>
                      <input type="hidden" name="sandbox" value="1"/>
                      <input type="hidden" name="result_url" value="' . $data['result_url'] . '"/>
                      ' . "<input type='hidden' name='server_url' value='" . $data['server_url'] . "'/>" . '     
                      <input type="hidden" name="type" value="buy"/>
                      <input type="hidden" name="signature" value="' . $signature . '"/>' .
                "<div class='btn-cart btn-cart-p'>
                    <input type='submit' value='Оплатить'>
                </div>" .
                '</form>';
    }

    public function callback() {
        if ($_POST) {
            $this->checkPaid($_POST);
        }
    }

    public function checkPaid($param) {
        $order_id = $param['order_id'];
        $ci = &get_instance();
        $userOrder = $ci->db->where('id', $order_id)
                ->get('shop_orders');
        if($userOrder){
            $userOrder = $userOrder->row();
        } else {
            show_error($ci->db->_error_message());
        } 

        $key = $userOrder->payment_method . '_payment_method_liqpay';
        $paySettings = $this->getPaymentSettings($key);
        

        $sign = base64_encode(sha1(
                        $paySettings['merchant_sig'] .
                        $param['amount'] .
                        $param['currency'] .
                        $param['public_key'] .
                        $param['order_id'] .
                        $param['type'] .
                        $param['description'] .
                        $param['status'] .
                        $param['transaction_id'] .
                        $param['sender_phone']
                        , 1));

        

//        if ($param['status'] == 'wait_secure') {
//            $this->waitPaid($model);
//            exit;
//        }

        if ($param['signature'] == $sign && $order_id)
            if ($param['status'] == 'success')
                $this->successPaid($order_id);
//            else
//                $this->failPaid($model, $this->type . ': status does not true');
//        else
//            $this->failPaid($model, $this->type . ': sigin does not true');
    }

    /**
     * Save settings
     *
     * @return bool|string
     */
    public function saveSettings(SPaymentMethods $paymentMethod) {
        $saveKey = $paymentMethod->getId() . '_payment_method_liqpay';
        \ShopCore::app()->SSettings->set($saveKey, serialize($_POST['payment_method_liqpay']));

        return true;
    }

    /**
     * success paid
     */
    public function successPaid($order_id) {
        $ci = &get_instance();
        
        $userOrder = $ci->db->where('id', $order_id)
                ->get('shop_orders');
        if($userOrder){
            $userOrder = $userOrder->row();
        } else {
            show_error($ci->db->_error_message());
        }    
        
        $amount = $ci->db->select('amout')
                        ->get_where('users', array('id' => $userOrder->user_id));
        
        if($amount){
            $amount = $amount->row()->amout;
        } else {
            show_error($ci->db->_error_message());
        }             
        $amount += $userOrder->total_price;      
        
        $result = $ci->db->where('id',$order_id)
                ->update('shop_orders', array('paid'=>'1'));
        if(!$result){
            show_error($ci->db->_error_message());
        }
        
        $result = $ci->db
                ->where('id', $userOrder->user_id)
                ->limit(1)
                ->update('users', array(
                    'amout' => str_replace(',', '.', $amount)
        ));
        if(!$result){
            show_error($ci->db->_error_message());
        }
    }

    public function autoload() {
        
    }

    public function _install() {
        $this->db->where('name', 'payment_method_liqpay')
                ->update('components', array('enabled' => '1'));
    }

    public function _deinstall() {
        
    }

}

/* End of file sample_module.php */