<?php

class EventsView
{
	private $Member;
	private $entity; // Entertainer or Club object
	private $type;
	
	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		if (StaysailIO::get('mode') == 'ClubProfile') {
			$this->type = 'Club';
			if (StaysailIO::session('Club.id')) {
				$this->entity = new Club(StaysailIO::session('Club.id'));
			} elseif (StaysailIO::get('club_id')) {
				$this->entity = new Club(StaysailIO::get('club_id'));
			}
		}
		
		if (!$this->entity) {
			$this->type = 'Entertainer';
			if (StaysailIO::session('Entertainer.id')) {
				$this->entity = new Entertainer(StaysailIO::session('Entertainer.id'));
			} else {
				$this->entity = $this->Member->getAccountOfType('Entertainer');
			}
		}
	}
	
	public function getHTML()
	{
		$writer = new StaysailWriter('upcoming');
		$writer->start('header');
		$writer->addHTML("<div class=\"header_image\">" . Icon::show(Icon::EVENT, Icon::SIZE_LARGE) . "</div>");
		$writer->addHTML("<div class=\"header_text\"><h2>Events</h2></div>");
		$writer->end('header');
		
		$writer->addHTML($this->getCalendar());
		
		return $writer->getHTML();
	}
	
	public function getCalendar()
	{
		$html = '';
		
		if (StaysailIO::get('cal')) {
			$cal = StaysailIO::get('cal');
			$month = substr($cal, 0, 2);
			$year = substr($cal, 3, 4);
		} else {
			$month = date('m');
			$year = date('Y');
		}
		
		$days = array('01' => 31, '02' => 28, '03' => 31, '04' => 30, '05' => 31, '06' => 30,
					  '07' => 31, '08' => 31, '09' => 30, '10' => 31, '11' => 30, '12' => 31);
		$start_date = strtotime("{$year}-{$month}-01");
		if (date('L', $start_date)) {$days['02'] += 1;} // Leap year
		$start_dow = date('w', $start_date);
		$end_day = isset($days[$month]) ? $days[$month] : null;
		
		if (!$end_day) {return '';}
		
		$month_year = date('F Y', $start_date);
		
		$dom = 0;
		$done = $started = false;
		$html .= '<div class="cal">';
		$html .= "<div class=\"cal_heading\">{$month_year}</div>";
		while (!$done) 
		{
			$html .= '<div class="cal_week">';
			// Add a week
			for ($dow = 0; $dow < 7; $dow++)
			{
				if (!$started and $dow == $start_dow) {$started = true;}
				
				if ($started and !$done) {
					$dom++;
					$domlink = $dom;
					$shows = $this->entity->getShowsOnDay($year, $month, $dom);
					if (sizeof($shows)) {
						$event = 'shows ' . $shows[0]->type;
						$domlink = "<a href=\"?mode={$this->type}Profile&job=events&yy={$year}&mm={$month}&dd={$dom}\">{$dom}</a>";
					} else {$event = '';}
					$html .= "<div class=\"cal_day {$event}\"><span>{$domlink}</span></div>";
				} else {
					$html .= "<div class=\"cal_day_empty\">&nbsp;</div>";
				}
				if ($dom == $end_day) {$done = true;}
			}
			$html .= '</div>';
		}
		$html .= '</div>&nbsp;';
		return $html;
	}
	
}