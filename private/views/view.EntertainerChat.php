<?php
class EntertainerChat extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter(__CLASS__);
		
		$writer->start('panel');
		$writer->h1('Community Chat');
		
		// Add new chat post
		$postform = new StaysailForm();
		$postform->setSubmit('Post')
				 ->setPostMethod()
				 ->setJobAction('EntertainerModule', 'post_reply', 0)
				 ->addField(StaysailForm::Text, '', 'content');
		$writer->draw($postform);	

		$posts = $this->account->getPosts();
		if (sizeof($posts)) {
			$writer->h2('Recent Posts');
			foreach ($posts as $Post)
			{
				$writer->draw($Post);
			}
		}
		$writer->end('panel');
		
		return $writer->getHTML();
	}
	
}