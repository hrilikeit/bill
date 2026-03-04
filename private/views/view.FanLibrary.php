<?php

class FanLibrary extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		return $writer->getHTML();				
	}
	
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter();
		$writer->start('panel')
			   ->h1('Images');
		
		$purchased_library = $this->account->getPurchasedLibrary();
		if (sizeof($purchased_library)) {
			foreach ($purchased_library as $Library)
			{
				$link = StaysailWriter::makeLink($Library->getThumbnailHTML(), $Library->getFullSizeURL(), '', null, '_blank');
				$writer->addHTML($link);
			}
		} else {
			$writer->p("You have not yet purchased any high-resolution images.  You may buy them by subscribing to an entertainer's site.");
		}
		
		$writer->end('panel');
		return $writer->getHTML();
	}
}