<?php

class EntertainerBio extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		if (StaysailIO::get('job') == 'edit_bio') {
			$bio = new StaysailForm();
			$bio->setSubmit('Update Bio')
				->setPostMethod()
				->setDefaults($this->account->info())
				->setJobAction('EntertainerModule', 'post_bio')
				->addField(StaysailForm::Text, 'Bio', 'bio', 'richtext');
			$writer->draw($bio);	
		} else {
			$writer->start('panel')
				   ->h1('Your Bio')
				   ->start('bio')
				   ->addHTML($this->account->bio)
				   ->end('bio')
				   ->end('panel')
				   ->p(StaysailWriter::makeJobLink('Edit Bio', 'EntertainerModule', "edit_bio&focus=" . __CLASS__));
		}
		return $writer->getHTML();				
	}
}