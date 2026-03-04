<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$name = 'lsf';

$target = '/Users/jason/Projects/LSF/application/repository/trunk/private/domain';
$stub_location = '/Users/jason/Projects/LSF/application/repository/trunk/private/staysail/class.StaysailEntityStub.php';

$db = mysqli_connect($host, $user, $pass);
if (!$db) {die("No database connection is available at this time");}
$db->select_db($name);

$sql = "SHOW TABLES";
$res = $db->query($sql);

$tables = array();
while ($row = $res->fetch_assoc())
{
    $tables[] = $row["Tables_in_{$name}"];
}

$stub = file_get_contents($stub_location);
foreach ($tables as $classname)
{
	// Replace the class name
	$domain = preg_replace("/%classname%/", $classname, $stub);
	
	// Create the properties based on the table
	$sql = "DESCRIBE `{$classname}`";
	$res = $db->query($sql);
	$properties = $enum_functions = '';
	while ($row = $res->fetch_assoc())
	{
		$field = $row['Field'];
		$type = $row['Type'];
		
		if ($field == 'id' or $field == 'name' or $field == 'sort') {continue;}
		
		$staysail_type = getStaysailType($type);
		if (preg_match('/_id$/', $field)) {
			$field = preg_replace("/_id$/", '', $field);
			$staysail_type = "parent::AssignOne";
		}
		
		if (preg_match('/^enum\((.*)\)/', $type, $m)) {
			$values = $m[1];
			$return_array = '';
			$values = explode(',', $values);
			foreach ($values as $value)
			{
				$return_array .= "{$value} => {$value},";
			}
			$enum_functions .= "    public function {$field}_Options {return array({$return_array});}\n\n";
		}
		
		$properties .= "    public \${$field} = {$staysail_type};\n";
	}
	$domain = preg_replace("/%properties%/", $properties, $domain);
	$domain = preg_replace('/%enum_functions%/', $enum_functions, $domain);

	$filename = "{$target}/class.{$classname}.php";
	$fh = fopen("{$filename}", 'x');
	fwrite($fh, $domain);
	fclose($fh);	
	
	print "Done with {$classname}<br/>\n";
}

function getStaysailType($sql_type)
{
	if (preg_match('/^int/', $sql_type)) {
		return "parent::Int";
	}
	if (preg_match('/^float/', $sql_type)) {
		return "parent::Float";
	}
	if (preg_match('/^tinyint/', $sql_type)) {
		return "parent::Boolean";
	}
	if (preg_match('/^bit/', $sql_type)) {
		return "parent::Boolean";
	}
	if (preg_match('/^varchar/', $sql_type)) {
		return "parent::Line";
	}
	if (preg_match('/^datetime/', $sql_type)) {
		return "parent::Time";
	}
	if (preg_match('/^time/', $sql_type)) {
		return "parent::Date";
	}
	if (preg_match('/^decimal/', $sql_type)) {
		return "parent::Currency";
	}
	if (preg_match('/^enum/', $sql_type)) {
		return "parent::Enum";
	}
	if (preg_match('/^text/', $sql_type)) {
		return "parent::Text";
	}
	return "<UNKNOWN TYPE>";
}
