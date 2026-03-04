<?php
class LiveChatView
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
	
	public function getHTML($private = false)
	{
		if ($private) {
			$chat_name = "Private Chat";
		} else {
			$chat_name = "{$this->Entertainer->name}'s Live Public Chat";
		}
		
		$writer = new StaysailWriter('upcoming');
		
		$size = "Text Size: <a class=\"text_small\" onclick=\"setChatSize(14)\">A</a> ";
    	$size .= "<a class=\"text_med\" onclick=\"setChatSize(16)\">A</a> ";
    	$size .= "<a class=\"text_large\" onclick=\"setChatSize(20)\">A</a>";
		$writer->addHTML($size);
    	
		$writer->start('header');
		$writer->addHTML("<div class=\"header_image\">" . $this->Entertainer->Member->getAvatarHTML(Member::AVATAR_TINY) . "</div>");
		$writer->addHTML("<div class=\"header_text\"><h2>{$chat_name}</h2></div>");
		$writer->end('header');
		$id = $this->Entertainer->id;
		
		$div = "<div id=\"online\"></div><script language=\"javascript\">startMeeting({$id})</script>";
		$writer->addHTML($div);
		$form = new StaysailForm();
		$form->setSubmit('Chat')
			 ->setPostMethod()
			 ->setAction("javascript:postChat({$id});")
		     ->addField(StaysailForm::Text, '', 'comment', 'required', '', array('onkeyup' => "monitorChat(event, {$id})"));
		$writer->draw($form);
		
		$writer->addHTML('<br/>&nbsp;&nbsp;');
		
		return $writer->getHTML();
	}	
}