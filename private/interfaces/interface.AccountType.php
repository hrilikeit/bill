<?php 
/**
 * The LSF account type classes are:
 *     Fan
 *     Entertainer
 *     Club_Admin
 * 
 * @author jason
 *
 */
interface AccountType
{
	/**
	 * Returns the profile form for the account type.
	 * 
	 * Mode and job are optional; but if they are not set by a call to
	 * getProfileForm(), then they should be sent by the caller.
	 * 
	 * @param string $mode
	 * @param string $job
	 * @return StaysailForm
	 */
	public function getProfileForm($mode = '', $job = '');
	
	/**
	 * Saves the profile data based on the form returned in getProfileForm()
	 * 
	 * @see getProfileForm()
	 * @return void
	 */
	public function saveProfile();
	
	/**
	 * Sets the session data for the account type (the Domain entity of
	 * the account) and the entity's id.
	 * 
	 * When a Member logs in, the Member's session is connected to
	 * a Fan, Entertainer, or Club Admin.
	 * 
	 * @return void
	 */
	public function registerSession();
	
}