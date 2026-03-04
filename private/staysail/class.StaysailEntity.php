<?php
/**
 * @package Staysail
 */

/**
 * Base class for the Staysail application domain classes.
 *
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
abstract class StaysailEntity
{
	// Data Types
	const Int = 1;
	const Float = 2;
	const Boolean = 3;
	const Enum = 4;
	const Currency = 15;

	const Line = 5;
	const Text = 6;
	const Richtext = 7;
	const File = 8;

	const Date = 9;
	const Time = 10;
	const Timestamp = 11;

	const AssignOne = 12;
	const AssignMany = 13;
	const Collection = 14;

	const DEFINITIONS = 'definitions';

	public $id;
	protected $_framework;                   // Reference to Staysail Framework
//    protected $db;                           // Add PDO
    protected $_class = __CLASS__;           // Name of final class
    protected $_fields = array();            // Associative array of array(fieldname => options)
    protected $_info = array();              // Current unsaved values
    protected $_save = array();              // Last saved values
    protected $_junctions = array();         // Array of junction (many-to-many) relationships
    protected $_collections = array();       // Array of collection (many-to-one) relationships
    protected $_category_ids = array();      // Array of category IDs
    protected $_library_paths = array();     // Array of library paths for specific File fields
    protected $_library_prefixes = array();  // Array of file name prefixes for specific File fields
    protected $_primary_category_id = null;  // Primary Category

    // Default properties.  Override these in the final class to change from default:
    protected $_name_template = null;        // Describes construction of 'name' field
    protected $_sort = 'sort';               // Default sort fields
	protected $_plural = null;               // Plural form of the class name (adds "s" if null)

    /**
     * Construct a StaysailEntity.
     *
     * This constructor should be called by the constructor of the
     * domain entity class, which is a child class of StaysailEntity.
     *
     * It performs various initialization tasks for the domain entity.
     *
     * @param string $class
     * @param int $id
     * @return StaysailEntity
     */
    public function __construct($class, $id = null)
    {
        $this->_class = $class;
		$this->_framework = StaysailIO::engage();
//        //TODO
//        $this->db = StaysailIO_PDO::engage();
		$this->_info = array();
		if (!$this->_plural) {$this->_plural = "{$class}s";}

        if ($id == self::DEFINITIONS) {
            return false;
        }

        // Set field definitions array
        foreach (array_keys(get_class_vars($this->_class)) as $fieldname)
        {
            if (substr($fieldname, 0, 1) == '_') {continue;}
        	$type = $this->$fieldname;
        	if ($fieldname == 'id') {continue;}
        	if ($type == self::Collection) {
        		$this->_collections[] = $fieldname;
        		continue;
        	}
            $this->_fields[$fieldname] = $type;
            if ($type == self::AssignMany) {$this->_junctions[] = $fieldname;}
            unset($this->$fieldname); // Unset to activate __set() and __get()
        }

        try {
            $this->pullData($id);
        } catch (Exception $e) {
            // The data pull failed, meaning that the ID probably wasn't found
            $this->id = null;
        }
    }

	/**
	 * Get entity information from database and populate the
	 * instance fields.  If the entity can't be created, this method
	 * throws an exception so that the constructor doesn't fatally fail.
	 *
	 * pullData is private, and is only called by the constructor.
	 *
	 * @param int $id
	 * @throws Exception
	 */
	private function pullData($id = null)
	{
		if ($id) {
			// Populate for existing entity
			$info = $this->_framework->getRowByID($this->_class, $id);
            if (isset($info['id'])) {
				$this->id = $id;
				unset($info['id']);
				$this->_info = $this->_save = $info;
				$parent_id = "{$this->_class}_id";
				foreach ($this->_junctions as $target_table)
				{
					$members = $this->loadMembers($target_table);
					$this->_info["{$target_table}_id"] = $members;
					$this->_save["{$target_table}_id"] = $members;
				}
			} else {
				throw new Exception("{$this->_class} not found");
			}
		} else {
			// Populate blank for new entity
			$this->id = 0;
			foreach ($this->_fields as $fieldname => $type)
			{
				if ($type == self::AssignOne) {
					$fieldname = "{$fieldname}_id";
					$this->_info[$fieldname] = $this->_save[$fieldname] = '';
				}
				if ($type == self::AssignMany) {
					$this->_info[$fieldname] = $this->_save[$fieldname] = array();
				} else {
					$this->_info[$fieldname] = $this->_save[$fieldname] = '';
				}
			}

			foreach ($this->_junctions as $target_table)
			{
				$this->_info["{$target_table}_id"] = $this->_save["{$target_table}_id"] = array();
			}

			$this->_category_ids = $this->_primary_category_id = null;
		}
	}

	/**
	 * Return the array of saved data or, if the entity hasn't been saved,
	 * the array of current data.
	 *
	 * @return array
	 */
	public function info()
	{
		if (!$this->id) {return $this->_info;}
		return $this->_save;
	}

	/**
	 * Return the object fields as a fieldname=>type array.
	 *
	 * @return array
	 */
	public function fields() {return $this->_fields;}

    /**
     * Universal field setter for $Entity->fieldname = $value
     *
     * @param string $fieldname
     * @param mixed $value
     */
    public function __set($fieldname, $value)
    {
    	if (isset($this->_info["{$fieldname}_id"]) and is_a($value, 'StaysailEntity')) {
    		$this->_info["{$fieldname}_id"] = $value->id;
    	} else {
	        $this->_info[$fieldname] = $value;
    	}

        if ($this->_name_template and strstr($this->_name_template, "{{$fieldname}}")) {
            $this->_info['name'] = $this->autoName();
        }

        if (method_exists($this, "{$fieldname}_Set")) {
            $method = "{$fieldname}_Set";
            $this->$method($value);
        }
    }

    /**
     * Universal field getter for $Entity->fieldname
     *
     * @param string $fieldname
     * @return string|NULL
     */
    public function __get($fieldname)
    {
    	if (isset($this->_save[$fieldname])) {
	    	$value = $this->_save[$fieldname];
    		if (!is_array($value) and substr($value, 0, 10) == '0000-00-00') {$value = '';}
    		return $value;
    	}
    	if (isset($this->_save["{$fieldname}_id"])) {
    		$id = $this->_save["{$fieldname}_id"];
    		return new $fieldname($id);
    	}
    	return null;
    }

    /**
     * Returns the name field if this instance is used as a string.
     *
     * @return string
     */
    public function __toString() {return $this->name;}

	/**
	 * Update the entity's current data.
	 *
	 * $info is an array of fieldname=>value.
	 *
	 * Optionally save after update with
	 * $Entity->update($info)->save();
	 *
	 * @param array $info
	 */
	public function update(array $info)
	{
		foreach ($this->_fields as $fieldname => $type)
		{
			if ($type == self::AssignMany) {continue;}
			if ($type == self::AssignOne) {$fieldname = "{$fieldname}_id";}
			if (isset($info[$fieldname])) {
				$this->_info[$fieldname] = $info[$fieldname];
			}
		}
		if ($this->useAutoname()) {$this->_info['name'] = $this->autoName();}
	}

	private function loadMembers($target_table)
	{
		$children = $this->_framework->getChildIDs($this->_class, $target_table, $this->id);
		return $children;
	}

	/**
	 * Updates junction table entries for the current data, given the target
	 * table name and an array of member ids.
	 *
	 * The changes to the junction table aren't actually committed to the
	 * database until the save() method is called.
	 *
	 * @see save()
	 * @param string $target_table
	 * @param array $members
	 */
	public function setMembers($target_table, array $members)
	{
		if (in_array($target_table, $this->_junctions)) {
			$this->_info["{$target_table}_id"] = $members;
		}
	}

	protected function getMembers($target_table)
	{
		if (in_array($target_table, $this->_junctions)) {
			return $this->_save["{$target_table}_id"];
		}
		return array();
	}

	/**
	 * Get an array of category ids associated with this entity.
	 *
	 * @return array<int>
	 */
	public function getCategoryIDs()
	{
		if (!sizeof($this->_category_ids)) {
			$this->loadCategories();
		}
		return $this->_category_ids;
	}

	/**
	 * Return the primary category id
	 *
	 * @return int
	 */
	public function getPrimaryCategoryID()
	{
		if ($this->_primary_category_id === null) {
			$this->loadCategories();
		}
		return $this->_primary_category_id;
	}

	/**
	 * Save the current data to the database.
	 */
	public function save()
	{
		if ($this->id) {
			$this->_framework->update($this->_class, $this->id, $this->_info, $this->_fields);
		} else {
			$this->_info['sort'] = time();

			$this->id = $this->_framework->insert($this->_class, $this->_info, $this->_fields);
		}

		foreach ($this->_junctions as $target_table)
		{
			$old = $this->_save["{$target_table}_id"];
			$new = $this->_info["{$target_table}_id"];
			$this->_framework->updateJunction($this->_class, $this->id, $target_table, $new, $old);
			$this->_save["{$target_table}_id"] = $new;
		}

		$this->_save = $this->_info;
	}

	// Alias functions
	protected function duplicate() {return $this->copy();}
	protected function remove() {return $this->delete();}

	/**
	 * Remove the entity from the database, along with category
	 * assignments and collection members.
	 *
	 * This is a protected method, and should be overridden in
	 * the child class if deletion should be permitted publically, or
	 * if additional functionality should be implemented in the delete
	 * operation.
	 */
	protected function delete()
	{
		foreach ($this->_junctions as $target_table)
		{
			$old = $this->_save["{$target_table}_id"];
			$this->_framework->updateJunction($this->_class, $this->id, $target_table, array(), $old);
		}

		$sql = "DELETE FROM `cat_assignment`
				WHERE item_id = {$this->id}
					AND cat_type_code = '{$this->_class}'";
		$this->_framework->query($sql, StaysailIO::DISCARD_RESULT);

		// Delete collection members
		foreach ($this->_collections as $collected_entity)
		{
			$sql = "SELECT id
					FROM `{$collected_entity}`
					WHERE {$this->_class}_id = {$this->id}";
			$this->_framework->query($sql);
			while ($row = $this->_framework->getNextRow())
			{
				$member = new $collected_entity($row['id']);
				$member->delete();
			}
		}

		$this->_framework->remove($this->_class, $this->id);
		$this->_info = $this->_save = array();
		$this->id = null;
	}

	/**
	 * Generate a copy of the entity, including links to other entities
	 * in junction tables.  Note that this method does not copy
	 * collections.
	 *
	 * This is a protected method, and should be overridden in the
	 * child class if copying should be permitted publically, or if
	 * the additional functionality should be implemented in the copy
	 * operation.
	 *
	 * Returns the reference to the copied entity.
	 *
	 * @return StaysailEntity
	 */
	protected function copy()
	{
		$copy = new $this->_class();
		$info = $this->info();
		if (!$this->useAutoname()) {
			$info['name'] = "{$info['name']} (Copy)";
		}
		$copy->update($info);

		foreach ($this->_junctions as $target_table)
		{
			$members = $this->getMembers($target_table);
			$copy->setMembers($target_table, $members);
		}

		return $copy;
	}

	/**
	 * Find the categories and primary category for this entity.  This is
	 * private, and is called by public getters.
	 *
	 * @see getCategoryIDs
	 * @see getPrimaryCategoryID
	 */
	private function loadCategories()
	{
		$category_ids = array();
		$primary_category_id = 0;
		if ($this->id) {
		    $sql = "SELECT category_id, primary_cat
					FROM `cat_assignment`
					WHERE item_id = {$this->id}
						AND cat_type_code = '{$this->_class}'";
		    $this->_framework->query($sql);
		    while ($row = $this->_framework->getNextRow())
		    {
			$category_ids[] = $row['category_id'];
			if ($row['primary_cat']) {$primary_category_id = $row['category_id'];}
		    }
 		}
		$this->_category_ids = $category_ids;
		$this->_primary_category_id = $primary_category_id;
	}

	/**
	 * Updates the category table for the entity.  Note that this
	 * makes the database updates, which is different than the update()
	 * method, which is used in conjunction with save() to actually
	 * change the database.
	 *
	 * $new is an array of category ids.
	 *
	 * @param array $new
	 * @param int $primary_category_id
	 */
	public function setCategories(array $new, $primary_category_id = 0)
	{
		$old = $this->getCategoryIDs();
		if ($primary_category_id and !in_array($primary_category_id, $new)) {
			$new[] = $primary_category_id;
		}

		// Add categories to the category table
		foreach (array_diff($new, $old) as $add_id)
		{
			$sql = "INSERT INTO `cat_assignment`
					(item_id, category_id, primary_cat, cat_type_code)
					VALUES ({$this->id}, {$add_id}, 0, '{$this->_class}')";
			$this->_framework->query($sql, StaysailIO::DISCARD_RESULT);
		}

		// Remove deleted categories from category table
		foreach (array_diff($old, $new) as $remove_id)
		{
			$sql = "DELETE FROM `cat_assignment`
					WHERE item_id = {$this->id}
						AND category_id = {$remove_id}
					    AND cat_type_code = '{$this->_class}'";
			$this->_framework->query($sql, StaysailIO::DISCARD_RESULT);
		}

		// Update primary category_id
		if ($primary_category_id != $this->getPrimaryCategoryID()) {
			$sql = "UPDATE `cat_assignment`
					SET primary_cat = 1
					WHERE item_id = {$this->id}
						AND category_id = {$primary_category_id}
						AND cat_type_code = '{$this->_class}'";
			$this->_framework->query($sql,StaysailIO::DISCARD_RESULT);
			$this->_primary_category_id = $primary_category_id;
		}

		$this->_category_ids = $new;
	}

    /**
     * Uses the name template to automatically set the name field for
     * this entity.  The child class may specify, through setting the
     * $_name_template field, whether to allow manual setting of the name
     * (with $Entity->name = 'My Name') or automatic creation of the name.
     *
     * For automatic naming, field names are enclosed in curly braces.  When
     * an update() call is performed, the field values are interpolated using
     * the template, and set as the name field.
     *
     * @see update()
     * @return string
     */
    private function autoName()
    {
        $name = $this->_name_template;
        foreach ($this->_fields as $fieldname => $type)
        {
        	if ($type == self::AssignOne or $type == self::AssignMany) {continue;}
			if (isset($this->_info[$fieldname])) {
				$name = str_replace("{{$fieldname}}", $this->_info[$fieldname], $name);
			}
        }
        return $name;
    }

    /**
     * Returns true if the entity is named automatically with the template.
     *
     * @see autoName()
     * @return boolean
     */
    public function useAutoname() {return $this->_name_template ? true : false;}

    public function getName() {return $this->_class;}
    public function getPluralName() {return $this->_plural;}


    /**
     * Return the fieldname(s) by which this entity is sorted.
     *
     * @return string
     */
    public function getSort() {return $this->_sort;}

    /**
     * Return an array of id=>name pairs of the current entity.  Suitable
     * for creating option lists for dropdowns, button sets, etc.
     *
     * @return array<int=>string>
     */
    public function getNames($filters = null)
    {
    	if (!$filters) {$filters = array();}
    	$filters [] = new Filter(Filter::Sort, $this->_sort);
    	$options = $this->_framework->getOptions($this->_class, $filters);
		return $options;
	}

	/**
	 * Return a list of fieldnames of the type self::Collection.
	 *
	 * @return array<string>
	 */
	public function getCollections() {return $this->_collections;}

	/**
	 * Return all, or a subset, of members of a collection owned by
	 * this entity.  The collected entity specified must be a field
	 * of type self::Collection.  If no filters are provided, all
	 * members of the collection will be returned.
	 *
	 * Returns an array of StaysailEntity objects.
	 *
	 * @param string $collected_entity
	 * @param array $filters
	 * @return array
	 */
	public function getCollection($collected_entity, $filters = null)
	{
		$collection = array();
		if (in_array($collected_entity, $this->_collections)) {
			if (!$filters) {$filters = array();}
			$filters[] = new Filter(Filter::Match, array("{$this->_class}_id" => $this->id));
			$collection = $this->_framework->getSubset($collected_entity, $filters);
		}
		return $collection;
	}

	/**
	 * For a field of type self::File, set the path to the file system
	 * directory to which files are located for a specific field.
	 * This will be used for saving file uploads, as well as for
	 * determining the URL and file path.
	 *
	 * @param string $fieldname
	 * @param string $file_path
	 */
	public function setLibraryPath($fieldname, $file_path)
	{
		$this->_library_paths[$fieldname] = $file_path;
	}

	/**
	 * Returns the file system directory for the specified field.
	 *
	 * @param string $fieldname
	 * @return string|NULL
	 */
	public function getLibraryPath($fieldname)
	{
		if (isset($this->_library_paths[$fieldname])) {
			return $this->_library_paths[$fieldname];
		}
		return null;
	}

	/**
	 * Sets a file name prefix for the specified field.  This is used
	 * when saving a file to the library, and is placed before the
	 * native file name.
	 *
	 * @param string $fieldname
	 * @param string $file_prefix
	 */
	public function setLibraryPrefix($fieldname, $file_prefix)
	{
		StaysailIO::cleanse($file_prefix, StaysailIO::Filename);
		$this->_library_prefixes[$fieldname] = $file_prefix;
	}

	/**
	 * Generates an update() call based on POST data, using the
	 * specified array of field names.
	 *
	 * Immediately save with
	 * $Entity->updateFrom($fields)->save();
	 *
	 * @see update()
	 * @param array $fields
	 * @return $this
	 */
	public function updateFrom(array $fields)
	{
		$update = array();
		foreach ($fields as $fieldname)
		{
			if (!isset($this->_fields[$fieldname])) {continue;}
			if ($this->_fields[$fieldname] == self::File) {
				$update[$fieldname] = $this->uploadFile($fieldname);
			} else {
				$update[$fieldname] = StaysailIO::post($fieldname);
			}
		}
		$this->update($update);
		return $this;
	}

	/**
	 * Handles a file upload from the $_FILES global by moving the
	 * file to the directory specified by the field's library path.
	 *
	 * If any prefix was specified earlier with setLibraryPrefix(),
	 * it is added to the beginning of the filename.
	 *
	 * If any override filename is specified, it is used in place of the
	 * native uploaded filename.  Even when a filename is overridden, the
	 * prefix is added.
	 *
	 * @see setLibraryPath()
	 * @see setLibraryPrefix()
	 * @param string $fieldname
	 * @param string $override_filename
	 * @param string|null $watermarkLink
	 * @return string
	 */
	public function uploadFile($fieldname, $override_filename = null, $file = null, $watermarkLink = null)
	{
//        var_dump('$fieldname');
//        var_dump($fieldname);
//        var_dump('$override_filename');
//        var_dump($override_filename);
//        var_dump('$file');
//        var_dump($file);
//        var_dump('$watermarkLink');
//        var_dump($watermarkLink);
		$filename = null;
		$library = $this->getLibraryPath($fieldname);
		if (!$library) {
			die("{$this->_class} library not specified for {$fieldname}.");
		}

		if ($file || isset($_FILES[$fieldname])) {
			$file = $file ? $file : $_FILES[$fieldname];

			$tmp_name = $file['tmp_name'];

			if (is_uploaded_file($tmp_name)) {
				$filename = $override_filename ? $override_filename : $file['name'];
                $path = pathinfo($file['name']);
				// If the uploaded file has an extension and the override does not, append the extension
				// to the override
				if ($override_filename) {
					if (isset($path['extension']) and !strstr($override_filename, '.')) {
						$filename .= ".{$path['extension']}";
					}
				}

				$prefix = isset($this->_library_prefixes[$fieldname]) ? $this->_library_prefixes[$fieldname] : '';
				if (!file_exists($library)) {
					mkdir($library);
					chmod($library, 0755);
				}
				$filename = $prefix . $filename;
				StaysailIO::cleanse($filename, StaysailIO::Filename);
				$full_path = "{$library}/{$filename}";

//                var_dump('$watermarkLink');
//                var_dump($watermarkLink);
//                var_dump('$full_path');
//                var_dump($full_path);
				move_uploaded_file($tmp_name, $full_path);

				if ($watermarkLink) {
                    $this->addWatermark($full_path, $path['extension'], $watermarkLink);
                }

				chmod($full_path, 0755);
			}
		}
		return $filename;
	}

	protected function addWatermark($targetFilePath, $fileType, $watermarkLink)
    {
        $watermarkImagePath = DATAROOT . '/public/site_img/logowm.png';
        // Set Path to Font File
        $font_path =  (DATAROOT. '/public/fonts/arial.ttf');

        $stamp = imagecreatefrompng($watermarkImagePath);
        // TODO error
        //Warning: imagecreatefromjpeg(): gd-jpeg: JPEG library reports unrecoverable error: Not a JPEG file: starts with 0x89 0x50
//        var_dump($targetFilePath);
        switch($fileType){
            case 'jpg':
                $im = imagecreatefromjpeg($targetFilePath);
                break;
            case 'jpeg':
                $im = imagecreatefromjpeg($targetFilePath);
                break;
            case 'png':
                $im = imagecreatefrompng($targetFilePath);
                break;
            default:
                $im = imagecreatefromjpeg($targetFilePath);
        }


        $white =  imagecolorallocatealpha($im, 255, 255, 255, 50);;

        $marge_right = 10;
        $marge_bottom = 10;
        $marge_left = 10;
        $pad_right = 10;
        $sx = 50; // logo width
        $sy = 55; // logo height

        $logo_x = $marge_left;
        $logo_y = imagesy($im) - $sy - $marge_bottom;
        $text_x = $logo_x + $pad_right + $sx;
        $text_size = 10;
        $text_y = round($logo_y  + $text_size + ($sy - $text_size)/2);

        imagecopyresized($im, $stamp, $logo_x, $logo_y, 0, 0, $sx, $sy, imagesx($stamp), imagesy($stamp));
        imagettftext($im, $text_size, 0, $text_x, $text_y, $white, $font_path, strtoupper($watermarkLink));


        // Save image and free memory
        imagepng($im, $targetFilePath);
        imagedestroy($im);

    }

	/**
	 * Returns the MIME type of the uploaded file of the specified
	 * name, or NULL if no file exists.
	 *
	 * @param string $fieldname
	 * @return string|NULL
	 */
	public function getFileType($fieldname)
	{
		if (isset($_FILES[$fieldname])) {
			$file = $_FILES[$fieldname];
			return $file['type'];
		}
		return null;
	}

	/**
	 * Returns the file system path of the specified field.
	 *
	 * @param string $fieldname
	 * @return string|NULL
	 */
	public function getFilePath($fieldname)
	{
		$library = $this->getLibraryPath($fieldname);
		if (!$library) {return null;}
    	if (isset($this->_save[$fieldname])) {
	    	$file = $this->_save[$fieldname];
	    	if (!$file) {return null;}
	    	return "{$library}/{$file}";
    	}
    	return null;
	}
}