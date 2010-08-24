<?php
$conn = mysql_connect("localhost", $config['username'], $config['password'])
or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

$db = mysql_select_db($config['dbname'], $conn)
or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
?>
