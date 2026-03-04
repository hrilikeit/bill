<?php
class Icon
{
	const SIZE_LARGE = 32;
	const SIZE_SMALL = 16;
	
	// Icon filenames used in this application
	const SETTINGS = '14.png';
	const STATUS = '15.png';
	const PHOTOS = '50.png';
	const ADD_CLUB = 'add_32.png';
	const VIDEOS = 'video-icon.png';
    const VIDEO_STORE = 'video-store.png';
	const EVENT = '20.png';
	const ENTERTAINER_OF_THE_MONTH = '43.png';
	const VOTE = 'chart_flipped_32.png';
	const MESSAGES = '42.png';
	const REVIEWS = 'comment_user_32.png';
	const LIVE_CHATS = '19.png';
	const GROUP_CHATS = '21.png';
	const VIDEO_CHATS = '17.png';
	const FOLLOWERS = 'heart_32.png';
	const COWORKERS = '19.png';
	const CLUBS = 'home_32.png';
	const ADMIN = '14.png';
	const PRICING = 'pencil_32.png';
	const ACCOUNT = 'chart_flipped_32.png';
	const TRAINING = 'info_button_16.png';
	const END_VIDEO = 'web_layout_error_32_close.png';
	
	const STAR_FULL = 'star_16.png';
	const STAR_HALF = 'star_half_16.png';
	const STAR_OFF = 'star_off16.png';
    const FOLDER = 'folder.png'; //@TODO add png
    const FOLDER_GREEN = 'green-folder.png'; //@TODO add png

	public static function show($icon, $size = self::SIZE_SMALL, $name = '', $link = '', $js = '')
	{
		$resize = $size ? "height=\"{$size}\" width=\"{$size}\"" : '';
		$img = "<img border=\"0\" src=\"/site_img/icons/{$icon}\" alt=\"{$name}\" {$resize} />";
		if ($link or $js) {
			$href_el = $link ? "href=\"{$link}\"" : '';
			$onclick_el = $js ? "onclick=\"{$js}\"" : '';
			$img = "<a {$href_el} {$onclick_el}>{$img}</a>";
		}
		return $img;
	}
	
	public static function getStarRatingHTML($rating, $max = 5)
	{
		$html = '';
		$half = ($rating - intval($rating) > .40) ? (intval($rating) + 1) : 0;
		$rating = intval($rating);
		for ($i = 1; $i <= $max; $i++)
		{
			if ($rating >= $i) {
				$icon = self::STAR_FULL;
			} else {
				$icon = ($half == $i) ? self::STAR_HALF : self::STAR_OFF;
			}
			$html .= "<img src=\"/site_img/icons/{$icon}\" />";
		}
		return $html;
	}
}