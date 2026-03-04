<?php
/**
 * @package Staysail
 */

/**
 * A StaysailModule is a controller that functionally ties together
 * one or more domain entities.
 * 
 * StaysailModule is the base class for the administrative (back-end)
 * side of an application.
 * 
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
abstract class StaysailModule
{
	/**
	 * The version of the application.
	 * @var string
	 */
	protected $version;
	
	/**
	 * Context settings.
	 * 
	 * The "context" refers to the page for which a group of settings applies.
	 * 
	 * If a setting has defined values, the child class may implement
	 * a method with the same name as the setting, which returns an
	 * array of value=>label pairs.  If this method isn't found,
	 * the settings screen provides a single-line text field.
	 * 
	 * <code>
	 * <?php
	 * class Gallery extends StaysailModule
	 * {
	 *     protected $settings = array('Columns', 'Image Size');
	 *     
	 *     public function Image_Size() {return array(0 => 'Small', 1 => 'Large');}
	 * }
	 * ?>
	 * </code>
	 * 
	 * @var array
	 */
	protected $settings;
	
	/**
	 * An array of entities used by this Module.
	 * 
	 * The entity names are child classes of StaysailEntity.
	 * 
	 * By default, the administrative area provides an interface for
	 * each entity specified.  The interface can be suppressed if you
	 * enclose the entity name in parentheses.
	 * 
	 * <code>
	 * <?php
	 * class Venues extends StaysailModule
	 * {
	 *     protected $entities = array('Venue', '(Event)', '(Photo)');
	 * }
	 * ?>
	 * </code>
	 * 
	 * Note that entities may be shared among Modules.  One Module
	 * may suppress the interface for an entity, while another Module
	 * exposes the interface for the same entity.
	 * 
	 * @var array
	 */
	protected $entities;
	
	/**
	 * Each entity may have any number of Views associated with it.
	 * $views is an array of ('Entity' => array('View Name', 'etc.')).
	 * 
	 * The child class must implement a method for each View.  The name
	 * of this method is Entity_View_Name().  It must return an array
	 * of ('filters' => $array_of_Filters, 'fields' => $array_of_field_names).
	 * 
	 * <code>
	 * <?php
	 * class Members extends StaysailModule
	 * {
	 *     protected $entities = array('Member');
	 *     protected $views = array('Member' => array('By Last Name', 'By State'));
	 *     
	 *     public function Member_By_Last_Name()
	 *     {
	 *         return array('filters' => array(new Filter(Filter::Sort, 'last_name')),
	 *                      'fields' => array('last_name', 'first_name'));
	 *     }
	 *     
	 *     public function Member_By_State()
	 *     {
	 *         return array('filters' => array(new Filter(Filter::Sort, array('state', 'last_name'))),
	 *                      'fields' => array('state', 'name'));
	 *     }
	 * }
	 * ?>
	 * </code>
	 * 
	 * @see class Filter
	 * @var array
	 */
	protected $views;
	
	protected $setup_entities;
	
	/**
	 * Do nothing.
	 * 
	 * Constructor tasks, if any, are handled in the child class's
	 * constructor.
	 * 
	 * @param string $class
	 */
	public function __construct($class) {}
		
	/**
	 * Returns the settings array.
	 * 
	 * @see self::settings
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}
	
	/**
	 * Returns the entities array.
	 * 
	 * @see self::entities
	 * @return array
	 */
	public function getEntities()
	{
		return $this->entities;
	}
	
	/**
	 * Returns the setup_entities array
	 * 
	 * @see self::setup_entities
	 * @return array
	 */
	public function getSetupEntity()
	{
		return $this->setup_entities();
	}
	
	/**
	 * Return a list of views for the specified entity.
	 * 
	 * @see self::views
	 * @param string $entity
	 * @return array
	 */
	public function getViewsFor($entity)
	{
		if (isset($this->views[$entity])) {
			return $this->views[$entity];
		}
		return array();
	}	
	
	/**
	 * Return an array of Filters and field names for the specified
	 * entity and View name.
	 * 
	 * The returned array is in the format: 
	 * array('filters' => array(<Filter>), 'fields' => array(<string>))
	 * 
	 * @param string $entity
	 * @param string $view_name
	 * @return array
	 */
	public function getView($entity, $view_name)
	{
		if (!$view_name) {$view_name = 'Default';}
		$view_name = str_replace(' ', '_', $view_name);
		$fn_name = "{$entity}_{$view_name}";
		if (method_exists($this, $fn_name)) {
			return $this->$fn_name();
		}
		return array('filters' => array(), 'fields' => array('name'));
	}
}