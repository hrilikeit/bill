<?php

class EntertainerTraining extends StaysailPublic
{
	protected $page, $settings, $categories;
    protected $framework;

    public $Member;
    public $Entertainer;
    
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
    }

    public function getHTML()
    {
    	
    	if ($this->Member->getRole() != Member::ROLE_ENTERTAINER) {return '';}
    	
    	$job = StaysailIO::get('job');
    	$id = StaysailIO::get('id');
    	$content_override = '';
    	
    	$map = Maps::getGalleryMap();
    	
    	switch ($job)
    	{
    		case 'video':
    			$content_override = $this->video($id);
    			break;
    			
    		case 'tips':
    			$content_override = $this->tips();
    			break;
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

		$content = $content_override ? (StaysailWriter::makeJobLink('&laquo; Back To Menu', __CLASS__) . $content_override) : $this->getMenu();
		$containers[] = new StaysailContainer('C', 'content', $content);
		$layout = new StaysailLayout($map, $containers);
		return $layout->getHTML();	
    }
    
    private function getMenu()
    {
    	$writer = new StaysailWriter('training');
    	
    	$table = new StaysailTable();
    	$table->addRow(array(StaysailWriter::makeJobLink('Webcam Tips<br/><img src="/site_img/Armani1.jpg" />', __CLASS__, 'tips'),
    	                     StaysailWriter::makeJobLink('Video: Setting the Stage<br/><img src="/site_img/video_screen1.jpg" />', __CLASS__, 'video', 1),
    	));
    	$table->addRow(array(StaysailWriter::makeJobLink('Video: Being Prepared<br/><img src="/site_img/video_screen2.jpg" />', __CLASS__, 'video', 2),
					       	 StaysailWriter::makeJobLink('Video: Performance Tips<br/><img src="/site_img/video_screen3.jpg" />', __CLASS__, 'video', 3)
		));
		
    	$writer->h1("Armani's Corner")
    		   ->h2("Tips and Videos")
    		   ->draw($table);
		return $writer->getHTML();    		   
    }
    
    private function video($video_number)
    {
    	StaysailIO::cleanse($video_number, StaysailIO::Int);
    	
		$html = <<<__END__
		<div id="container">Loading...</div>
		<script type="text/javascript">
		jwplayer("container").setup({
			flashplayer: "/jwplayer/player.swf", 
			file: "/site_video/training{$video_number}.flv",
			height: 390,
			width: 520,
			autostart: true,
    });
		</script>
__END__;
    	
		return $html;
    }
    
    private function tips()
    {
		$writer = new StaysailWriter();
		$writer->h1("Armani's Webcam Tips")
			   ->h2("Webcam Tip #1 - Have Fun")
			   ->p("Don't think of it as work. Think of it as flirting. Get on camera to have fun! Keep this your primary focus and you'll have no problem making plenty of money. Don't ever look bored or complain that it's slow or that you are having a bad day. Always smile and laugh.")
			   
		       ->addHTML(StaysailWriter::makeImage('/site_img/Armani2.jpg', 'Armani', 'page_image'))
			   ->h2("Webcam  Tip #2 - Be Prepared")
			   ->p("Never make your visitors wait. To stay on camera, keep your props, toys, or any changes to your outfit close by so that you won't have to go \"off cam\" to go get something.")

			   ->h2("Webcam  Tip #3 - Get Repeat Visitors")
			   ->p("Letting your clients know when you'll be back online (whether it's hours from now or another day) is an easy way to get paying customers to come back for more...and more! And when you do set a time and date, be sure to be on time. This helps build trust with your customers, meaning more money for you.")
			   
			   ->h2("Webcam  Tip #4 - Offer Phone to Phone")
			   ->p("Some of your clients may not have their speakers on or they just aren't working.  Offer phone to phone, this is also great to use to provide more of a 1 on 1 personal chat and a good way to keep your client on longer. Ask them if it would be better for you to call them while you are on chat. Once you get their number call them, and remain on cam the entire time you are on the phone with them.")

			   ->h2("Webcam  Tip #5 - Use Visitor's Name")
			   ->p("In free chat, make sure you use your customers' nickname. Visitors need to know that you're chatting to them personally. Also, learn their real names. Paying customers want to feel an emotional connection to you. Learning and memorizing names will get you more repeat paying customers.")

			   ->h2("Webcam  Tip #6 - Look Sexy")
			   ->p("Wear makeup and have your hair done. Also be sure to have a variety of sexy lingerie, outfits, see through clothing, etc. and change outfits from day to day to give customers variety. Bright colors such as red, orange, blue, green and pink attract more customers, so you should also be matching the colors of your sheets/curtains with your clothing. Do theme days and dress up...On sundays wear something related to football. Christmas red & white lil outfits, candycanes. things like that.")

			   ;
		return $writer->getHTML();
    }

}