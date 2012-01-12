<?php
$host   = isset($_POST['host']) ? trim($_POST['host']) : '';
$user   = isset($_POST['user']) ? trim($_POST['user']) : '';
$pass   = isset($_POST['pass']) ? trim($_POST['pass']) : '';
$dbname = isset($_POST['dbname']) ? trim($_POST['dbname']) : '';

define('TABLE_NAME', 'simple_queue');
define('DB_FILE', 'SimpleQueueDB.php');

testInstall();
if (empty($host) || empty($user) || empty($pass) || empty($dbname)) {
	// Something is not provided, so we need to prompt for the appropriate values
	$err = NULL;
	if (isset($_POST['submit'])) {
		// Form has been submitted, so show an error
		$err = 'All fields are required. Please enter all data and try again.';
	}

	showForm($err);
	exit;
}

// Attempt connection and check the DB access
$err = NULL;
$con = @mysql_connect($host, $user, $pass);
if (FALSE === $con) {
	$err = 'Could not connect to MySql server. Please check credentials and try again.';
} elseif (FALSE === @mysql_select_db($dbname, $con)) {
	$err = 'Could not select database. Please check DB name and permissions.';
}

if (NULL !== $err) {
	showForm($err);
	exit;
}

// All connections are good, so let's update the DB file
$txt = str_replace(
	array('$user = \'\';', '$pass = \'\';', '$host = \'\';', '$dbname = \'\';'), 
	array('$user = \'' . $user . '\';', '$pass = \'' . $pass . '\';', '$host = \'' . $host . '\';', '$dbname = \'' . $dbname . '\';'), 
	$db_content);

if (FALSE === @file_put_contents(DB_FILE, $txt)) {
	$err = 'Could not update the SimpleQueueDB.php file. Please check file permissions and try again.';
	showForm($err);
	exit;
}

// Check to see that we can create the database table
try {
	require(DB_FILE);
	$c = SimpleQueueDB::getInstance();
	$queries = getQueries();
	$create  = $queries[0];

	if (FALSE === @mysql_query($create)) {
		throw new Exception('Could not create the database table. Check user permissions.');
	}

	showConfirm();
	exit;
} catch (Exception $e) {
	showForm($e->getMessage());
	exit;
}

function showForm($err) {
	global $host, $user, $pass, $dbname;
	header('Content-type: text/html');
	$error = (NULL === $err) ? '' : '<p class="error">' . $err . '</p>';

	echo <<<EOF
<!DOCTYPE html>
<html>
<head>
<title>PHP SimpleQueue Installation</title>
<style type="text/css">
* {
	margin: 0;
	padding: 0;
}

body {
	padding: 15px;
	background-color: #EAF4F7;
	font-family: Verdana, Helvetica, Arial, sans-serif;
	font-size: 12px;
}

#install-form {
	border: 2px solid #8FBDC9;
	background-color: #fff;
	padding: 15px;
}

h1 {
	font-weight: normal;
	font-size: 18px;
	color: #709DA5;
	margin-bottom: 8px;
}

form td {
	padding: 3px 8px;
}

form td.label {
	width: 110px;
	font-size: 12px;
	color: #444;
	text-align: right;
}

form td.value input {
	border: 1px solid #ADC4CE;
	font-size: 12px;
	padding: 2px;
	width: 180px;
}

form td.value input.active {
	border-color: #1F6670;
}

form td.submit input {
	font-size: 14px;
	cursor: pointer;
	padding: 4px 10px;
}

#footer {
	padding: 15px;
	color: #7C9EA8;
}

p.copyright {
	text-align: center;
	font-size: 11px;
}

p.copyright a {
	text-decoration: none;
	font-weight: bold;
	color: #4F7984;
}

p.copyright a:hover {
	text-decoration: underline;
}

p.error {
	margin: 4px 0 10px;
	font-size: 12px;
	font-weight: bold;
	color: #ff0000;
}
</style>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('form td.value input').on('focus', function(e) {
		e.preventDefault();
		$(this).addClass('active');
	}).on('blur', function(e) {
		e.preventDefault();
		$(this).removeClass('active');
	}).get(0).focus();

	$('form td.submot input').click(function(e) {
		$(this).attr('disabled', true);
	});
});
</script>
</head>
<body>
<div id="install-form">
	<h1>PHP SimpleQueue Installation</h1>
	$error
	<form name="installation" action="" method="post">
		<table border="0" cellspacing="0" cellpadding="0">
			<tr>
				<td class="label">DB host</td>
				<td class="value"><input type="text" name="host" value="$host" /></td>
			</tr>
			<tr>
				<td class="label">DB user</td>
				<td class="value"><input type="text" name="user" value="$user" /></td>
			</tr>
			<tr>
				<td class="label">DB password</td>
				<td class="value"><input type="password" name="pass" value="$pass" /></td>
			</tr>
			<tr>
				<td class="label">DB name</td>
				<td class="value"><input type="text" name="dbname" value="$dbname" /></td>
			</tr>
			<tr>
				<td class="label">&nbsp;</td>
				<td class="submit"><input type="submit" name="submit" value="Install" /></td>
			</tr>
		</table>
	</form>
</div>
<div id="footer">
	<p class="copyright">&copy; Copyright 2012 <a href="http://www.guahanweb.com">Guahan Web</a> &#8211 all rights reserved</p>
</div>
</body>
</html>
EOF;
}

function showConfirm() {
	echo <<<EOF
<!DOCTYPE html>
<html>
<head>
<title>PHP SimpleQueue Installation</title>
<style type="text/css">
* {
	margin: 0;
	padding: 0;
}

body {
	padding: 15px;
	background-color: #EAF4F7;
	font-family: Verdana, Helvetica, Arial, sans-serif;
	font-weight: 12px;
}

#install-form {
	border: 2px solid #8FBDC9;
	background-color: #fff;
	padding: 15px;
}

h1 {
	font-weight: normal;
	font-size: 18px;
	color: #709DA5;
	margin-bottom: 8px;
}

#footer {
	padding: 15px;
	color: #7C9EA8;
}

p {
	font-size: 12px;
	color: #444;
	line-height: 1.5em;
	margin: 4px 0 18px;
}

p.copyright {
	text-align: center;
	font-size: 11px;
}

p.copyright a {
	text-decoration: none;
	font-weight: bold;
	color: #4F7984;
}

p.copyright a:hover {
	text-decoration: underline;
}
</style>
</head>
<body>
<div id="install-form">
	<h1>PHP SimpleQueue Installation</h1>
	<p>Installation has been successfully completed. You may now use SimpleQueue in any scripts or applications.</p>
</div>
<div id="footer">
	<p class="copyright">&copy; Copyright 2012 <a href="http://www.guahanweb.com">Guahan Web</a> &#8211 all rights reserved</p>
</div>
</body>
</html>
EOF;
}

function getQueries() {
	$str = trim(file_get_contents(dirname(__FILE__) . '/sql/table.sql'));
	$qs  = explode(';', $str);
	$ret = array();
	for ($i = 0; $i < count($qs); $i++) {
		$q = trim($qs[$i]);
		if (!empty($q)) {
			$q = str_replace("\n\r", " ", $q);
			$ret[] = $q;
		}
	}

	return $ret;
}

function testInstall() {
	$db_content = file_get_contents(DB_FILE);
	if (preg_match_all('/(user|pass|host|dbname) = \'([^\']+)/im', $db_content, $matches, PREG_SET_ORDER)) {
		$tmp = array(
			'user' => $matches[0][2],
			'pass' => $matches[1][2],
			'host' => $matches[2][2],
			'dbname' => $matches[3][2]
		);

		if (FALSE !== ($con = @mysql_connect($tmp['host'], $tmp['user'], $tmp['pass'], TRUE))) {
			if (FALSE !== @mysql_select_db($tmp['dbname'], $con)) {
				if (FALSE !== ($sql = @mysql_query("SHOW TABLES"))) {
					while ($row = @mysql_fetch_row($sql)) {
						if ($row[0] === TABLE_NAME) {
							showConfirm();
							exit;
						}
					}
				}
			}
		}
	}
}
?>
