<?php
class EntertainerSiteActions extends LSFView
{
	private $subscribed;
	
	public function setSubscribed($subscribed)
	{
		$this->subscribed = $subscribed;
	}
	
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter();
		$writer->start('panel')
			   ->h1('Interact');
		
		$links = array(
			StaysailWriter::makeLink($this->account->name, $this->account->getFanURL()),
			StaysailWriter::makeLink('Read Reviews', '/?mode=EntertainerSite&focus=EntertainerSiteReviews'),
			StaysailWriter::makeLink('Write a Review', '/?mode=FanModule&focus=FanWriteReview&type=Entertainer&id=' . $this->account->id),
		);
		if (!$this->subscribed) {
			array_unshift($links, StaysailWriter::makeLink('Become a Fan!', '/?mode=FanModule&focus=FanCheckout&type=Entertainer&id=' . $this->account->id));
		}
		
		foreach ($links as $link)
		{
			$writer->p($link);
		}
		$writer->end('panel');
		
		if ($this->subscribed) {
			$writer->start('panel')
				   ->h1('Fans Only');
			$links = array(
				StaysailWriter::makeLink('Send a Message', '/?mode=FanModule&job=compose_message'),
				StaysailWriter::makeLink('View Gallery', '/?mode=EntertainerSite&focus=EntertainerSiteGallery'),
			);
			foreach ($links as $link)
			{
				$writer->p($link);
			}
			$writer->end('panel');
		}
		

		return $writer->getHTML();
	}
}