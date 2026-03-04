<?php

use function Couchbase\defaultDecoder;

define("APPROVED", 1);
define("DECLINED", 2);
define("ERROR", 3);
define('FALLBACK_IP', '198.154.220.137');
//define("SECURITY_KEY", '6457Thfj624V5r7WUwc5v6a68Zsd6YEm');live
define("SECURITY_KEY", 'P893GNhgmSd4Wp2GWGdbR6cMgBaJAgVc');

class PayTechTrustPaymentGateway
{
    private $Payment_Method, $Order;
    private $order, $billingInfo;
    private $last_response;
    private $testmode;
    private $login;
    private $query;
    private $billing;
    private $responses;

    public function __construct()
    {
        $this->login['security_key'] = SECURITY_KEY;
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

        $this->order['orderid'] = $Order->order_code;
        $this->order['orderdescription'] = $Order->order_time;
        $this->order['tax'] = 0;
        $this->order['shipping'] = 0;
        $this->order['ponumber'] = 0;

        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : FALLBACK_IP;
        $this->order['ipaddress'] = $ip;
    }

    public function setPaymentMethod(Payment_Method $Payment_Method)
    {
        $this->Payment_Method = $Payment_Method;

        $fields = array('firstname', 'lastname', 'company', 'address1', 'address2',
            'city', 'state', 'country', 'zip', 'phone', 'email');
        foreach ($fields as $fieldname) {
            $this->billing[$fieldname] = $Payment_Method->$fieldname;
        }

        if (!$this->billing['country']) {
            $this->billing['country'] = 'US';
        }
        $this->billing['fax'] = '';
        $this->billing['website'] = '';
    }


    // Transaction Functions

//    function doSale($auth = false) {
//
//        $this->reset();
//        $query  = '';
//        $amount = $this->Order->getTotalAmount();
//
//        // Credit card info
////        if ($this->Payment_Method->cc_tx) {
////            $ccnumber = "CS:{$this->Payment_Method->cc_tx}";
////            $query .= "ccnumber=" . urlencode($ccnumber) . "&";
////         } else {
//            $ccnumber = $this->Payment_Method->getCCNumber();
//            $cvv = $this->Payment_Method->getCCVC();
//            $ccexp = $this->testmode ? '1010' : $this->Payment_Method->getExpiration(); // Force exp to 10/10 if in testmode
//
//            $query .= "ccnumber=" . urlencode($ccnumber) . "&";
//            $query .= "ccexp=" . urlencode($ccexp) . "&";
//            $query .= "cvv=" . urlencode($cvv) . "&";
//
////        }
//
//        // Login Information
//        $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
//
//        $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
//        // Order Information
//        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
//        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
//        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
//        $query .= "tax=" . urlencode(number_format($this->order['tax'],2,".","")) . "&";
//        $query .= "shipping=" . urlencode(number_format($this->order['shipping'],2,".","")) . "&";
//        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
//        // Billing Information
//        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
//        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
//        $query .= "company=" . urlencode($this->billing['company']) . "&";
//        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
//        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
//        $query .= "city=" . urlencode($this->billing['city']) . "&";
//        $query .= "state=" . urlencode($this->billing['state']) . "&";
//        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
//        $query .= "country=" . urlencode($this->billing['country']) . "&";
//        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
//        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
//        $query .= "email=" . urlencode($this->billing['email']) . "&";
//        $query .= "website=" . urlencode($this->billing['website']) . "&";
//
//        $response = $this->_doPost($query);
//
//        if (isset($response['response_code']) and $response['response_code'] != '0' and $response['response_code'] != 'F' && in_array($response['responsetext'], ['SUCCESS', 'Approved'])) {
//            //var_dump(111);
//            $authcode = isset($response['authcode']) ? $response['authcode'] : false;
//            $transactionid = isset($response['transactionid']) ? $response['transactionid'] : false;
//
//            if ($authcode and $transactionid) {
//                //var_dump(222);
//                $this->Order->applyPayment($amount, $authcode, $transactionid, $this->Payment_Method, $auth);
//            }
//            return true;
//        }
//       // var_dump(333);
//        return false;
//    }


    function doSale($auth = false)
    {
        $this->reset();
//        $query  = '';
        $amount = $this->Order->getTotalAmount();

        // Credit card info
//        if ($this->Payment_Method->cc_tx) {
//            $ccnumber = "CS:{$this->Payment_Method->cc_tx}";
//            $query .= "ccnumber=" . urlencode($ccnumber) . "&";
//         } else {
//            $ccnumber = $this->Payment_Method->getCCNumber();
//            $cvv = $this->Payment_Method->getCCVC();
//            $ccexp = $this->testmode ? '1010' : $this->Payment_Method->getExpiration(); // Force exp to 10/10 if in testmode
//
//            $query .= "ccnumber=" . urlencode($ccnumber) . "&";
//            $query .= "ccexp=" . urlencode($ccexp) . "&";
//            $query .= "cvv=" . urlencode($cvv) . "&";
//
////        }
//
//        // Login Information
//        $query .= "security_key=" . urlencode($this->login['security_key']) . "&";
//
//        $query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
//        // Order Information
//        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
//        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
//        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
//        $query .= "tax=" . urlencode(number_format($this->order['tax'],2,".","")) . "&";
//        $query .= "shipping=" . urlencode(number_format($this->order['shipping'],2,".","")) . "&";
//        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
//        // Billing Information
//        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
//        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
//        $query .= "company=" . urlencode($this->billing['company']) . "&";
//        $query .= "address1=" . urlencode($this->billing['address1']) . "&";
//        $query .= "address2=" . urlencode($this->billing['address2']) . "&";
//        $query .= "city=" . urlencode($this->billing['city']) . "&";
//        $query .= "state=" . urlencode($this->billing['state']) . "&";
//        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
//        $query .= "country=" . urlencode($this->billing['country']) . "&";
//        $query .= "phone=" . urlencode($this->billing['phone']) . "&";
//        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
//        $query .= "email=" . urlencode($this->billing['email']) . "&";
//        $query .= "website=" . urlencode($this->billing['website']) . "&";

        $response = $this->prepare_query($amount);
        if (isset($response['response_code']) and $response['response_code'] != '0' and $response['response_code'] != 'F' && in_array($response['responsetext'], ['SUCCESS', 'Approved'])) {
            $authcode = isset($response['authcode']) ? $response['authcode'] : false;
            $transactionid = isset($response['transactionid']) ? $response['transactionid'] : false;

            if ($authcode and $transactionid) {
                $this->Order->applyPayment($amount, $authcode, $transactionid, $this->Payment_Method, $auth);
            }
            return true;
        }
        return false;
    }

    function doSaleTips($auth = false)
    {
        $this->reset();
        $amount = $this->Order->getTotalAmount();

        $response = $this->prepare_query($amount);
        if (isset($response['response_code']) and $response['response_code'] != '0' and $response['response_code'] != 'F' && in_array($response['responsetext'], ['SUCCESS', 'Approved'])) {
            $authcode = isset($response['authcode']) ? $response['authcode'] : false;
            $transactionid = isset($response['transactionid']) ? $response['transactionid'] : false;

            if ($authcode and $transactionid) {
                $this->Order->applyPayment($amount, $authcode, $transactionid, $this->Payment_Method, $auth);
            }
            return ['status' => true, 'response_text' => $response['responsetext']];
        }
        return ['status' => false, 'response_text' => $response['responsetext']];
    }

    function prepare_query($amount)
    {
        $query = '';
        $ccnumber = $this->Payment_Method->getCCNumber();
        $cvv = $this->Payment_Method->getCCVC();
        $ccexp = $this->testmode ? '1010' : $this->Payment_Method->getExpiration(); // Force exp to 10/10 if in testmode

        $query .= "ccnumber=" . urlencode($ccnumber) . "&";
        $query .= "ccexp=" . urlencode($ccexp) . "&";
        $query .= "cvv=" . urlencode($cvv) . "&";

//        }

        // Login Information
        $query .= "security_key=" . urlencode($this->login['security_key']) . "&";

        $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
        // Order Information
        $query .= "ipaddress=" . urlencode($this->order['ipaddress']) . "&";
        $query .= "orderid=" . urlencode($this->order['orderid']) . "&";
        $query .= "orderdescription=" . urlencode($this->order['orderdescription']) . "&";
        $query .= "tax=" . urlencode(number_format($this->order['tax'], 2, ".", "")) . "&";
        $query .= "shipping=" . urlencode(number_format($this->order['shipping'], 2, ".", "")) . "&";
        $query .= "ponumber=" . urlencode($this->order['ponumber']) . "&";
        // Billing Information
        $query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
        $query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
        if ($this->billing['company']) {
            $query .= "company=" . urlencode($this->billing['company']) . "&";
        }
        if ($this->billing['address1']) {
            $query .= "address1=" . urlencode($this->billing['address1']) . "&";
        }
        if ($this->billing['address2']) {
            $query .= "address2=" . urlencode($this->billing['address2']) . "&";
        }
        $query .= "city=" . urlencode($this->billing['city']) . "&";
        $query .= "state=" . urlencode($this->billing['state']) . "&";
        $query .= "zip=" . urlencode($this->billing['zip']) . "&";
        $query .= "country=" . urlencode($this->billing['country']) . "&";
        if ($this->billing['phone']) {
            $query .= "phone=" . urlencode($this->billing['phone']) . "&";
        }
        $query .= "fax=" . urlencode($this->billing['fax']) . "&";
        $query .= "email=" . urlencode($this->billing['email']) . "&";
        $query .= "website=" . urlencode($this->billing['website']) . "&";

        return $this->_doPost($query);
    }

    function _doPost($query)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://paytechtrust.transactiongateway.com/api/transact.php");
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_POST, 1);

        if (!($data = curl_exec($ch))) {
            return ERROR;
        }
        curl_close($ch);
        unset($ch);
        $data = explode("&", $data);

        for ($i = 0; $i < count($data); $i++) {
            $rdata = explode("=", $data[$i]);
            $this->responses[$rdata[0]] = $rdata[1];
        }
        return $this->responses;
    }

    private function reset()
    {
        $this->query = '';
    }

    function capture($amount = 0)
    {
        $transactionid = $this->Order->transactionid;
        if (!$transactionid) {
            return false;
        }

        $this->Reset();
        $this->add('account_id', SECURITY_KEY);
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

    private function add($key, $value)
    {
        $value = urlencode($value);
        $this->query .= "{$key}={$value}&";
    }

    private function sendRequest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://test.authorize.net/gateway/transact.dll");
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

        $data = explode("&", $data);
        for ($i = 0; $i < count($data); $i++) {
            $rdata = explode("=", $data[$i]);
            $this->responses[$rdata[0]] = $rdata[1];
        }
        return $this->responses;
    }
}
