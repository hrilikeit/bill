<?php

class LSFView
{
	protected $Member, $account;
	protected $version;
	
	const MainVersion = 1;
	const DashVersion = 2;
	
	public function __construct(Member $Member, AccountType $account, $version)
	{
		$this->Member = $Member;
		$this->account = $account;
		$this->version = $version;
	}
	
	public function getHTML()
	{
		switch ($this->version)
		{
			case self::DashVersion:
				return $this->getDashVersionHTML();
				break;
			
			case self::MainVersion:
			default:
				return $this->getMainVersionHTML();
				break;
		}
	}
	
	public function getMainVersionHTML()
	{
		return '[<strong>' . get_class($this) . '</strong> Main]';
	}
	
	public function getDashVersionHTML()
	{
		return '[<strong>' . get_class($this) . '</strong> Dash]';
	}
}