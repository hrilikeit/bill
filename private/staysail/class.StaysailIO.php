<?php

/**
 * @package Staysail
 */

/**
 * The IO class for the Staysail application framework.
 *
 * Handles web-based IO functions like GET and POST processing, session
 * handling, input and output cleansing, as well as database connection,
 * queries, and abstraction of various types of queries.
 *
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
require '../private/domain/class.Member.php';
require '../private/domain/class.Fan.php';
require '../private/domain/class.Fan_Subscription.php';
require '../private/domain/class.Private_Message.php';
require '../private/domain/class.Post.php';
require '../private/domain/class.Library.php';
require '../private/domain/class.Fan_Library.php';
require '../private/domain/class.Payment_Method.php';
require '../private/domain/class.Post_Bump.php';

class StaysailIO
{
    const DISCARD_RESULT = true;
    const MAX_SUBSET = 5000;

    // Techniques for IO cleansing
    const SQL = 1;      // SQL statements
    const HTML = 2;     // HTML output
    const Int = 3;      // Integer
    const URL = 4;      // URL
    const JS = 5;       // JavaScript
    const Filename = 6; // Filenames

    private static $instance;
    private $db, $stack, $res;

    /**
     * Construct a StaysailIO.
     *
     * This constructor isn't invoked directly; it's private.  Rather,
     * it's invoked by the engage() method, which assigns the instance
     * to a static variable, as a singleton object.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $name
     * @see engage()
     */
    private function __construct($host, $user, $pass, $name)
    {
        $this->db = mysqli_connect($host, $user, $pass);

        if (!$this->db) {
            die("No database connection is available at this time");
        }

        $this->db->select_db($name);
        mysqli_set_charset($this->db, "utf8mb4");
        $this->stack = array();
    }

    /**
     * Called without parameters, returns the StaysailIO singleton instance
     * if it has been created.
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $name
     * @return StaysailIO
     */

    public static function engage($host = '', $user = '', $pass = '', $name = '')
    {
        if (!isset(self::$instance)) {
            self::$instance = new StaysailIO($host, $user, $pass, $name);
        }

        return self::$instance;
    }

    /**
     * Execute a MySQL query and put the result on the result stack.
     *
     * If $discard_result is set, no result is put on the stack.
     *
     * @param string $sql
     * @param boolean $discard_result
     */

    public function query($sql, $discard_result = false)
    {
        if (STAYSAIL_DIAGNOSTIC) {
            print "<!--\n\n{$sql}\n\n-->\n\n\n";
        }
        $res = mysqli_query($this->db, $sql) or die("Query failed: {$sql}" . mysqli_error($this->db));
        if (!$discard_result) {
            $this->res = $res;
        }
    }

    public function get_array($sql)
    {
        $result = mysqli_query($this->db, $sql);
        $resultArray = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $resultArray[] = $row;
            }

        }
        return $resultArray;
    }

    /**
     * Push current result on the result stack
     */
    private function pushResult()
    {
        if (is_object($this->res)) {
            array_push($this->stack, $this->res);
        }
    }

    /**
     * Pop a result off the result stack
     */
    private function popResult()
    {
        if (sizeof($this->stack)) {
            $this->res = array_pop($this->stack);
        }
    }

    /**
     * Execute the specified SQL and return the first row found
     * as an associative array of fieldname=>value.
     *
     * @param string $sql
     * @return array<string=>mixed>
     */

    public function getSingleRow($sql)
    {
        $this->query($sql);
        $row = mysqli_fetch_assoc($this->res);
        $this->popResult();
        return $row;
    }

    /**
     * Return the next row of the current result stack as an associative
     * array of fieldname=>value.
     *
     * @return array<string=>mixed>
     */

    public function getNextRow()
    {
        if ($row = mysqli_fetch_assoc($this->res)) {
            return $row;
        }
        $this->popResult();
    }

    /**
     * Return the count of the current result set.
     *
     * @return int
     */

    public function getCount()
    {
        return mysqli_num_rows($this->res);
    }

    /**
     * Return an associative row of fieldname=>value for the specified
     * id in the specified table.
     *
     * @param string $table
     * @param int $id
     * @return array<string=>mixed>
     */

    public function getRowByID($table, $id)
    {
        if (!is_numeric($id)) {
            return array();
        }
        $this->cleanse($table, StaysailIO::SQL);
        $this->cleanse($id, StaysailIO::Int);
        $sql = "SELECT * FROM `{$table}` WHERE id = {$id}";
        return $this->getSingleRow($sql);
    }

    /**
     * Return single record filtered by passed field name
     *
     * @param string $table
     * @param  $fieldName
     * @param  $fieldValue
     * @return array<string=>mixed>
     */

    public function getRowByField($table, $fieldName, $fieldValue)
    {
        $this->cleanse($table, StaysailIO::SQL);

        $sql = "SELECT * FROM `{$table}` WHERE {$fieldName} = {$fieldValue}";
        return $this->getSingleRow($sql);
    }

    /**
     * Return single record filtered by passed field name
     *
     * @param string $table
     * @param array $conditions
     * @return array<string=>mixed>
     */

    public function getRowByConditions($table, array $conditions, $sortColumn = false, $sort = false)
    {
        $this->cleanse($table, StaysailIO::SQL);

        $sql = "SELECT * FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND ";
        }
        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);
        if ($sortColumn && $sort) {
            $sql .= " ORDER BY $sortColumn $sort";
        }

        return $this->getSingleRow($sql);
    }

    /**
     * Return single record filtered by passed field name
     *
     * @param string $table
     * @param string $conditions
     * @return array<string=>mixed>
     */

    public function getRowByConditionsString($table, $conditions, $sortColumn = false, $sort = false)
    {
        $this->cleanse($table, StaysailIO::SQL);
        $sql = "SELECT * FROM `{$table}` WHERE {$conditions}";
        if ($sortColumn && $sort) {
            $sql .= " ORDER BY $sortColumn $sort";
        }
        return $this->getSingleRow($sql);
    }

    /**
     * Return single record filtered by passed field name
     *
     * @param string $table
     * @param  $fieldName
     * @param  $fieldValue
     * @return array<string=>mixed>
     */

    public function getAllIdsRowsByField($table, $fieldName, $fieldValue, $limit = 100)
    {
        $this->cleanse($table, StaysailIO::SQL);

        $sql = "SELECT id FROM `{$table}` WHERE {$fieldName} = '{$fieldValue}'  LIMIT $limit";
        $this->query($sql);
        return mysqli_fetch_all($this->res);
    }

    /**
     *
     * @param string $table
     * @param  $fieldName
     * @param  $fieldValue
     * @return array<string=>mixed>
     */
    public function getAllIdsRowsByConditions($table, $conditions, $limit = 100)
    {
        $this->cleanse($table, StaysailIO::SQL);
        $sql = "SELECT * FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND ";
        }
        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);

        $this->query($sql);
        return mysqli_fetch_all($this->res);
    }


    /**
     *
     * @param string $table
     * @param  $fieldName
     * @param  $fieldValue
     * @return array<string=>mixed>
     */
    public function getColumnByConditions($table, $column, $conditions, $limit = 100)
    {
        $this->cleanse($table, StaysailIO::SQL);
        $sql = "SELECT {$column} FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND ";
        }
        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);

        $this->query($sql);
        return array_column(mysqli_fetch_all($this->res), 0);
    }

    /**
     * Insert a record into the specified table.
     *
     * a $row array of fieldname=>value specifies the row data,
     * with a $fields array of fieldname=>type hinting at field types.
     *
     * This method is public, but it is preferable within Staysail to
     * perform database inserts with the StaysailEntity constructor.
     *
     * @param string $table
     * @param array $row
     * @param array $fields
     * @return int
     * @see StaysailEntity::__construct()
     */
    public function insert($table, array $row, $fields = '')
    {
        $keys = '';
        $values = '';
        if (!$fields) {
            $fields = $this->getFieldsFromRow($row);
        }
        foreach ($fields as $fieldname => $type) {
            if (isset($row[$fieldname]) && $row[$fieldname] === '') {
                continue;
            }
            if ($type == StaysailEntity::AssignMany) {
                continue;
            }
            if ($type == StaysailEntity::AssignOne) {
                $fieldname = "{$fieldname}_id";
            }
            if (isset($row[$fieldname])) {
                $val = $row[$fieldname];
                $keys .= "`{$fieldname}`,";
                if ($type == StaysailEntity::AssignOne and !$val) {
                    $values .= "NULL,";
                } else {
                    StaysailIO::cleanse($val, StaysailIO::SQL);
                    $values .= "'{$val}',";
                }
            }
        }
        $keys = trim($keys, ',');
        $values = trim($values, ',');
        $sql = "INSERT INTO `{$table}`
				({$keys})
				VALUES ({$values})";
//        var_dump($sql);
        mysqli_query($this->db, $sql);

        return mysqli_insert_id($this->db);
    }

    /**
     * Update a record in the specified table.
     *
     * A $row array of fieldname=>value specifies table data,
     * with a $fields array of fieldname=>type hinting at field types.
     *
     * This method is public, but it is preferable within Staysail to
     * perform database updates with the StaysailEntity interfaces
     * (StaysailEntity::update(), etc.).
     *
     * @param string $table
     * @param int $id
     * @param array $row
     * @param array $fields
     * @return mysqli_result
     * @see StaysailEntity::update()
     */
    public function update($table, $id, array $row, $fields = '')
    {
//        var_dump($row);
        $set = '';
        StaysailIO::cleanse($id, StaysailIO::Int);
        if (!$fields) {
            $fields = $this->getFieldsFromRow($row);
        }

        foreach ($fields as $fieldname => $type) {
            if (isset($row[$fieldname]) && $row[$fieldname] === '') {
                continue;
            }
            if ($type == StaysailEntity::AssignMany or $type == StaysailEntity::Timestamp) {
                continue;
            }
            if ($type == StaysailEntity::AssignOne) {
                $fieldname = "{$fieldname}_id";
            }
            if (isset($row[$fieldname]) && $row[$fieldname] !== "") {
                $val = $row[$fieldname];

                if ($type == StaysailEntity::AssignOne and !$val) {
                    $set .= "`{$fieldname}` = NULL,";
                } else {
                    if (is_bool($val)) {
                        if ($val === true) {
                            $set .= "`{$fieldname}` = 1,";
                        } else {
                            $set .= "`{$fieldname}` = 0,";
                        }
                    } else {
                        StaysailIO::cleanse($val, StaysailIO::SQL);
                        $set .= "`{$fieldname}` = '{$val}',";
                    }
                }
            }
        }
        $set = trim($set, ',');
        $sql = "UPDATE `{$table}`
				SET {$set}
				WHERE id = {$id}";
        //var_dump($sql);

        return mysqli_query($this->db, $sql);
    }

    /**
     * Returns a list of fields based on the fieldname=>value row.
     *
     * This is a private method used to dynamically get a list of
     * fields.
     *
     * @param array $row
     * @return array
     */
    private function getFieldsFromRow(array $row)
    {
        $fields = array();
        foreach (array_keys($row) as $fieldname) {
            // The value TRUE below doesn't really matter, since types
            // other than AssignMany and AssignOne are handled the same way
            $fields[$fieldname] = true;
        }
        return $fields;
    }

    /**
     * Update a junciton table.
     *
     * Performs an update given the parent and target table names, the
     * parent table id, and lists of new and old target ids.
     *
     * Although this method is public, it is preferable within Staysail to
     * update junction tables with the StaysailEntity::setMembers() method.
     *
     * @param string $parent_table
     * @param int $id
     * @param string $target_table
     * @param array $new
     * @param array $old
     * @see StaysailEntity::setMembers()
     */
    public function updateJunction($parent_table, $id, $target_table, $new, $old)
    {
        StaysailIO::cleanse($id, StaysailIO::Int);

        // Add new things to junction table
        foreach (array_diff($new, $old) as $add_id) {
            StaysailIO::cleanse($add_id, StaysailIO::Int);
            $sql = "INSERT INTO {$parent_table}_{$target_table}
					({$parent_table}_id, {$target_table}_id)
					VALUES ({$id}, {$add_id})";
            $this->db->query($sql, StaysailIO::DISCARD_RESULT);
        }

        // Remove deleted things from junction table
        foreach (array_diff($old, $new) as $remove_id) {
            StaysailIO::cleanse($remove_id, StaysailIO::Int);
            $sql = "DELETE FROM {$parent_table}_{$target_table}
					WHERE {$parent_table}_id = {$id}
					    AND {$target_table}_id = {$remove_id}";
            $this->db->query($sql, StaysailIO::DISCARD_RESULT);
        }
    }

    /**
     * Removes a record from the specified table.
     *
     * It is usually preferable within Staysail to use
     * StaysailEntity::delete(), since a delete operation often involves
     * removing collections, junction table entries, and category
     * assignments.  However, StaysailEntity::delete() is protected,
     * so any public delete method should be implemented in the final
     * domain class.
     *
     * @param string $table
     * @param int $id
     * @return mysqli_result
     * @see StaysailEntity::delete()
     */
    public function remove($table, $id)
    {
        StaysailIO::cleanse($id, StaysailIO::Int);
        $sql = "DELETE FROM `{$table}`
				WHERE id = {$id}";
        return mysqli_query($this->db, $sql);
    }

    /**
     * Return single record filtered by passed field name
     *
     * @param string $table
     * @return array<string=>mixed>
     */
    public function getAll($table)
    {
        $this->cleanse($table, StaysailIO::SQL);

        $sql = "SELECT * FROM `{$table}`";
        return $this->getSingleRow($sql);
    }


    /**
     * Returns child ids from a junction table.
     *
     * Looks at a junction table associated with the
     * provided parent table and target table for the specified
     * parent id.
     *
     * It is usually preferable within Staysail to use the
     * StaysailEntity::getMembers() method, which does the same
     * thing for a specific entity.
     *
     * @param string $parent_table
     * @param string $target_table
     * @param int $parent_id
     * @return array<int>
     * @see StaysailEntity::getMembers()
     */
    public function getChildIDs($parent_table, $target_table, $parent_id)
    {
        $child_ids = array();
        $sql = "SELECT {$target_table}_id AS `id`
				FROM `{$parent_table}_{$target_table}`
				WHERE {$parent_table}_id = {$parent_id}";
        $this->query($sql);
        while ($row = $this->getNextRow()) {
            $child_ids[$row['id']] = $row['id'];
        }
        return $child_ids;
    }

    /**
     * Cleanses the provided value (by reference) using the specified
     * technique.
     *
     * The available techniques are:
     *
     * <code>
     *     StaysailIO::SQL (escape MySQL characters to prevent injection)
     *     StaysailIO::JS (escape JavaScript characters to prevent XSS)
     *     StaysailIO::HTML (escape HTML characters to prevent XSS)
     *     StaysailIO::Int (remove any non-digits)
     *     StaysailIO::URL (URL-encode)
     *     StaysailIO::Filename (confine filenames to a limited char set)
     * </code>
     *
     * @param mixed $value
     * @param int $technique
     */
    public static function cleanse(&$value, $technique = StaysailIO::SQL)
    {
        if ($value !== null) {
            switch ($technique) {
                case StaysailIO::SQL:
                case StaysailIO::JS:
                    $value = addslashes($value);
                    break;

                case StaysailIO::HTML:
                    $value = htmlspecialchars($value);
                    break;

                case StaysailIO::Int:
                    $value = preg_replace('/[^0-9]/', '', $value);
                    break;

                case StaysailIO::URL:
                    $value = urlencode($value);
                    break;

                case StaysailIO::Filename:
                    $value = str_replace(' ', '_', $value);
                    $value = preg_replace('/[^\.0-9a-zA-Z_]/', '', $value);
                    break;
            }
        }

    }

    /**
     * Deprecated alias for cleanse()
     *
     * @deprecated Use cleanse() instead
     */
    public static function untaint(&$value, $technique = StaysailIO::SQL)
    {
        StaysailIO::cleanse($value, $technique);
    }

    /**
     * Return a list of jobs available to a naked objects backend interface.
     *
     * @param string $entity
     * @return array<string>
     */
    public function getJobs($entity)
    {
        $jobs = array();
        $methods = get_class_methods($entity);
        foreach ($methods as $method) {
            if (preg_match('/(.*)_Job/', $method, $m)) {
                $jobs[] = $m[1];
            }
        }
        return $jobs;
    }

    /**
     * Return unprocessed $_GET input for the specified key, or null.
     *
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        $value = isset($_GET[$key]) ? $_GET[$key] : null;
        return $value;
    }

    /**
     * Return unprocessed $_POST input for the specified key, or null.
     *
     * @param string $key
     * @return mixed
     */
    public static function post($key)
    {
        $value = isset($_POST[$key]) ? $_POST[$key] : null;
        return $value;
    }

    /**
     * Return $_GET input for the specified key, forced to an integer.
     *
     * @param string $key
     * @return int
     */
    public static function getInt($key)
    {
        $value = StaysailIO::get($key);
        if ($value) {
            StaysailIO::cleanse($value, StaysailIO::Int);
        }
        return $value;
    }

    /**
     * Return unprocessed $_SESSION input for the specified key, or null.
     *
     * @param string $key
     * @return mixed
     */
    public static function session($key)
    {
        $value = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        return $value;
    }

    /**
     * Set the session value for the specified key.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Return the current datetime in the database format.
     *
     * @return string
     */
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Gets information about an uploaded file.
     *
     * key can be 'name', 'tmp_name', 'size', 'type', 'error'.
     *
     * @param string $fieldname
     * @param string $key
     * @return string | NULL
     */
    public static function getFileInfo($fieldname, $key, $file = null)
    {
        if ($file && isset($file[$key])) {
            return $file[$key];
        } else if (isset($_FILES[$fieldname]) and isset($_FILES[$fieldname][$key])) {
            return $_FILES[$fieldname][$key];
        }
        return null;
    }

    /**
     * Return options as an array of id=>name.
     *
     * This is useful for SELECT drop-downs, or sets of buttons.
     *
     * The $filters parameter is an optional array of Filter instances,
     * or a single Filter instance.
     *
     * @param string $entity
     * @param array|Filter
     * @return array
     * @see Filter
     */
    public function getOptions($entity, $filters = null, $joinSQL = '', $groupSql = '')
    {
        $options = array();
        $find_sql = $this->getFindSQL($entity, $filters);
        $sql = "SELECT {$entity}.id, {$entity}.name
				FROM `{$entity}`";
        $sql .= " $joinSQL";
        $sql .= " {$find_sql}";
        $sql .= " $groupSql";

        $this->query($sql);
        $found = 0;

//        var_dump(mysqli_num_rows($this->res));
//        die();
        //        var_dump($this->getNextRow());
//        die();
//        var_dump($this->res);

//        var_dump(mysqli_fetch_assoc($this->res));
//        die();

        while ($row = $this->getNextRow()) {
            //var_dump($row);
            if ($found++ > StaysailIO::MAX_SUBSET) {
                continue;
            }
            $options[$row['id']] = $row['name'];
        }
//die();
//                var_dump($options);
//        die();

        return $options;
    }

    /**
     * Returns the number of items in a list of options.
     *
     * Works the same way as getOptions(), except instead of returning
     * the actual option list, it simply returns the number of items that
     * would have been returned by getOptions().
     *
     * The $filters parameter is either an array of Filter instances,
     * or a single Filter instance
     *
     * @param string $entity
     * @param array $filters
     * @return int
     * @see getOptions()
     * @see Filter
     */
    public function getSubsetCount($entity, $filters = null)
    {
        $find_sql = $this->getFindSQL($entity, $filters);
        $sql = "SELECT COUNT({$entity}.id) AS `count`
				FROM `{$entity}`
				{$find_sql}";
        $row = $this->getSingleRow($sql);
        return $row['count'];
    }

    /**
     * Deprecated alias for getSubsetCount()
     *
     * @deprecated User getSubsetCount() instead
     */
    public function getCollectionCount($entity, $filters = null)
    {
        return $this->getSubsetCount($entity, $filters);
    }

    /**
     * Generate SQL given a set of Filters.
     *
     * $filters may be either an array of Filter instances, or a single
     * Filter instance.
     *
     * Made this public so that it can be used for diagnostics.
     *
     * @param string $entity
     * @param array|Filter
     * @return string
     * @see Filter::compileSQL()
     */
    public function getFindSQL($entity, $filters)
    {
        $where_list = array();
        $where_sql = $filter_sql = $sort_sql = $limit_sql = $join_sql = '';
        if (!is_array($filters) and $filters) {
            $filters = array($filters);
        }
        if (!is_array($filters)) {
            $filters = array();
        }

        foreach ($filters as $Filter) {
            list ($where, $filter, $sort, $limit, $join) = $Filter->compileSQL($entity);
            if ($where) {
                $where_list[] = $where;
            }
            if ($filter) {
                $filter_sql = $filter;
            }
            if ($sort) {
                $sort_sql = $sort;
            }
            if ($limit) {
                $limit_sql = $limit;
            }
            if ($join) {
                $join_sql .= $join;
            }
        }

        if (!$sort_sql) {
            $thing = new $entity();
//			$thing = new Member();
            $sort_sql = "ORDER BY " . $thing->getSort();
        }

        if (sizeof($where_list)) {
            $where_sql = "WHERE " . join("\n    AND ", $where_list);
        }
        return "\n{$filter_sql}\n{$join_sql}\n{$where_sql}\n{$sort_sql}\n{$limit_sql}\n";
    }

    /**
     * Returns an array of instances of the specified entity, based
     * on specified Filters.
     *
     * $filters is either an array of Filter instances, or a single
     * Filter instance.
     *
     * @param string $entity
     * @param array|Filter $filters
     * @return array
     * @see Filter
     */
    public function getSubset($entity, $filters = null, $joinSql = null, $groupSql = null, $withoutSorting = true)
    {
//		$options = $this->getOptions($entity, $filters, $joinSql, $groupSql, $withoutSorting);
        $options = $this->getOptions($entity, $filters, $joinSql, $groupSql);
        $subset = array();
        foreach (array_keys($options) as $id) {
            $subset[] = new $entity($id);
        }
        return $subset;
    }

    /**
     * Returns on instance of the specified entity, based on
     * specified Filters, or NULL if such an entity is not found.
     *
     * This method is based on getSubset(), which does the heavy lifting,
     * or at least makes getOptions() do the heavy lifting.
     *
     * @param string $entity
     * @param array|Filter $filters
     * @return <entity>|NULL
     * @see StaysailIO::getSubset()
     * @see Filter
     */
    public function getSingle($entity, $filters = null)
    {
        if (!is_array($filters)) {
            $filters = array($filters);
        }
        $filters[] = new Filter(Filter::Limit, 1);
        $subset = $this->getSubset($entity, $filters);
        if (sizeof($subset)) {
            return $subset[0];
        }
        return null;
    }

    /**
     * Deprecated alias for getSubset()
     *
     * @deprecated Use getSubset() instead
     */
    public function getCollection($entity, $filters = null)
    {
        return $this->getSubset($entity, $filters);
    }

    /**
     * Shortcut for requiring the public module; returns an instance of the module
     *
     * @param string $module
     * @return <$module>
     */
    public static function publicModule($module)
    {
        $module = str_replace('/', '', $module);
        $module = str_replace('\\', '', $module);
        if (is_file(DOCROOT . "/private/modules/{$module}/public.{$module}.php")) {
            require_once(DOCROOT . "/private/modules/{$module}/public.{$module}.php");
            return new $module();
        }
    }

    /**
     * Returns the value of the setting given a key and the setting's user-defined context
     *
     * @param string $key
     * @param string $context
     * @return string
     */
    public function getSetting($key, $context = '')
    {
        StaysailIO::cleanse($key, StaysailIO::SQL);
        if ($context) {
            StaysailIO::cleanse($context, StaysailIO::SQL);
            $and_context = "AND `context` = '{$context}'";
        } else {
            $and_context = '';
        }

        $sql = "SELECT `value`
				FROM `setting`
				WHERE `key` = '{$key}'
					{$and_context}
				LIMIT 1";
        $row = $this->getSingleRow($sql);
        return $row['value'];
    }

    public function setSetting($key, $value, $context = '')
    {
        StaysailIO::cleanse($key, StaysailIO::SQL);
        StaysailIO::cleanse($value, StaysailIO::SQL);
        if ($context) {
            StaysailIO::cleanse($context, StaysailIO::SQL);
            $and_context = "AND `context` = '{$context}'";
            $context_value = "'{$context}'";
        } else {
            $and_context = '';
            $context_value = 'NULL';
        }

        // Remove previous setting
        $sql = "DELETE FROM `setting`
				WHERE `key` = '{$key}'
				    {$and_context}";
        $this->query($sql, StaysailIO::DISCARD_RESULT);

        // Add the new setting
        $sql = "INSERT INTO `setting`
				(`key`, `value`, `context`)
				VALUES ('{$key}', '{$value}', {$context_value})";
        $this->query($sql, StaysailIO::DISCARD_RESULT);

        return $this;
    }

    public function getEarnings()
    {
        $sql = "SELECT Fan_Library.Library_id, Entertainer.name,
            SUM((Library.price)) AS total
			    FROM `Fan_Library` as `Fan_Library`
			    LEFT JOIN Library ON Library.id=Fan_Library.Library_id
			    LEFT JOIN Entertainer ON Library.Member_id=Entertainer.Member_id
			    WHERE Entertainer.is_deleted = 0 
			      AND Library.is_deleted = 0
			    GROUP BY     Fan_Library.Library_id, 
			                 Entertainer.name, 
			                 Entertainer.Member_id 
			                 LIMIT 100";

        $result = mysqli_query($this->db, $sql);

        return mysqli_fetch_all($result);
    }

    public function getSubsetPerPeriod($entity, $period = '')
    {
        $sql = "SELECT $entity.id FROM `$entity` 
            LEFT JOIN Member ON $entity.Member_id = Member.id
            WHERE $entity.is_deleted = 0";

        if ($period) {
            $sql .= "AND Member.created_at >= '$period'";
        }

        $result = mysqli_query($this->db, $sql);
        $newIds = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $newIds[] = (int)$row['id'];
            }
        }

        $formatIdsString = implode(',', $newIds);

        $sql = "SELECT $entity.id FROM `$entity` 
            LEFT JOIN Member ON $entity.Member_id = Member.id
            WHERE $entity.is_deleted = 0
            AND DATE(Member.created_at) = DATE(Member.last_login)";

        if ($formatIdsString) {
            $sql .= "AND $entity.id NOT IN ($formatIdsString)";
        }

        $result = mysqli_query($this->db, $sql);

        $noLoginIds = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $noLoginIds[] = $row['id'];
            }
        }

        $allIds = array_merge($newIds, $noLoginIds);

        $subset = [];
        foreach ($allIds as $value) {
            $subset[] = new $entity($value);
        }

        return $subset;
    }
}