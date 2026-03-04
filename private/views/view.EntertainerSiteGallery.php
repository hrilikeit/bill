<?php
class EntertainerSiteGallery extends LSFView
{
	private $subscribed;
	
	public function setSubscribed($subscribed)
	{
		$this->subscribed = $subscribed;
	}
	
	public function getMainVersionHTML()
	{
		if (!$this->subscribed) {return '';}
		
		$writer = new StaysailWriter();
		$writer->start('panel')
			   ->h1($this->account->name . "'s Gallery")
		       ->draw($this->getGallery())
		       ->end('panel');

		return $writer->getHTML();
	}
	
	public function getGallery()
	{
		$writer = new StaysailWriter();
		$gallery = $this->account->getGallery();
		foreach ($gallery as $Library)
		{
			$writer->addHTML($Library->getGalleryEntry($this->Member));
		}
		return $writer;
	}
	
}