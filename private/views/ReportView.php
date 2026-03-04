<?php
class ReportView
{
	private $Member;
	private $Entertainer;
	private $Club_Admin;
	private $Fan;
	private $Club;
	private $reports;
	
	public function __construct(Member $Member)
	{
		$this->Member = $Member;
		$this->Entertainer = null;
		$this->Club_Admin = null;
		
		if ($this->Member->getRole() == Member::ROLE_ENTERTAINER) {
			$this->reports = array('DailySalesCreator' => 'Daily Sales for Creator',
								  );
			
			if (StaysailIO::session('Entertainer.id')) {
				$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
			} else {
				$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
			}
		}
        if ($this->Member->getRole() == Member::ROLE_FAN) {
            $this->reports = array('DailySalesFan' => 'Daily Sales for Fan',);

            if (StaysailIO::session('Fan.id')) {
                $this->Fan = new Fan(StaysailIO::session('Fan.id'));
            } else {
                $this->Fan = $this->Member->getAccountOfType('Fan');
            }
        }
		if ($this->Member->getRole() == Member::ROLE_CLUB) {
			$this->reports = array('ClubBreakdown' => 'Sales Breakdown',
								  );
			$this->Club_Admin = $this->Member->getAccountOfType('Club_Admin');
			$this->Club = $this->Club_Admin->Club;
		}
		
		
	}

	public function getHTML()
	{
		$writer = new StaysailWriter();

		$classname = StaysailIO::get('job');
		if ($classname) {
	    	StaysailIO::cleanse($classname, StaysailIO::Filename);
	    	$filepath = "../private/reports/report.{$classname}.php";
	    	if (file_exists($filepath)) {
	    		require $filepath;
	    		$Report = new $classname();
	    		$range = $this->getRangeString();
	    		if ($this->Entertainer) {
                    StaysailIO::setSession('entertainer_creator_id', $this->Entertainer->id);
                    //$Report->setParameters(array('CLUB_ID' => $this->Club->id, 'RANGE' => $range));
//		    		$Report->setParameters(array('ENTERTAINER_ID' => $this->Entertainer->id,
//		    								     'MEMBER_ID' => $this->Entertainer->Member->id,
//		    									 'RANGE' => $range,
//		    							  ));

	    		} else {
		    		$Report->setParameters(array('CLUB_ID' => $this->Club->id, 'RANGE' => $range));
	    		}
	    		$writer->p($writer->makeJobLink('&laquo; Other Reports', 'ReportModule'));
	    		$writer->h1($Report->getName());
	    		//$writer->p($this->getDateSelector());
	    		$writer->draw($Report);
	    	} else {
	    		$writer->h2('Report not found');
	    		$classname = '';
	    	}
		}

		if (!$classname) {
			$writer->h1('Reports');
			$list = array();
			foreach ($this->reports as $job => $name)
			{
				$link = $writer->makeJobLink($name, 'ReportModule', $job);
				$list[] = $link;
			}
			$writer->ul($list);
		}

		return $writer->getHTML();
	}
	public function getCreatorHTML()
	{
		$writer = new StaysailWriter();

		$classname = StaysailIO::get('job');
		if ($classname) {
	    	StaysailIO::cleanse($classname, StaysailIO::Filename);
	    	$filepath = "../private/reports/creator/report.{$classname}.php";
	    	if (file_exists($filepath)) {
	    		require $filepath;
	    		$Report = new $classname();
	    		$range = $this->getRangeString();
	    		if ($this->Entertainer) {
                    StaysailIO::setSession('entertainer_creator_id', $this->Entertainer->id);
                    //$Report->setParameters(array('CLUB_ID' => $this->Club->id, 'RANGE' => $range));
//		    		$Report->setParameters(array('ENTERTAINER_ID' => $this->Entertainer->id,
//		    								     'MEMBER_ID' => $this->Entertainer->Member->id,
//		    									 'RANGE' => $range,
//		    							  ));

	    		} else if ($this->Club){
                    $Report->setParameters(array('CLUB_ID' => $this->Club->id, 'RANGE' => $range));
                } else if ($this->Fan){
                    StaysailIO::setSession('fan_id', $this->Fan->id);
                }
                $writer->p($writer->makeJobLink('&laquo; Other Reports', 'ReportModule'));
                $writer->h1($Report->getName());
	    		//$writer->p($this->getDateSelector());
	    		$writer->draw($Report);
	    	} else {
	    		$writer->h2('Report not found');
	    		$classname = '';
	    	}
		}

		if (!$classname) {
			$writer->h1('Reports');
			$list = array();
			foreach ($this->reports as $job => $name)
			{
				$link = $writer->makeJobLink($name, 'ReportModule', $job);
				$list[] = $link;
			}
			$writer->ul($list);
		}

		return $writer->getHTML();
	}

	public function getDateSelector()
	{
		$html = '<form method="post" action="?mode=ReportModule&job=' . StaysailIO::get('job') . '" id="range_find"><select name="range" onchange="el(\'range_find\').submit()"><option value="ALL">-- ALL --</option>';
		$this_year = date('Y');
		$this_month = date('n');
		$default = StaysailIO::post('range');
		$months = array('', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec');
		for ($year = $this_year - 2; $year <= $this_year; $year++)
		{
			for ($month = 1; $month <= 12; $month++)
			{
				if ($year == $this_year and $month > $this_month) {continue;}
				$pad_month = str_pad($month, 2, '0', STR_PAD_LEFT);
				$label = "{$months[$month]} {$year}";
				$range = "{$year}-{$pad_month}";
				$selected = $range == $default ? 'selected="selected"' : '';
				$html .= "<option value=\"{$range}\" {$selected}>{$label}</option>\n";
			}
		}
		$html .= "</select>";
		return $html;
	}
	
	public function getRangeString()
	{
		$range = StaysailIO::post('range');
		
		if ($range == 'ALL') {
			return '';
		}
				
		if (!$range) {
			$range = date('Y-m');
		}
		$range_string = "AND (order_time >= '{$range}-01' AND order_time <= LAST_DAY({$range}))";
		return $range_string;
	}
}