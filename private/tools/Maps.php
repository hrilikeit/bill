<?php
/**
 * Layout maps for various types of pages
 *
 */
class Maps
{
	/**
	 * Fan profile screen map
	 * 
	 *   H = header
	 *   A = actions
	 *   E = favorite entertainer selection
	 *   L = subscribed entertainer list
	 *   R = club list
	 *   B = banner ads
	 *   F = footer
	 *   - = empty
	 * 
	 * @return string
	 */
	public static function getFanProfileMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAAEEEEEEEEEEEE-BBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getEntertainerHomeMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAALLLLLLLLLRRRRBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getEntertainerProfileMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAACCCCCCCCCCCCCBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getEntertainerProfileUpdateMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAALLLLLL-RRRRRRBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}

	public static function getGalleryMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAACCCCCCCCCCCCCBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getReportMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAACCCCCCCCCCCCCBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getClubProfileMap()
	{
		// This is the same as:
		return Maps::getEntertainerProfileMap();
	}
	
	public static function getLoginMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			LLLLLLLL---RRRRRRRR
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}

	public static function getPrivacyMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			CCCCCCCCCCCCCCCCCCC
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public function getClubAdminHomeMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAACCCCCCCCCCCCCBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}

    static function getMessageMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAALLLLDDDDDDDDDBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public static function getWebShowMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAALLLLLLLLLRRRRRRR
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
	
	public function getSearchResultsMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAACCCCCCCCCCCCCBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}

    static function getContractMap()
	{
		$map = <<<__END__
			HHHHHHHHHHHHHHHHHHH
			AAALLLLLLLLLLLLLBBB
			FFFFFFFFFFFFFFFFFFF
__END__;
		return $map;
	}
}