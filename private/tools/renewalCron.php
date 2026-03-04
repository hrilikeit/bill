<?php
// Find newly-expired peeps and renew them

date_default_timezone_set('America/New_York');

require '../staysail/Staysail.php';

require '../config.php';
require '../interfaces/class.LSFMetadataEntity.php';
require '../interfaces/interface.AccountType.php';
require '../interfaces/interface.AccountPublic.php';
require '../tools/TripleDES.php';
require '../tools/OrbitalPaymentGateway.php';


// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.
$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$filters = array(new Filter(Filter::Match, array('is_deleted' => 0)));
$fans = $framework->getSubset('Fan', $filters);

$yesterday = date('Y-m-d', time() - (24 * 60 * 60));  // Looking for Fans who expired yesterday
$yesterday = date('Y-m-d');

foreach ($fans as $Fan)
{
	$Member = $Fan->Member;
	if ($Member) {
		print "\n\n--{$Member->name}--\n";
		$expire_time = strtotime($Member->expire_time);
		$expire_date = date('Y-m-d', $expire_time);
		if ($expire_date == $yesterday) {
			// This person's expiration date was yesterday, so attempt to renew
			
			// This is the payment method used last time
			$Payment_Method = $Fan->getLastMemberPaymentMethod();
			if ($Payment_Method) {
				print "  Last used was {$Payment_Method->id}\n";
			} else {
				print "  No last used found\n";
			}
                        if ($Payment_Method and $Payment_Method->isExpired()) {$Payment_Method = false;}
			if (!$Payment_Method) {
				// if the payment method is no good, or a payment method doesn't exist,
				// try to find the first unexpired payment method
				$Payment_Method = $Member->getUnexpiredPaymentMethod();
			}
			if ($Payment_Method and $Payment_Method->belongsTo($Member)) {
				print "  Paying with: {$Payment_Method->id}\n";
				$Order = new Order();
				$Order->setMember($Member);
				$Order->addOrderLine('Monthly Member Fee', '4.97');
				
				$gateway = new PaymentGateway();
				if (strtolower($Payment_Method->company) == 'test') {$gateway->setTestMode();}
				$gateway->setPaymentMethod($Payment_Method);
				$gateway->setOrder($Order);
                                if ($gateway->doSale(0)) {
					print "    ** SUCCESS! **\n";
					$Member->extendMembership();
				} else {
					print "    ** FAILED **\n";
					$Order->cancel();
				}
			} else {
				print "  No payment method found\n";
			}
		}
	}
}
