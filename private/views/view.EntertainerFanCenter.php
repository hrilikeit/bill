<?php
class EntertainerFanCenter extends LSFView
{
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter();
		
		$subscribers = $this->account->getSubscribers();
		
		$table = new StaysailTable();
		$table->setColumnHeaders(array('Name', 'Expires'));
		$table->setColumnClasses(array('left', 'right'));
		foreach ($subscribers as $Fan_Subscription)
		{
			$Fan = $Fan_Subscription->Fan;
			$link = StaysailWriter::makeJobLink($Fan->name, 'EntertainerModule', '&focus=' . __CLASS__, $Fan_Subscription->id);
			$row = array('Name' => $link, 'End Date' => $Fan_Subscription->getShortExpireDate());
			$table->addRow($row);
		}
		
		$writer->start('panel')
			   ->h1('Fan Center')
			   ->draw($table)
			   ->end('panel');
		return $writer->getHTML();
	}
}