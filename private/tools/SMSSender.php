<?php

require '../private/external/twilio-php-master/Services/Twilio.php';


/*
 * Example:
 * 
 * $SMSSender = new SMSSender($Member, 'sender');
 * $SMSSender->send('Hello!  This is your SMS!');
 * 
 */
class SMSSender
{
	const SID = 'AC0af94c42071686cb7a8a4bf90d0eb62b';
	const AUTH_TOKEN = '85257c05b9b84802fddcbc417dc109dd';
	const SMS_PHONE = '708-716-4069';
	
	private $provider;
	private $to;
	private $from;
	private $truncate;
	private $optout;

	const MAXLEN = 140;
	
	public function __construct($Member, $from = '')
	{
		$this->provider =  !empty($Member->cell_provider) ? $Member->cell_provider :  '-';
		$this->to = $Member->phone;
		$this->optout = $Member->sms_optout;
		$this->from = $from;
		$this->truncate = false;
		
		if ($Member->is_deleted or !$Member->memberIsPaid()) {
			$this->to = null;
		}
	}
	
	public function truncate()
	{
		$this->truncate = true;
	}
	
//	public function send($message)
//	{
//		if (!$this->to) {return;}
//		if ($this->optout) {return;}
//
//		// Validate phone number
//		$to = preg_replace('/[^0-9]/', '', $this->to);
//		if (strlen($to) != 10) {return;}
//
//		try {
//			$client = new Services_Twilio(self::SID, self::AUTH_TOKEN);
//			$client->account->messages->sendMessage(self::SMS_PHONE, $this->to, $message);
//		} catch (Exception $e) {
//			return;
//		}
//	}
	
//	public static function getProviders()
//	{
//		$providers = array('' => '',
//						   'Alltell' => 'message.alltel.com',
//						   'AT&T' => 'txt.att.net',
//						   'Boost' => 'myboostmobile.com',
//						   'Cricket' => 'sms.mycricket.com',
//						   'Fido (Canada)' => 'fido.ca',
//						   'Nextel' => 'messaging.nextel.com',
//						   'MetroPCS' => 'mymetropcs.com',
//						   'Simple Mobile' => 'smtext.com',
//						   'Sprint' => 'messaging.sprintpcs.com',
//						   'T-Mobile' => 'tmomail.net',
//						   'US Cellular' => 'email.uscc.net',
//						   'Verizon' => 'vtext.com',
//						   'Virgin' => 'vmobl.com',
//		 				   'Other' => '-',
//						  );
//		return $providers;
//	}
}