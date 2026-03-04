<?php
/**
 * @package Staysail
 */

/**
 * The Filter type keeps track of the type and parameters for a
 * Filter, and generates SQL components based on its parameters.
 *
 * @package Staysail
 * @author Jason Justian, Clockwork Logic
 */
class Filter
{
    // Available filter types, with examples

    /**
     * Find records that match all supplied fields:
     *
     * new Filter(Filter::Match, array('last_name' => $last_name, 'company_id' => $company_id))
     */
    const Match = 1;

    /**
     * Find records in one or more of the supplied categories:
     *
     * new Filter(Filter::Category, $category_id)
     * new Filter(Filter::Category, array($category_id1, $category_id2, $etc))
     */
    const Category = 2;

    /**
     * Set a limit or limit range:
     *
     * new Filter(Filter::Limit, $limit)
     * new Filter(Filter::Limit, array($from, $to))
     *
     * Note: Subsequent Limit Filters overwrite previous Limit Filters.
     */
    const Limit = 3;


    /**
     * Specify the sort order of the subset:
     *
     * new Filter(Filter::Sort, 'last_name')
     * new Filter(Filter::Sort, array('name', 'start_date DESC'))
     *
     * You may sort by names of AssignOne child entities, which are
     * indicated with a leading capital letter:
     *
     * new Filter(Filter::Sort, array('Event'))
     *
     * Note: Subsequent Sort Filters overwrite previous Sort Filters.
     */
    const Sort = 4;


    /**
     * Find records whose name field contains the supplied text:
     *
     * new Filter(Filter::NameQuery, 'Murphy')
     */
    const NameQuery = 5;

    /**
     * Find records with no category set:
     *
     * new Filter(Filter::Uncategorized)
     */
    const Uncategorized = 6;


    /**
     * Find records with at least one category set:
     *
     * new Filter(Filter::AnyCategory)
     */
    const AnyCategory = 7;

    /**
     * Perform a full-text search of a Line, Text, or Richtext field:
     *
     * new Filter(Filter::FullText, array('abstract', $query))
     */
    const FullText = 8;

    /**
     * Find by free-form SQL as a WHERE clause:
     *
     * new Filter(Filter::Where, "start_date = '2012-10-31'")
     */
    const Where = 9;

    /**
     * Find records compared to a string, using an operator:
     *
     * new Filter(Filter::StringCompare, array('city', 'LIKE', 'Carson'))
     * new Filter(Filter::StringCompare, array('start_date', '>', '2012'))
     */
    const StringCompare = 10;

    /**
     * Find records compared to a number, using an operator:
     *
     * new Filter(Filter::NumberCompare, array('on_hand', '>=', 3))
     *
     * You can also use NumberCompare to evaluate functions:
     *
     * new Filter(Filter::NumberCompare, array('end_date', '<', 'NOW()'))
     */
    const NumberCompare = 11;

    /**
     * Find records that match a regular expression:
     *
     * new Filter(Filter::RegEx, array('url', '^http(s)?:'))
     *
     * If you want to match on the name field, you may provide only one
     * parmater:
     *
     * new Filter(Filter::RegEx, '(Inc|Ltd|Corp)')
     */
    const RegEx = 12;

    /**
     * Finds records in which the value of the field is 1:
     *
     * new Filter(Filter::IsTrue, 'active')
     */
    const IsTrue = 13;

    /**
     * Finds records in which the value of the field is NULL:
     *
     * new Filter(Filter::IsNull, 'Invoice_id')
     */
    const IsNull = 14;

    /**
     * Finds records in which the value of the field is NULL:
     *
     * new Filter(Filter::IsNotNull, 'Invoice_id')
     */
    const IsNotNull = 15;
    const IN = 16;

    private $type, $param;

    /**
     * Create a new Filter.
     *
     * @param int $type
     * @param array $param
     */
    public function __construct($type, $param = null)
    {
        $this->type = $type;
        $this->param = $param;
    }

    public function getType() {return $this->type;}
    public function getParam() {return $this->param;}

    /**
     * Convert this Filter's parameters into several types of SQL components,
     * used for finding a subset of the specified StaysailEntity.
     *
     * Returns an array of $where_sql, $filter_sql, $sort_sql, $limit_sql, $join_sql
     *
     * @see StaysailIO::getFindSQL()
     * @param string $entity
     * @return array
     */
    public function compileSQL($entity)
    {
        $type = $this->getType();
        $param = $this->getParam();
        if (!is_array($param)) {$param = array($param);}

        $where_sql = $filter_sql = $sort_sql = $limit_sql = $join_sql = '';
        switch ($type)
        {
            case Filter::Match:
                foreach ($param as $k => $v)
                {
                    StaysailIO::cleanse($v, StaysailIO::SQL);
                    if ($where_sql) {$where_sql .= " AND ";}
                    $where_sql .= "({$entity}.{$k} = '{$v}')\n";
                }
                break;

            case Filter::IN:
                foreach ($param as $k => $v)
                {
                    $where_sql .= "({$entity}.{$k} IN ('"
                    . implode("','", $v)
                        ."'))\n";
                }
                break;

            case Filter::Category:
                if (sizeof($param)) {
                    $in = join(',', $param);
                    $filter_sql = "INNER JOIN `cat_assignment` 
						 		   ON cat_assignment.item_id = {$entity}.id
					 	    	       AND cat_assignment.cat_type_code = '{$entity}'
					 			       AND cat_assignment.category_id IN ({$in})";
                }
                break;

            case Filter::Limit:
                if (sizeof($param)) {
                    $limit_sql = "LIMIT " . join(',', $param);
                }
                break;

            case Filter::Sort:
                if (sizeof($param)) {
                    $sort_fields = array();
                    foreach ($param as $f)
                    {
                        if ($f == ucwords($f)) {
                            $sort_fields[] = "{$f}.name";
                            $join_sql = "LEFT JOIN `{$f}` ON {$f}.id = {$entity}.{$f}_id\n\n";
                        } else {$sort_fields[] = $f;}
                    }
                    $sort_sql = "ORDER BY " . join(',', $sort_fields);
                }
                break;

            case Filter::NameQuery:
                if (sizeof($param)) {
                    $query = $param[0];
                    StaysailIO::cleanse($query);
                    $where_sql = "{$entity}.name LIKE '%{$query}%'";
                }
                break;

            case Filter::Uncategorized:
                $filter_sql = "LEFT JOIN `cat_assignment`
							       ON cat_assignment.item_id = {$entity}.id
							           AND cat_assignment.cat_type_code = '{$entity}'
							           AND cat_assignment.category_id > 0";
                $where_sql = "cat_assignment.category_id IS NULL";
                break;

            case Filter::AnyCategory:
                $filter_sql = "INNER JOIN `cat_assignment`
							       ON cat_assignment.item_id = {$entity}.id
							           AND cat_assignment.cat_type_code = '{$entity}'";
                break;

            case Filter::FullText:
                if (sizeof($param) == 2) {
                    $v = $param[1];
                    $this->untaint($v, StaysailIO::SQL);
                    $where_sql = "MATCH ({$param[0]}) AGAINST ('{$v}')";
                }
                break;

            case Filter::Where:
                $where_sql = $param[0];
                break;

            case Filter::StringCompare:
            case Filter::NumberCompare:
                if (sizeof($param) == 3) {
                    list ($field, $op, $val) = $param;
                    StaysailIO::cleanse($val, StaysailIO::SQL);
                    if ($type == Filter::StringCompare) {$val = "'{$val}'";}
                    $where_sql = "{$field} {$op} {$val}";
                }
                break;

            case Filter::RegEx:
                if (sizeof($param) == 2) {
                    list ($field, $regex) = $param;
                } else {
                    $field = "{$entity}.name";
                    $regex = $param[0];
                }
                $where_sql = "{$field} REGEXP '{$regex}'";
                break;

            case Filter::IsTrue:
                $where_sql = "{$param[0]} = 1";
                break;

            case Filter::IsNull:
                $where_sql = "{$param[0]} IS NULL";
                break;

            case Filter::IsNotNull:
                $where_sql = "{$param[0]} IS NOT NULL";
                break;
        }

        return array($where_sql, $filter_sql, $sort_sql, $limit_sql, $join_sql);
    }
}
