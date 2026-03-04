<?php
class TwitterView
{
	public $Member;
	
	public function __construct($Member = null)
	{
		$this->Member = $Member;
	}
	
	public function getHTML()
	{
		$html = '';
		
		$html .= <<<__END__
<!--            <a class="twitter-timeline" data-width="220" data-height="200" data-theme="dark" href="https://twitter.com/LocalStripFan?ref_src=twsrc%5Etfw">Tweets by LocalStripFan</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>-->
__END__;

		return $html;
	}
}