<?php
class BannerAds extends LSFView
{
	public function getDashVersionHTML()
	{
		$writer = new StaysailWriter();
		for ($i = 1; $i < 4; $i++)
		{
			$url = "/ads/Banner-{$i}.jpg";
			$writer->addHTML(StaysailWriter::makeImage($url, 'Banner Ad'));
		}
		return $writer->getHTML();
	}
}