<?php
class EntertainerSchedule extends LSFView
{
	public function getMainVersionHTML()
	{
		$writer = new StaysailWriter();
		$job = StaysailIO::get('job');
		$id = StaysailIO::getInt('id');
		
		switch ($job)
		{
			case 'edit_show':
				$writer = $this->getEditShowScreen($id);
				break;
		}
		
		return $writer->getHTML();
	}
	
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter();
		$schedule = $this->account->getShowSchedule();
		
		$table = new StaysailTable();
		$table->setColumnHeaders(array('Time', 'Edit'));
		$table->setColumnClasses(array('left', 'right'));
		foreach ($schedule as $Show_Schedule)
		{
			$edit = StaysailWriter::makeJobLink('Edit', 'EntertainerModule', 'edit_show&focus=EntertainerSchedule', $Show_Schedule->id);
			$start_end = $Show_Schedule->getStartEnd();
			$row = array('Time' => $start_end, 'Edit' => $edit);
			$row_class = $Show_Schedule->comingSoon() ? 'alert' : '';
			$table->addRow($row, $row_class);
		}
		
		$writer->start('panel')
			   ->h1('Show Schedule')
			   ->draw($table)
			   ->end('panel')
			   ->p(StaysailWriter::makeJobLink('Add Show', 'EntertainerModule', 'edit_show&focus=EntertainerSchedule'));
		return $writer->getHTML();
	}
	
	private function getEditShowScreen($show_id = null)
	{
		$writer = new StaysailWriter();
		$Show_Schedule = new Show_Schedule($show_id);
		if ($show_id and !$Show_Schedule->belongsTo($this->Member)) {
			$writer->h1('Sorry...')
				   ->p("You do not have access to this show schedule");
			return $writer;				   
		}
		
		$fans = $this->account->getSubscriberFans();
		$types = array('video' => 'Live Video', 'chat' => 'Live Chat');
		$show = new StaysailForm();
		$show->setJobAction('EntertainerModule', 'post_show', $show_id)
		      ->setSubmit($show_id ? 'Update Show' : 'Add Show')
		      ->setPostMethod()
		      ->setDefaults($Show_Schedule->info())
		      ->addField(StaysailForm::Line, 'Show Start Time', 'start_time', 'required')
		      ->addField(StaysailForm::Line, 'Show End Time', 'end_time', 'required')
		      ->addField(StaysailForm::Radio, 'Show Type', 'type', 'require-choice', $types)
		      ->addField(StaysailForm::Line, 'Maximum Participants', 'max_viewers')
		      ->addField(StaysailForm::Text, 'Description', 'description');
		if (sizeof($fans)) {
			array_unshift($fans, '--');
			$show->addField(StaysailForm::Select, 'Private Show For', 'Fan', '', $fans);
		}		      
		      
		$writer->h1($show_id ? 'Update a Show' : 'Add a Show')
			   ->p("Please enter your show data below.  For now, enter dates in the format YYYY-MM-DD hh:mm.  But shortly there will be a popup date picker with a visible calendar.")
			   ->draw($show);
		return $writer;	   
	}
		
}