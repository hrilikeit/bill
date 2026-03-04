<?php

// Find newly-expired peeps and renew them

date_default_timezone_set('America/New_York');

require __DIR__.'/../staysail/Staysail.php';

require __DIR__.'/../config.php';

require __DIR__.'/../interfaces/class.LSFMetadataEntity.php';
require __DIR__.'/../interfaces/interface.AccountType.php';
require __DIR__.'/../interfaces/interface.AccountPublic.php';
require __DIR__.'/../tools/TripleDES.php';
require __DIR__.'/../tools/PayTechTrustPaymentGateway.php';

// Database info only needs to be passed the first time; StaysailIO::engage() will
// return a singleton instance henceforth.

$framework = StaysailIO::engage(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$yesterday = date("Y-m-d 23:59:59", strtotime("-1 day", time()));;

$filters = array(new Filter(Filter::Where, "expire_time <= '$yesterday'"));
$Fan_Subscriptions = $framework->getSubset('Fan_Subscription', $filters);

foreach ($Fan_Subscriptions as $Fan_Subscription)
{

    print " --Starting  {$Fan_Subscription->id}\n";

    $Entertainer = $Fan_Subscription->Entertainer;
    $Fan = $Fan_Subscription->Fan;
    if ($Entertainer && $Fan) {
        $Member = $Fan->Member;
        if (!$Member) {
            print "  No Member found for {$Fan_Subscription->id } subscription\n";
            continue;
        }
        if (!$Entertainer->subscription_pricing) { // Free subscription case
            $Fan_Subscription->expire_time = date('Y-m-d 00:00:00', strtotime('+1 month', strtotime($yesterday)));
            $Fan_Subscription->save();
            print "  Free subscription\n";
            continue;
        } else {
            // This is the payment method used last time
            $Payment_Method = $Fan->getLastMemberPaymentMethod();
            if ($Payment_Method) {
                print "  Last used was {$Payment_Method->id}\n";
            } else {
                print "  No last used found\n";
            }

            if ($Payment_Method and $Payment_Method->isExpired()) {$Payment_Method = false;}
            if (!$Payment_Method && $Fan->Member) {
                // if the payment method is no good, or a payment method doesn't exist,
                // try to find the first unexpired payment method
                $Payment_Method =  $Member->getUnexpiredPaymentMethod();
            }
            if ($Payment_Method and $Payment_Method->belongsTo($Member)) {
                print "  Paying with: {$Payment_Method->id}\n";
                $Order = new Order();
                $Order->setMember($Member);
                $Order->addOrderLine('Entertainer Subscription', $Entertainer->subscription_pricing, 1, $Entertainer);
                $gateway = new PayTechTrustPaymentGateway();
                if (strtolower($Payment_Method->company) == 'test') {
                    $gateway->setTestMode();
                }
                $gateway->setPaymentMethod($Payment_Method);
                $gateway->setOrder($Order);

                if ($gateway->doSale(0)) {
                    print "    ** SUCCESS! **\n";
                    $Fan_Subscription->expire_time = date('Y-m-d 00:00:00', strtotime('+1 month', strtotime($yesterday)));
                    $Fan_Subscription->save();
                    $Member->extendMembership();
                } else {
                    print "    ** FAILED **\n";
                    $Order->cancel();
                    $Fan_Subscription->delete_Job();
                }
            } else {
                $Fan_Subscription->delete_Job();
                print "  No payment method found\n";
            }
        }
    } else {
        print "  No Member or Fan {$Fan_Subscription->id}\n";
    }
}