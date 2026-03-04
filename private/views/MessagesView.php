<?php
class MessagesView
{
	private $Member;
	private $Entertainer;

	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
	}

	public function getHTML()
	{
		$writer = new StaysailWriter();
		$writer->h1('Messages');
		//$writer->p('Click on a message to the left to view it, or click the Compose Message button below.');
		$button = "<a href=\"?mode=Message&job=compose\" class=\"button\">Write new Message</a>";
		$writer->addHTML($button);

		return $writer->getHTML();
	}

	public function getMessageListHTML()
	{
		$writer = new StaysailWriter();

		$messages = $this->Member->getMessages();


		$inbox = $read = $sent = '';

		foreach ($messages as $Private_Message)
		{
			$selector_html = $Private_Message->getSelectorHTML();
			if ($Private_Message->receive_time) {
				$read .= $selector_html;
			} else {
				$inbox .= $selector_html;
			}
		}

		$sent_messages = $this->Member->getSentMessages();
		foreach ($sent_messages as $Private_Message)
		{
			$selector_html = $Private_Message->getSelectorHTML(true);
			$sent .= $selector_html;
		}

		$writer->h1('New Messages', 'inbox_new');
		$writer->addHTML($inbox);
		if (!$inbox) {
			$writer->p('You have no messages in your Inbox.');
		}

		$writer->h1('Read Messages', 'inbox_read');
		$writer->addHTML($read);
		if (!$read) {
			$writer->p('You have no read messages.');
		}

		$writer->h1('Sent Messages', 'inbox_sent');
		$writer->addHTML($sent);
		if (!$sent) {
			$writer->p('You have no sent messages.');
		}

		return $writer->getHTML();
	}
}