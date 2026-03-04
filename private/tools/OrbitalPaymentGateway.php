<?php

//define('ORBITAL_ACCOUNT', '121155311990');

class PaymentGateway
{
    private $Payment_Method, $Order;
    private $order_info, $billing;
    private $last_response;
    private $testmode;
    private $query;
    
    public function __construct()
    {
        $this->testmode = false;
    }
    
    public function getLastResponse()
    {
        return $this->last_response;
    }
    
    public function setTestMode()
    {
        $this->testmode = true;
    }
    
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
        
        $this->order_info['orderid'] = $Order->order_code;
        $this->order_info['orderdescription'] = $Order->order_time;
        $this->order_info['tax'] = 0;
        $this->order_info['shipping'] = 0;
        $this->order_info['ponumber'] = 0;
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : FALLBACK_IP;
        $this->order_info['ipaddress'] = $ip;
    }
    
    public function setPaymentMethod(Payment_Method $Payment_Method)
    {
        $this->Payment_Method = $Payment_Method;
        
        $fields = array('firstname', 'lastname', 'company', 'address1', 'address2',
                        'city', 'state', 'country', 'zip', 'phone', 'email');
        foreach ($fields as $fieldname)
        {
        $this->billing[$fieldname] = $Payment_Method->$fieldname;
        }
        
        if (!$this->billing['country']) {$this->billing['country'] = 'US';}
        $this->billing['fax'] = '';
        $this->billing['website'] = '';
    }
    
    public function doSale($auth = false)
    {
        $this->reset();
        
        $amount = $this->Order->getTotalAmount();
        
        // Credit card info
        if ($this->Payment_Method->cc_tx) {
            $ccnumber = "CS:{$this->Payment_Method->cc_tx}";
            $this->add('card_number', $ccnumber);

        } else {
            $ccnumber = $this->Payment_Method->getCCNumber();
            $cvv = $this->Payment_Method->getCCVC();
            $ccexp = $this->testmode ? '1010' : $this->Payment_Method->getExpiration(); // Force exp to 10/10 if in testmode
            $this->add('card_expire', $ccexp);
            $this->add('card_cvv2', $cvv);
            $this->add('card_number', $ccnumber);
        }
                
        // Basic Info
        $this->add('pay_type', 'C');
        $this->add('tran_type', ($auth ? 'A' : 'S'));
        $this->add('account_id', ORBITAL_ACCOUNT);
        $this->add('amount', number_format($amount,2,".",""));
                
        // Billing Info
        $this->add('bill_name1', $this->billing['firstname']);
        $this->add('bill_name2', $this->billing['lastname']);
        $this->add('bill_street', $this->billing['address1']);
        $this->add('bill_zip', $this->billing['zip']);
        $this->add('bill_country', $this->billing['country']);
              
        $response = $this->sendRequest();
        $this->last_response = $response;
        if (isset($response['status_code']) and $response['status_code'] != '0' and $response['status_code'] != 'F') {
            $authcode = isset($response['auth_code']) ? $response['auth_code'] : false;
            $transactionid = isset($response['trans_id']) ? $response['trans_id'] : false;
            if ($authcode and $transactionid) {
                $this->Order->applyPayment($amount, $authcode, $transactionid, $this->Payment_Method, $auth);
            }
            return true;
        }

        return false;
    }
    
    function authorize()
    {
        return $this->doSale(true);
    }
    
    function capture($amount = 0)
    {
        $transactionid = $this->Order->transactionid;
        if (!$transactionid) {return false;}
        
        $this->Reset();
        $this->add('account_id', ORBITAL_ACCOUNT);
        $this->add('tran_type', 'D');
	$this->add('pay_type', 'C');
        $this->add('orig_id', $transactionid);
	$this->add('amount', $amount);
        $response = $this->sendRequest();
        $this->last_response = $response;
        if (isset($response['status_code']) and $response['status_code'] != '0' and $response['status_code'] != 'F') {
            $authcode = isset($response['auth_code']) ? $response['auth_code'] : false;
            $transactionid = isset($response['trans_id']) ? $response['trans_id'] : false;
            if ($authcode and $transactionid) {
                $this->Order->applyPayment($amount, $authcode, $transactionid);
            }
            return true;
        }
        return false;
    }
    
    private function sendRequest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://orbitalpay.gettrx.com:1402/gw/sas/direct3.2");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, "LSF1.0");
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->query);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!($data = curl_exec($ch))) {
mail('jjustian+lsf@gmail.com', 'LSF Fail', $this->query);
            return false;
        }
        curl_close($ch);
        unset($ch);

mail('jjustian+lsf@gmail.com', 'LSF Diag', "{$this->query}\n\nResponse:{$data}");

        $data = explode("&",$data);
        for ($i=0; $i<count($data); $i++)
        {
            $rdata = explode("=",$data[$i]);
            $this->responses[$rdata[0]] = $rdata[1];
        }
        return $this->responses;
    }
    
    private function reset() {$this->query = '';}
    
    private function add($key, $value)
    {
        $value = urlencode($value);
        $this->query .= "{$key}={$value}&";
    }
}

