<?php
class ClubEntertainers extends LSFView
{
	private $Club;
	
	public function setClub(Club $club)
	{
		$this->Club = $club;
	}
	
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		
		
		$writer->start('panel')
			   ->h1($this->Club->name)
			   ->draw($this->getEntertainerScreen())
			   ->end('panel');
		return $writer->getHTML();
	}
	
	public function getEntertainerScreen()
	{
		$writer = new StaysailWriter();
		$entertainers = $this->Club->getEntertainers();
		foreach ($entertainers as $Entertainer)
		{
			$writer->draw($Entertainer->getClubLink());
		}
		return $writer;
	}
	
}