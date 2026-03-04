<?php

require '../private/views/ReportView.php';

class ReportModule extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    public $Fan;
    public $valid;

    public function __construct($dbc = '')
    {
    	$this->valid = false;

        $this->framework = StaysailIO::engage();

        $member_id = StaysailIO::session('Member.id');
        $this->Member = new Member($member_id);
		if (StaysailIO::session('Entertainer.id')) {
			$this->Entertainer = new Entertainer(StaysailIO::session('Entertainer.id'));
		} else {
			$this->Entertainer = $this->Member->getAccountOfType('Entertainer');
		}
        if (StaysailIO::session('Fan.id')) {
            $this->Fan = new Fan(StaysailIO::session('Fan.id'));
        } else {
            $this->Fan = $this->Member->getAccountOfType('Fan');
        }

        if ($this->Member and $this->Entertainer) {
        	$this->valid = true;
        }
    }

    public function getHTML()
    {
        if(!is_bool($this->Entertainer) && is_bool( $this->Fan)){
            if ($this->Entertainer->requiredFields() == true){
                $this->Member->requiredEmailVerify();
            }
            if (!Member_Docs::hasMemberDocs($this->framework, $this->Member->id)) {
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            }
            if ($this->Entertainer->checkContract() == false){
                header("Location:?mode=EntertainerProfile&job=update_bio");
                exit;
            }
        }
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	$content_override = '';

    	$map = Maps::getReportMap();

    	switch ($job)
    	{
    	}

    	$header = new HeaderView();
    	$footer = new FooterView();
    	$action = new ActionsView($this->Member);
    	$banner = new BannerAdsView();

		$containers = array(new StaysailContainer('H', 'header', $header->getHTML()),
							new StaysailContainer('F', 'footer', $footer->getHTML()),
							new StaysailContainer('A', 'action', $action->getHTML()),
							new StaysailContainer('B', 'banner', $banner->getHTML()),
							);

		$report = new ReportView($this->Member);
		$content = $content_override ? $content_override : $report->getCreatorHTML();
		$containers[] = new StaysailContainer('C', 'content', $content);
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();
    }


}
