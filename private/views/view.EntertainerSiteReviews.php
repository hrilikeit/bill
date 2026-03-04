<?php

class EntertainerSiteReviews extends LSFView
{
	public function setSubscribed($bool)
	{
		// That doesn't matter here
	}
	
	
	public function getDashVersionHTML()
	{
		return __CLASS__;
	}
	
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		$review_summary = $this->account->getReviewSummary();
		
		$writer->start('panel')
			   ->h1("Reviews for {$this->account->name}")
			   ->h2("Total Rating: {$this->account->getRating()}")
			   ->draw($review_summary)
			   ->end('panel');
		return $writer->getHTML();			   
	}
}