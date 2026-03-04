<?php

class StaysailIO_PDO
{
    const DISCARD_RESULT = true;
    const MAX_SUBSET = 5000;
    const SQL = 1;      // SQL statements
    const HTML = 2;     // HTML output
    const Int = 3;      // Integer
    const URL = 4;      // URL
    const JS = 5;       // JavaScript
    const Filename = 6; // Filenames

    private static ?self $instance = null;
    private PDO $pdo;
    private array $stack = [];
    private $res;

    public function __construct($host, $user, $pass, $name)
    {
        $dsn = "mysql:host=" . $host . ";dbname=" . $name . ";charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("No database connection" . $e->getMessage());
        }
    }

    public static function engage($host = '', $user = '', $pass = '', $name = '')
    {
        if (!isset(self::$instance)) {
            self::$instance = new StaysailIO_PDO($host, $user, $pass, $name);
        }

        return self::$instance;
    }

    public function query(string $sql, bool $discardResult = false)
    {
        if (STAYSAIL_DIAGNOSTIC) {
            error_log("[SQL] " . $sql);
        }

        try {
            $res = $this->pdo->prepare($sql);
            $res->execute();

            if (!$discardResult) {
                $this->res = $res;
            }

            return $res;
        } catch (PDOException $e) {
            throw new RuntimeException("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
        }
    }

    public function getArray(string $sql)
    {
        $result = $this->pdo->query($sql);
        $resultArray = [];
        if ($result->rowCount() > 0) {
            $resultArray = $result->fetchAll(PDO::FETCH_ASSOC);
        }

        return $resultArray;
    }

    public function getSingleRow(string $sql)
    {
        $this->query($sql);
        $row = $this->res->fetch(PDO::FETCH_ASSOC);
        $this->popResult();

        return $row;
    }

    public function getNextRow()
    {
        if ($this->res instanceof PDOStatement) {
            if ($row = $this->res->fetch(PDO::FETCH_ASSOC)) {
                return $row;
            }
        }
        $this->popResult();
        return null;
    }

    public function getCount()
    {
        if ($this->res instanceof PDOStatement) {
            return $this->res->rowCount();
        }
        return 0;
    }

    public function getRowByID(string $table, int $id)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $sql = "SELECT * FROM `{$table}` WHERE id = {$id} LIMIT 1";

        return $this->getSingleRow($sql) ?: [];
    }

    public function getRowByField(string $table, string $fieldName, $fieldValue)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $sql = "SELECT * FROM `{$table}` WHERE {$fieldName} = {$fieldValue}";

        return $this->getSingleRow($sql);
    }

    public function getRowByConditions(string $table, array $conditions, $sortColumn = false, $sort = false)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $sql = "SELECT * FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND";
        }

        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);

        if ($sortColumn && $sort) {
            $sql .= " ORDER BY {$sortColumn} {$sort}";
        }

        return $this->getSingleRow($sql);
    }

    public function getRowByConditionsString(string $table, array $conditions, $sortColumn = false, $sort = false)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $sql = "SELECT * FROM `{$table}` WHERE {$conditions}";

        if ($sortColumn && $sort) {
            $sql .= " ORDER BY {$sortColumn} {$sort}";
        }

        return $this->getSingleRow($sql);
    }

    public function getAllIdsRowsByField(string $table, $fieldName, $fieldValue, int $limit = 100)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }

        $sql = "SELECT id FROM `{$table}` WHERE {$fieldName} = '{$fieldValue}'  LIMIT $limit";
        $this->query($sql);

        return $this->res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllIdsRowsByConditions(string $table, array $conditions, int $limit = 100)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $sql = "SELECT * FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND ";
        }

        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);
        $this->query($sql);

        return $this->res->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getColumnByConditions(string $table, string $column, array $conditions, int $limit = 100)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $sql = "SELECT {$column} FROM `{$table}` WHERE";

        foreach ($conditions as $conditionKey => $conditionValue) {
            $sql .= " {$conditionKey} = {$conditionValue} AND ";
        }
        $sql = preg_replace('/\W\w+\s*(\W*)$/', '$1', $sql);
        $this->query($sql);

        return array_column($this->res->fetchAll(PDO::FETCH_NUM), 0);
    }

    public function insert(string $table, array $row, $fields = '')
    {
//        dd('enter');
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
                if ($type == StaysailEntity::AssignOne && !$val) {
                    $values .= "NULL,";
                } else {
                    $values .= $this->pdo->quote($val) . ",";
                }
            }
        }

        $keys = trim($keys, ',');
        $values = trim($values, ',');

        $sql = "INSERT INTO `{$table}` ({$keys}) VALUES ({$values})";
        $this->pdo->exec($sql);
//        dd($sql);
//dd($this->pdo->lastInsertId());
        return $this->pdo->lastInsertId();
    }

    public function update(string $table, int $id, array $row, $fields = '')
    {
        $set = '';
        $values = [];

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
                    $set .= "`{$fieldname}` = ?,";
                    $values[] = $val;
                }
            }
        }

        $set = trim($set, ',');

//        if (empty($set)) {
//            return false;
//        }

        $sql = "UPDATE `{$table}` SET {$set} WHERE id = ?";
        $values[] = $id;

        $result = $this->pdo->prepare($sql);

        return $result->execute($values);
    }

    public function updateJunction(string $parent_table, int $id, string $target_table, array $new, array $old)
    {
        $junctionTable = "{$parent_table}_{$target_table}";
        $parentColumn = "{$parent_table}_id";
        $targetColumn = "{$target_table}_id";

        // Add new things to junction table
        $sql = "INSERT INTO `$junctionTable` (`$parentColumn`, `$targetColumn`) VALUES (?, ?)";
        $insertResult = $this->pdo->prepare($sql);

        foreach (array_diff($new, $old) as $add_id) {
            $insertResult->execute([$id, $add_id]);
        }

        // Remove deleted things from junction table
        $sql = "DELETE FROM `$junctionTable` 
                 WHERE `$parentColumn` = ? AND `$targetColumn` = ?";
        $deleteResult = $this->pdo->prepare($sql);

        foreach (array_diff($old, $new) as $remove_id) {
            $deleteResult->execute([$id, $remove_id]);
        }
    }

    public function remove(string $table, int $id)
    {
        $sql = "DELETE FROM `{$table}` WHERE id = ?";
        $result = $this->pdo->prepare($sql);

        return $result->execute([$id]);
    }

    public function getAll(string $table)
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $sql = "SELECT * FROM `{$table}`";

        return $this->getSingleRow($sql);
    }

    public function getChildIDs(string $parent_table, string $target_table, int $parent_id): array
    {
        $child_ids = [];

        $sql = "SELECT {$target_table}_id AS `id`
				FROM `{$parent_table}_{$target_table}`
				WHERE {$parent_table}_id = {$parent_id}";
        $this->query($sql);
        while ($row = $this->getNextRow()) {
            $child_ids[$row['id']] = $row['id'];
        }

        return $child_ids;
    }

//    public static function cleanse(&$value, $technique = self::SQL)
//    {
//        if ($value === null) {
//            return;
//        }
//
//        switch ($technique) {
//            case self::SQL:
//                $value = str_replace(["\\", "\0", "\n", "\r", "'", '"', "\x1a"], ["\\\\", "\\0", "\\n", "\\r", "\\'", '\\"', "\\Z"], $value);
//                break;
//
//            case self::JS:
//                $value = addslashes($value);
//                break;
//
//            case self::HTML:
//                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
//                break;
//
//            case self::Int:
//                $value = preg_replace('/[^0-9]/', '', (string)$value);
//                $value = $value === '' ? null : (int)$value;
//                break;
//
//            case self::URL:
//                $value = urlencode((string)$value);
//                break;
//
//            case self::Filename:
//                $value = str_replace(' ', '_', (string)$value);
//                $value = preg_replace('/[^\.0-9a-zA-Z_]/', '', $value);
//                break;
//
//            default:
//                throw new InvalidArgumentException("Unknown cleansing technique: {$technique}");
//        }
//    }
    public static function cleanse(&$value, $technique = StaysailIO_PDO::SQL)
    {
        if ($value !== null) {
            switch ($technique) {
                case StaysailIO_PDO::SQL:
                case StaysailIO_PDO::JS:
                    $value = addslashes($value);
                    break;

                case StaysailIO_PDO::HTML:
                    $value = htmlspecialchars($value);
                    break;

                case StaysailIO_PDO::Int:
                    $value = preg_replace('/[^0-9]/', '', $value);
                    break;

                case StaysailIO_PDO::URL:
                    $value = urlencode($value);
                    break;

                case StaysailIO_PDO::Filename:
                    $value = str_replace(' ', '_', $value);
                    $value = preg_replace('/[^\.0-9a-zA-Z_]/', '', $value);
                    break;
            }
        }
    }

    public static function untaint(&$value, int $technique = self::SQL): void
    {
        self::cleanse($value, $technique);
    }

    public function getSubset($entity, $filters = null, $joinSql = null, $groupSql = null, $withoutSorting = true)
    {
//        dd(2);
        $options = $this->getOptions($entity, $filters, $joinSql, $groupSql);
        $subset = [];
        foreach (array_keys($options) as $id) {
            $subset[] = new $entity($id);
        }
        return $subset;
    }

    public function getSubsetCount($entity, $filters = null)
    {
        $find_sql = $this->getFindSQL($entity, $filters);
        $sql = "SELECT COUNT({$entity}.id) AS `count`
				FROM `{$entity}`
				{$find_sql}";
        $row = $this->getSingleRow($sql);

        return $row['count'];
    }

    public function getSingle($entity, $filters = null)
    {
        if (!is_array($filters)) {
            $filters = [$filters];
        }
        $filters[] = new Filter(Filter::Limit, 1);
        $subset = $this->getSubset($entity, $filters);

        if (count($subset) > 0) {
            return $subset[0];
        }

        return null;
    }

    public function getCollection($entity, $filters = null)
    {
        return $this->getSubset($entity, $filters);
    }

    public static function publicModule($module)
    {
        $module = str_replace('/', '', $module);
        $module = str_replace('\\', '', $module);
        if (is_file(DOCROOT . "/private/modules/{$module}/public.{$module}.php")) {
            require_once(DOCROOT . "/private/modules/{$module}/public.{$module}.php");
            return new $module();
        }
    }

    public function getJobs($entity)
    {
        $jobs = [];
        $methods = get_class_methods($entity);

        foreach ($methods as $method) {
            if (preg_match('/(.*)_Job/', $method, $m)) {
                $jobs[] = $m[1];
            }
        }

        return $jobs;
    }

    public static function get($key)
    {
        $value = isset($_GET[$key]) ? $_GET[$key] : null;
        return $value;
    }

    public static function post($key)
    {
        $value = isset($_POST[$key]) ? $_POST[$key] : null;
        return $value;
    }

    public static function getInt($key)
    {
        $value = StaysailIO_PDO::get($key);
        if ($value) {
            StaysailIO_PDO::cleanse($value, StaysailIO_PDO::Int);
        }
        return $value;
    }

    public static function session($key)
    {
        $value = isset($_SESSION[$key]) ? $_SESSION[$key] : null;
        return $value;
    }

    public static function setSession($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    public static function getFileInfo($fieldname, $key, $file = null)
    {
        if ($file && isset($file[$key])) {
            return $file[$key];
        }

        if (isset($_FILES[$fieldname]) && isset($_FILES[$fieldname][$key])) {
            return $_FILES[$fieldname][$key];
        }

        return null;
    }

    public function getOptions(string $entity, $filters = null, $joinSQL = '', $groupSql = ''): array
    {
        $options = [];
        $find_sql = $this->getFindSQL($entity, $filters);
        $sql = "SELECT {$entity}.id, {$entity}.name FROM `{$entity}`";
        $sql .= " $joinSQL";
        $sql .= " {$find_sql}";
        $sql .= " $groupSql";

        $this->pdo->query($sql);
        $found = 0;

        while ($row = $this->getNextRow()) {
            if ($found++ > self::MAX_SUBSET) {
                continue;
            }
            $options[$row['id']] = $row['name'];
        }

        return $options;
    }

    public function getSetting($key, $context = '')
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            return '';
        }
        if ($context) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $context)) {
                return '';
            }
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
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
            return '';
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            return '';
        }
        if ($context) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $context)) {
                return '';
            }
            $and_context = "AND `context` = '{$context}'";
            $context_value = "'{$context}'";
        } else {
            $and_context = '';
            $context_value = 'NULL';
        }

        $sql = "DELETE FROM `setting`
				WHERE `key` = '{$key}'
				    {$and_context}";
        $this->query($sql, self::DISCARD_RESULT);

        $sql = "INSERT INTO `setting`
				(`key`, `value`, `context`)
				VALUES ('{$key}', '{$value}', {$context_value})";
        $this->query($sql, self::DISCARD_RESULT);

        return $this;
    }

    public function getEarnings()
    {
        $sql = "SELECT Fan_Library.Library_id, Entertainer.name,
                    SUM((Library.price)) AS total
			    FROM `Fan_Library`
			    LEFT JOIN Library ON Library.id=Fan_Library.Library_id
			    LEFT JOIN Entertainer ON Library.Member_id=Entertainer.Member_id
			    WHERE Entertainer.is_deleted = 0 
			      AND Library.is_deleted = 0
			    GROUP BY     Fan_Library.Library_id, 
			                 Entertainer.name, 
			                 Entertainer.Member_id 
			                 LIMIT 100";

        $result = $this->pdo->prepare($sql);
        $result->execute();

        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFindSQL(string $entity, $filters): string
    {
        $where_list = [];
        $filter_sql = $sort_sql = $limit_sql = $join_sql = '';

        if (!is_array($filters) && $filters) {
            $filters = [$filters];
        }
        if (!is_array($filters)) {
            $filters = [];
        }

        foreach ($filters as $filter) {
            list($where, $filter, $sort, $limit, $join) = $filter->compileSQL($entity);

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
            $sort_sql = "ORDER BY " . $thing->getSort();
        }

        $where_sql = '';
        if (count($where_list) > 0) {
            $where_sql = "WHERE " . implode("\n    AND ", $where_list);
        }

        return "\n{$filter_sql}\n{$join_sql}\n{$where_sql}\n{$sort_sql}\n{$limit_sql}\n";
    }

    public function getSubsetPerPeriod(string $entity, string $period = '')
    {
        $params = [];
        $sql = "SELECT $entity.id FROM `$entity` 
            LEFT JOIN Member ON $entity.Member_id = Member.id
            WHERE $entity.is_deleted = 0";

        if ($period) {
            $sql .= " AND Member.created_at >= :period";
//            $sql .= "AND Member.created_at >= '$period'";
            $params[':period'] = $period;
        }

        $result = $this->pdo->prepare($sql);
        $result->execute($params);
        $newIds = $result->fetchAll(PDO::FETCH_COLUMN);
//        $newIds = $result->fetchAll(PDO::FETCH_COLUMN, 0);

        if ($newIds) {
            $placeholders = implode(',', array_fill(0, count($newIds), '?'));
            $result = $this->pdo->prepare(
                "SELECT {$entity}.id FROM `{$entity}`
             LEFT JOIN Member ON {$entity}.Member_id = Member.id
             WHERE {$entity}.is_deleted = 0
               AND DATE(Member.created_at) = DATE(Member.last_login)
               AND {$entity}.id NOT IN ($placeholders)"
            );
            $result->execute($newIds);
            $noLoginIds = $result->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $result = $this->pdo->query(
                "SELECT {$entity}.id FROM `{$entity}`
             LEFT JOIN Member ON {$entity}.Member_id = Member.id
             WHERE {$entity}.is_deleted = 0
               AND DATE(Member.created_at) = DATE(Member.last_login)"
            );
            $noLoginIds = $result->fetchAll(PDO::FETCH_COLUMN);
        }

        $allIds = array_merge($newIds, $noLoginIds);

        $subset = [];
        foreach ($allIds as $id) {
            $subset[] = new $entity((int)$id);
        }

        return $subset;
    }

    private function getFieldsFromRow(array $row): array
    {
        $fields = [];
        foreach (array_keys($row) as $fieldname) {
            $fields[$fieldname] = true;
        }

        return $fields;
    }

    private function pushResult()
    {
        if ($this->res instanceof PDOStatement) {
            array_push($this->stack, $this->res);
        }
    }

    private function popResult()
    {
        if (is_array($this->stack) && count($this->stack) > 0) {
            $this->res = array_pop($this->stack);
        } else {
            $this->res = null;
        }
    }
}
