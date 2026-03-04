<?php
class EntertainerSiteBody extends LSFView
{
	private $subscribed;
	
	public function setSubscribed($subscribed)
	{
		$this->subscribed = $subscribed;
	}
	
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		$writer->start('panel')
			   ->h1($this->account->name)
		       ->addHTML($this->account->bio)
		       ->end('panel');

		if ($this->subscribed) {
			$writer->start('panel')
				   ->h1('Community Chat')
				   ->draw($this->getPosts())
				   ->end('panel');
				   
			$writer->start('panel')
				   ->h1('Show Schedule')
				   ->draw($this->getSchedule())
				   ->end('panel');				   
		}		       

		return $writer->getHTML();
	}
	
	public function getPosts()
	{
		$writer = new StaysailWriter();
		$posts = $this->account->getPosts();
		if (sizeof($posts)) {
			foreach ($posts as $Post)
			{
				$writer->draw($Post);
				$writer->draw($Post->getFanReplyForm());
			}
		}		
		return $writer;
	}
	
	public function getSchedule()
	{
		$writer = new StaysailWriter();
		$schedule = $this->account->getShowSchedule();
		
		$table = new StaysailTable();
		$table->setColumnHeaders(array('Time', 'Name', 'Join'));
		$table->setColumnClasses(array('left', 'left', 'right'));
		foreach ($schedule as $Show_Schedule)
		{
			$join = StaysailWriter::makeLink('Join', '/?mode=FanModule&focus=FanCheckout&type=Show_Schedule&id=' . $Show_Schedule->id);
			$start_end = $Show_Schedule->getStartEnd();
			$row = array('Time' => $start_end, 'Name' => $Show_Schedule->name, 'Join' => $join);
			$row_class = $Show_Schedule->comingSoon() ? 'alert' : '';
			$table->addRow($row, $row_class);
		}
		$writer->draw($table);
		return $writer;
	}
}