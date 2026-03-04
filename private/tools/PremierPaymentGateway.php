<?php

//define('PAYMENT_USERNAME', 'localstrip');
//define('PAYMENT_PASSWORD', 'Lucky1332');
//define('FALLBACK_IP', '198.154.220.137');

class PremierPaymentGateway
{
	private $Payment_Method, $Order;
	private $order_info, $billing;
	private $username, $password;
	private $testmode;
	private $last_response;
	
	public function __construct()
	{
		$this->username = PAYMENT_USERNAME;
		$this->password = PAYMENT_PASSWORD;
		
		$this->testmode = false;
	}
	
	public function getLastResponse()
	{
		return $this->last_response;
	}
	
	public function setTestMode()
	{
		$this->username = 'demo';
		$this->password = 'password';
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
		$amount = $this->Order->getTotalAmount();
		$ccnumber = $this->Payment_Method->getCCNumber();
		$cvv = $this->Payment_Method->getCCVC();
		$ccexp = $this->testmode ? '1010' : $this->Payment_Method->getExpiration(); // Force exp to 10/10 if in testmode
		
		$query  = "";
		// Login Information
		$query .= "username=" . urlencode($this->username) . "&";
		$query .= "password=" . urlencode($this->password) . "&";
		// Sales Information
		$query .= "ccnumber=" . urlencode($ccnumber) . "&";
		$query .= "ccexp=" . urlencode($ccexp) . "&";
		$query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
		$query .= "cvv=" . urlencode($cvv) . "&";
		// Order Information
		$query .= "ipaddress=" . urlencode($this->order_info['ipaddress']) . "&";
		$query .= "orderid=" . urlencode($this->order_info['orderid']) . "&";
		$query .= "orderdescription=" . urlencode($this->order_info['orderdescription']) . "&";
		$query .= "tax=" . urlencode(number_format($this->order_info['tax'],2,".","")) . "&";
		$query .= "shipping=" . urlencode(number_format($this->order_info['shipping'],2,".","")) . "&";
		$query .= "ponumber=" . urlencode($this->order_info['ponumber']) . "&";
		// Billing Information
		$query .= "firstname=" . urlencode($this->billing['firstname']) . "&";
		$query .= "lastname=" . urlencode($this->billing['lastname']) . "&";
		$query .= "company=" . urlencode($this->billing['company']) . "&";
		$query .= "address1=" . urlencode($this->billing['address1']) . "&";
		$query .= "address2=" . urlencode($this->billing['address2']) . "&";
		$query .= "city=" . urlencode($this->billing['city']) . "&";
		$query .= "state=" . urlencode($this->billing['state']) . "&";
		$query .= "zip=" . urlencode($this->billing['zip']) . "&";
		$query .= "country=" . urlencode($this->billing['country']) . "&";
		$query .= "phone=" . urlencode($this->billing['phone']) . "&";
		$query .= "fax=" . urlencode($this->billing['fax']) . "&";
		$query .= "email=" . urlencode($this->billing['email']) . "&";
		$query .= "website=" . urlencode($this->billing['website']) . "&";

		$xtype = $auth ? 'auth' : 'sale';
		$query .= "type={$xtype}";
		
		$response = $this->sendRequest($query);
		$this->last_response = $response;
		if (isset($response['response']) and $response['response'] == 1) {
			$authcode = isset($response['authcode']) ? $response['authcode'] : false;
			$transactionid = isset($response['transactionid']) ? $response['transactionid'] : false;
			if ($authcode and $transactionid) {
				$this->Order->applyPayment($amount, $authcode, $transactionid, $this->Payment_Method, $auth);
			}
			return true;
		}
		if (isset($response['response']) and $response['response'] == 3) {
			mail('jjustian@gmail.com,CustomerService@localstripfan.com', 'Error in LSF gateway response', print_r($response, true));
			print_r($response);
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
		
		$query  = "";
		// Login Information
		$query .= "username=" . urlencode($this->username) . "&";
		$query .= "password=" . urlencode($this->password) . "&";
		// Transaction Information
		$query .= "transactionid=" . urlencode($transactionid) . "&";
		if ($amount>0) {
			$query .= "amount=" . urlencode(number_format($amount,2,".","")) . "&";
		}
		$query .= "type=capture";
		$response = $this->sendRequest($query);
		$this->last_response = $response;
		
		if (isset($response['response']) and $response['response'] == 1) {
			$authcode = isset($response['authcode']) ? $response['authcode'] : false;
			$transactionid = isset($response['transactionid']) ? $response['transactionid'] : false;
			if ($authcode and $transactionid) {
				$this->Order->applyPayment($amount, $authcode, $transactionid);
			}
			return true;
		}
		return false;
	}

	private function sendRequest($query) 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://secure.ppsgateway.com/api/transact.php");
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
 
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_POST, 1);
 
		if (!($data = curl_exec($ch))) {
			return false;
		}
		curl_close($ch);
		unset($ch);
		$data = explode("&",$data);
		for ($i=0; $i<count($data); $i++) 
		{
			$rdata = explode("=",$data[$i]);
			$this->responses[$rdata[0]] = $rdata[1];
		}
		return $this->responses;
	}
}