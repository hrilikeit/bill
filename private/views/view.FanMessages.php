<?php

class FanMessages extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		return $writer->getHTML();				
	}
	
	public function getDashVersionHTML()
	{
		return __CLASS__;
	}
}