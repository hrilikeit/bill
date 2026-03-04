<?php
/**
 * @package Staysail
 */

/**
 * A StaysailPublic is a controller that functionally ties together
 * one or more domain entities.
 * 
 * StaysailPublic is the base class for the public (front-end) side
 * of an application.
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */

class StaysailPublic 
{
	/**
	 * Metadata about the web page that instantiates the child class.
	 * @var array
	 */
	protected $page;
	
	/**
	 * Context settings selected on the web page that instantiates the child class.
	 * @see StaysailModule::settings
	 * @var array
	 */
	protected $settings;
	
	/**
	 * The available categories (not the page's categories).
	 * 
	 * $categories is an array of this format:
	 * <code>
	 * array(category_id => 
	 * array('name' => $name, 
	 * 'parent_id' => $parent_id, 
	 * 'class' => $class, 
	 * 'sort' => $sort))
	 * 
	 * </code>
	 * 
	 * @var array
	 */
	protected $categories;
	
	/**
	 * A reference to the active StaysailIO instance.
	 * @var StaysailIO
	 */
	protected $framework;

    /**
     * @var
     */
    protected $valid;
	
	/**
	 * Engage the StaysailIO with the specified database settings.
	 * 
	 * $dbc is an array of ('host' => $host, 'user' => $user, 'pass' => $pass, 'name' => $name).
	 * 
	 * @param array $dbc
	 */
	public function __construct($dbc) 
	{
		if (is_array($dbc)) {
			$this->framework = StaysailIO::engage($dbc['host'], $dbc['user'], $dbc['pass'], $dbc['name'] );
		}
	}
	
	/**
	 * Set the context settings for the page.
	 * 
	 * $settings is an array of key=>value pairs.
	 * 
	 * @see self::settings
	 * @param array $settings
	 */
	public function setSettings($settings) {$this->settings = $settings;}
	
	/**
	 * Set the metadata for the page.
	 * 
	 * $page is an array of key=>value pairs.
	 * 
	 * @see self::page
	 * @param array $page
	 */
	public function setPage($page) {$this->page = $page;}
	
	/**
	 * Set the available categories.
	 * 
	 * @see self::categories  
	 * @param array $categories
	 */
	public function setCategories($categories) {$this->categories = $categories;}
	
	/**
	 * Return the context setting with the specified key, if it exists.
	 * 
	 * @param string $setting
	 * @return mixed
	 */
	public function setting($setting) 
	{
		if (isset($this->settings[$setting])) {
			return $this->settings[$setting];
		}
		return '';
	}
	
	/**
	 * Return the page metadata parameter with the specified key, if it exists.
	 * 
	 * @param string $param
	 * @return mixed
	 */
	public function page($param) 
	{
		if (isset($this->page[$param])) {
			return $this->page[$param];
		}
		return '';
	}
	
	/**
	 * Return a subset of categories that match all of the
	 * specified match pairs.
	 * 
	 * The $match array is an array of key=>value.  The returned
	 * list has the same format as the category array, and contains
	 * any categories that match all of the match pairs.
	 * 
	 * The valid keys for the match array are: id, name, parent_id,
	 * class, and sort.
	 * 
	 * @see self::category
	 * @param array $match
	 * @return array
	 */
	public function getCategories($match) 
	{
		$category_subset = array ();
		foreach ($this->categories as $category_id => $category) 
		{
			$category ['id'] = $category_id;
			$found = true;
			foreach ($match as $key => $value) {
				if (isset($category[$key]) and $category[$key] != $value) {
					$found = false;
				}
			}
			if ($found) {
				$category_subset [$category_id] = $category;
			}
		}
		return $category_subset;
	}
}

/**
 * Autoload methods for domain entities, so that they don't need to be
 * explicitly included within Staysail code.
 * 
 * @param string $entity
 */
spl_autoload_register('Staysail_autoload');
function Staysail_autoload($entity)
{
	if (defined('DOCROOT')) {
		$domain_path = DOCROOT . "/private/domain/class.{$entity}.php";
		$module_path = DOCROOT . "/private/modules/{$entity}/admin.{$entity}.php";
		if (file_exists($domain_path)) {
			require_once $domain_path;
		} elseif (file_exists($module_path)) {
			require_once $module_path;
		}
	} else {
		print "DOCROOT undefined";
	}
}
?>