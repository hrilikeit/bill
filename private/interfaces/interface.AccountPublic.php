<?php
/**
 * This interface specifies the required methods for the three
 * account interfaces.
 * 
 * @author jason
 *
 */
interface AccountPublic
{
	/**
	 * Returns HTML for populating the left-hand panel
	 * 
	 * @return string
	 */
	public function getLeftPanelHTML();
	
	/**
	 * Returns HTML for populating the right-hand panel
	 * 
	 * @return string
	 */
	public function getRightPanelHTML();
}