<?php

class FanSubscriptions extends LSFView
{
	public function getMainVersionHTML()
	{
		$job = StaysailIO::get('job');
		$id = StaysailIO::getInt('id');

		switch ($job)
		{
			case 'profile':
				$writer = $this->manageProfile();
				break;
			
			case 'manage_subscription':
				$writer = $this->manageSubscription($id);
				break;
				
			case 'post_subscription_prefs':
				$writer = $this->postSubscriptionPrefs($id);
				break;
			
			default:
				$writer = $this->getSubscriptionList();
		}
		
		return $writer->getHTML();
		
	}
	
	public function getDashVersionHTML()
	{
		return __CLASS__;
	}
	
	private function getSubscriptionList()
	{
		$writer = new StaysailWriter();
		$writer->start('panel')
			   ->h1('Your Subscriptions');
		
		$table = new StaysailTable();
		$table->setColumnHeaders(array('', 'Entertainer Stage Name', 'Expiration Date', 'Manage'));
		$subscriptions = $this->account->getActiveSubscriptions();
		foreach ($subscriptions as $Fan_Subscription)
		{
			$entertainer_link = StaysailWriter::makeLink($Fan_Subscription->Entertainer->name, $Fan_Subscription->Entertainer->getFanURL());
			$arr = array('Avatar' => $Fan_Subscription->Entertainer->Member->getAvatarHTML(Member::AVATAR_LITTLE),
			             'Entertainer' => $entertainer_link,
						 'Expiration' => date(SHORT_DATE_FORMAT, strtotime($Fan_Subscription->expire_time)),
						 'Manage Subscription' => StaysailWriter::makeJobLink('Manage', 'FanModule', 'manage_subscription', $Fan_Subscription->id),
					    );
			$table->addRow($arr);
		}
		$writer->draw($table)
		       ->end('panel');
		return $writer;
	}
	
	private function manageSubscription($fan_subscription_id)
	{
		$writer = new StaysailWriter();
		$writer->start('panel');
		$Fan_Subscription = new Fan_Subscription($fan_subscription_id);
		if ($Fan_Subscription->Fan->id == $this->account->id) {
			$name = $Fan_Subscription->Entertainer->name;
			$writer->h1("Manage Subscription for {$name}");
			$renew_date = date('M j, Y', strtotime($Fan_Subscription->expire_time));
			$form = new StaysailForm();
			$form->setSubmit('Update Preferences')
			     ->setPostMethod()
			     ->setDefaults($Fan_Subscription->info())
			     ->setJobAction('FanModule', 'post_subscription_prefs', $fan_subscription_id)
			     ->addField(StaysailForm::Bool, "Auto Renew on {$renew_date}", 'auto_renew')
			     ->addHTML("When the payment processor is in place, this will also contain a way to choose the credit card, etc.");
			$writer->draw($form);			     
		}
		$writer->end('panel');
		return $writer;
	}
	
	private function postSubscriptionPrefs($fan_subscription_id)
	{
		$Fan_Subscription = new Fan_Subscription($fan_subscription_id);
		if ($Fan_Subscription->Fan->id == $this->account->id) {
			$auto_renew = StaysailIO::post('auto_renew') ? 1 : 0;
			$Fan_Subscription->update(array('auto_renew' => $auto_renew));
			$Fan_Subscription->save();
		}
		return $this->getSubscriptionList();
	}
	
	private function manageProfile()
	{
		$writer = new StaysailWriter();
		$writer->draw($this->account->getProfileForm());
		return $writer;
	}
}