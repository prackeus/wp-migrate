<?php

	$data = array(
		'host' => '',
		'user' => '',
		'pw' => '',
		'name' => '',
		'table' => '',
		'search' => '',
		'replace' => '',
	);

	if (!empty($_POST))
	{
		$data = $_POST;
	}

	function search_replace_object($object, $search, $replace)
	{
		foreach ($object as $property => $value)
		{
			if (is_object($value))
			{
				$object->$property = search_replace_object($value, $search, $replace);
			}
			elseif (is_array($value))
			{
				$object->$property = search_replace_array($value, $search, $replace);
			}
			elseif (is_string($value))
			{
				$object->$property = str_replace($search, $replace, $value);
			}
		}
		return $object;
	}

	function search_replace_array($array, $search, $replace)
	{
		foreach ($array as $property => $value)
		{
			if (is_object($value))
			{
				$array[$property] = search_replace_object($value, $search, $replace);
			}
			elseif (is_array($value))
			{
				$array[$property] = search_replace_array($value, $search, $replace);
			}
			elseif (is_string($value))
			{
				$array[$property] = str_replace($search, $replace, $value);
			}
		}
		return $array;
	}

	$x = 0;
	$fields = array(
		'host' => 'Host',
		'user' => 'User',
		'name' => 'DB Name',
		'table' => 'Table',
		'search' => 'Search',
		'replace' => 'Replace With',
	);
	$message = array();

	$submitted = false;
	$errors = array();
	foreach ($fields as $field => $label)
	{
		$$field = '';
		if (!empty($_POST[$field]))
		{
			$submitted = true;
			$$field = $_POST[$field];
		}
		else
		{
			$errors[] = 'Please enter a '.$label;
		}
	}
	if (!$submitted)
	{
		$errors = array();
	}
	elseif (empty($errors))
	{
		$pw = $_POST['pw'];

		//Process
		/*
		$pdo = new PDO(
		    'mysql:host='.$host.';dbname='.$name,
		    $user,
		    $pw
		);
		*/
		$conn = mysql_connect($host, $user, $pw);
		mysql_select_db($name);
		
		//Get searchable fields to process
		$textFields = array(
			'varchar',
			'longtext',
			'mediumtext',
			'tinytext',
			'text',
			'mediumblob',
			'blob',
			'tinyblob',
			'longblob',
		);

		$primaryKey = null;
		$searchableFields = array();
		//$query = $pdo->prepare("SHOW COLUMNS FROM $table");
		//$result = $query->execute();
		//while ($row = $query->fetch())
		$result = mysql_query("SHOW COLUMNS FROM $table");
		while ($row = mysql_fetch_array($result))
		{
			if ($row['Key'] == 'PRI')
			{
				$primaryKey = $row['Field'];
			}
			foreach ($textFields as $tf)
			{
				if (preg_match('|'.$tf.'|', $row['Type']))
				{
					$searchableFields[] = $row['Field'];
					break;
				}					
			}
		}

		//No primary key
		if (empty($primaryKey))
		{
			$message[] = $table." not migrated due to lack of primary key";
			continue;
		}

		//Nothing to search for this table
		if (empty($searchableFields))
		{
			continue;
		}

		//pull records for the table
		$fields = implode("`, `", $searchableFields);
		/*
		$query = $pdo->prepare("SELECT `$primaryKey`, `$fields` FROM `$table`");
		$result = $query->execute();
		while ($row = $query->fetch())
		*/
		$result = mysql_query("SELECT `$primaryKey`, `$fields` FROM `$table`");
		while ($row = mysql_fetch_array($result))
		{
			foreach ($searchableFields as $field)
			{
				//see if serialized
				$object = unserialize($row[$field]);
				if ($object !== false)
				{
					if (is_object($object))
					{
						$object = search_replace_object($object, $search, $replace);
					}
					else
					{
						$object = search_replace_array($object, $search, $replace);

					}
					$row[$field] = serialize($object);
				}
				else
				{
					$row[$field] = str_replace($search, $replace, $row[$field]);
				}
			}
			$updateFields = array();
			$updateFieldVals = array();
			$updateFieldCtr = 1;
			foreach ($searchableFields as $field)
			{
				//$updateFieldVals[':bindParam'.$updateFieldCtr] = $row[$field];
				//$updateFields[] = "$field = :bindParam".$updateFieldCtr;
				//$updateFieldCtr++;
				$updateFields[] = "$field = '".mysql_real_escape_string($row[$field])."'";
			}
			$updateResult = mysql_query("UPDATE $table SET ".implode(',', $updateFields)." WHERE $primaryKey = {$row[$primaryKey]}") or mysql_error();
			/*
			$updateQuery = $pdo->prepare("UPDATE $table SET ".implode(", ", $updateFields)." WHERE `$primaryKey` = :primaryKey");
			$updateQuery->bindParam(':primaryKey', $row[$primaryKey]);
			foreach ($updateFieldVals as $key => $value)
			{
				$updateQuery->bindParam($key, $value);
			}
			*/
		}
		$message[] = 'Migrate successful';
	}
	$message = implode("<br>", $message);

?><!doctype HTML>
<html>
	<head>
		<title>WP Migrate</title>
	</head>
	<body>
		<form action="" method="post">
<?php if (!empty($message)) : ?>
			<p class="message"><?php echo $message; ?></p>
<?php endif; ?>
<?php if (!empty($errors)) : ?>
			<ul class="errors">
<?php 	foreach ($errors as $error) : ?>
				<li><?php echo $error; ?></li>
<?php 	endforeach; ?>
			</ul>
<?php endif; ?>
			<fieldset>
				<legend>DB</legend>
				<div><label for="host">Host:</label><input type="text" name="host" value="<?php echo $data['host']; ?>"></div>
				<div><label for="user">User:</label><input type="text" name="user" value="<?php echo $data['user']; ?>"></div>
				<div><label for="pw">Password:</label><input type="text" name="pw" value="<?php echo $data['pw']; ?>"></div>
				<div><label for="name">DB Name:</label><input type="text" name="name" value="<?php echo $data['name']; ?>"></div>
			</fieldset>
			<fieldset>
				<legend>Replace parameters</legend>
				<div><label for="table">Table:</label><input type="text" name="table" value="<?php echo $data['table']; ?>"></div>
				<div><label for="search">Search:</label><input type="text" name="search" value="<?php echo $data['search']; ?>"></div>
				<div><label for="replace">Replace With:</label><input type="text" name="replace" value="<?php echo $data['replace']; ?>"></div>
			</fieldset>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>