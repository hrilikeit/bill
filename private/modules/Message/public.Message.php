<?php

require '../private/views/MessagesView.php';

class Message extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Fan;
    public $Entertainer;
    public $valid;

    public function __construct($dbc = '')
    {
    	$this->valid = false;
        $this->framework = StaysailIO::engage();
        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
        $this->Fan = $this->Member->getAccountOfType('Fan');
        $this->Entertainer = $this->Member->getAccountOfType('Entertainer');
    }

    public function getHTML()
    {
        if ($this->Fan){
            $this->Member->requiredEmailVerify();
        }
        elseif($this->Entertainer){
            if ($this->Entertainer->requiredFields() == true){
                $this->Member->requiredEmailVerify();
            }
        }
    	
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');

    	$map = Maps::getMessageMap();
    	$content_override = '';

    	switch ($job)
    	{
    		case 'read':
    			$content_override = $this->readMessage($id);
    			break;

    		case 'compose':
    			$content_override = $this->composeMessage($id);
    			break;

    		case 'delete':
    			$content_override = $this->deleteMessage($id);
    			break;

    		case 'send':
    			$content_override = $this->sendMessage();
    			break;
       	}

    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView();
		$messages = new MessagesView($this->Member);

        $containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
            new StaysailContainer('F', 'footer', $footer->getHTML()),
            new StaysailContainer('A', 'action', $action->getHTML()),
            new StaysailContainer('B', 'banner', $banner->getHTML()),
            $content_override ? new StaysailContainer('L', 'detail', $content_override) : new StaysailContainer('L', 'detail', $messages->getHTML()),
            new StaysailContainer('D', 'list', $messages->getMessageListHTML()),
							);

//		if ($content_override) {
//			$containers[] = new StaysailContainer('D', 'detail', $content_override);
//		} else {
//			$containers[] = new StaysailContainer('D', 'detail', $messages->getHTML());
//		}

		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();
    }

    public function readMessage($Private_Message_id)
    {
    	$writer = new StaysailWriter();
    	$Private_Message = new Private_Message($Private_Message_id);

    	if ($this->Member->id == $Private_Message->to_Member_id or $this->Member->id == $Private_Message->from_Member_id) {
    		$writer->draw($Private_Message);
    	} else {
    		$writer->h1('Sorry...');
    		$writer->p('This message does not appear to be to you.');
    	}
    	return $writer->getHTML();

    }

    public function composeMessage($Private_Message_id = '')
    {
    	$writer = new StaysailWriter();
    	$form = new StaysailForm('compose_message');
    	if ($Private_Message_id) {
    		$Private_Message = new Private_Message($Private_Message_id);
    		if ($this->Member->id != $Private_Message->to_Member_id) {
    			$writer->p('This message does not appear to be to you.');
    			return $writer->getHTML();
    		}
    		$defaults = array('to_Member_id' => $Private_Message->from_Member_id,
    						  'content' => '',
    						  'name' => "Re: {$Private_Message->name}",
    						 );
			$writer->h1('Reply to Message');
    	} else {
    		$defaults = array();
    		$writer->h1('Compose Message');
    	}

    	if (StaysailIO::get('target')) {
    		$defaults['to_Member_id'] = StaysailIO::get('target');
    	}

    	if ($this->Member->getRole() == Member::ROLE_FAN) {
    		$recipient_list = $this->Fan->getEntertainerOptionList();
    	} else {
    		$recipient_list = $this->Entertainer->getFanOptionList();
    		$recipient_list['all'] = 'All Fans';
    	}
		$writer->addHTML($this->sizeControl());
    	$form->setPostMethod()
    		 ->setJobAction(__CLASS__, 'send')
    		 ->setDefaults($defaults)
    		 ->setSubmit('Send Private Message')
    		 ->addField(StaysailForm::Select, 'To', 'to_Member_id', 'required', $recipient_list)
    		 ->addField(StaysailForm::Line, 'Subject', 'name', 'required')
    		 ->addField(StaysailForm::Text, 'Message', 'content', 'required');
		$writer->draw($form);
		return $writer->getHTML();

    }

    private function sizeControl()
    {
    	$html = "Text Size: <a class=\"text_small\" onclick=\"changeTextarea(12)\">A</a> ";
    	$html .= "<a class=\"text_med\" onclick=\"changeTextarea(16)\">A</a> ";
    	$html .= "<a class=\"text_large\" onclick=\"changeTextarea(20)\">A</a>";
    	return $html;
    }

	public function sendMessage()
	{
		if (StaysailIO::post('to_Member_id') == 'all' and $this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$subscribers = $this->Entertainer->getSubscribers();
			foreach ($subscribers as $Fan_Subscription)
			{
				if ($Fan_Subscription->isBanned()) {continue;}
				$Fan = $Fan_Subscription->Fan;
				$recipient_id = $Fan->Member->id;
				$Private_Message = new Private_Message();
				$fields = array('name', 'content');
				$Private_Message->updateFrom($fields);
				$Private_Message->update(array('to_Member_id' => $recipient_id, 'send_time' => StaysailIO::now(), 'deleted' => 0, 'from_Member_id' => $this->Member->id));
				$Private_Message->save();
			}
		} else {
			$recipient_id = StaysailIO::post('to_Member_id');

			if ($this->Member->getRole() == Member::ROLE_FAN) {
				$Entertainer_Member = new Member($recipient_id);
				$Entertainer = $Entertainer_Member->getAccountOfType('Entertainer');
			    if ($this->Fan->isBannedFrom($Entertainer)) {
    				header("Location:?mode=FanHome&da={$Entertainer->id}");
    				exit;
    			}

//				if (!$Entertainer_Member->isOnline()) {
//					// If an Entertainer is the recipient, and she's not online, send an SMS
//					$from = 'LocalCityScene';
//					$message = "You have a new private message from {$this->Member->name}";
//					$SMSSender = new SMSSender($Entertainer_Member, $from);
//					$SMSSender->send($message);
//				}
			}

			$Private_Message = new Private_Message();
			$fields = array('name', 'content');
			$Private_Message->updateFrom($fields);
			$Private_Message->update(array('to_Member_id' => $recipient_id, 'send_time' => StaysailIO::now(), 'deleted' => 0, 'from_Member_id' => $this->Member->id));
			$Private_Message->save();

		}

		return null;
	}

	public function deleteMessage($Private_Message_id)
	{
    	$Private_Message = new Private_Message($Private_Message_id);

    	if ($this->Member->id == $Private_Message->to_Member_id) {
    		$Private_Message->markDeleted();
    		return null;
    	}

    	$writer = new StaysailWriter();
    	$writer->h1('Sorry...');
    	$writer->p('This message does not appear to be to you.');
    	return $writer->getHTML();
	}


}
