<?php

define('DIR', $_SERVER['DOCUMENT_ROOT'].'/');

require_once(DIR.'includes/config.php');
require_once(DIR.'includes/functions.php');
require_once(DIR.'includes/data.php');

// Starting block to calculate execution time.
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$starttime = $mtime; 

$patterns = array(
	'home' => '/^home$/i',
	'login' => '/^login$/i',
	'logout' => '/^logout$/i',
	'register' => '/^register$/i',
	'members' => '/^members(\/((page[0-9]+)|([a-zA-z]+-[a-zA-z]+\.[0-9]+)|([0-9]+)|sort\/(date|abc)))?$/i',
	'account' => '/^account(\/delete(\/confirm)?)?$/i',
	'schedule' => '/^schedule(\/edit)?$/i',
	'recover-password' => '/^recover-password$/i',
	'contact' => '/^contact$/i'
);

$request_uri = trim($_SERVER['REQUEST_URI'], " \t\n\r\0\x0B/");

if (!ini_get('session.use_only_cookies')) {
    ini_set('session.use_only_cookies', 1); // security
}

ini_set('session.gc_maxlifetime', 1440);
ini_set('session.gc_probability', 100);
ini_set('session.gc_probability', 999);

// start output buffering with gzip encoding if available
if (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip')) ob_start("ob_gzhandler");
else ob_start();


session_name('sched_session');
session_start();


if(isset($_COOKIE['sched_user']) && $_SESSION['logged_in'] != true) {
	require_once(MYSQL);
	$cookie = explode(',', $_COOKIE['sched_user']);
	$sql = sprintf_escape("SELECT user_id, email, first_name, pass FROM ".TABLE_PREFIX."users WHERE user_id='%u' AND pass='%s' LIMIT 1", $cookie[0], $cookie[1]);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			if(mysql_num_rows($result) === 1 && $cookie[2] === md5(md5($_SERVER['HTTP_USER_AGENT']).SALT)) {
				$row = mysql_fetch_assoc($result);
				$_SESSION['logged_in'] = true;
				$_SESSION['user_id'] = $row['user_id'];
				$_SESSION['first_name'] = $row['first_name'];
				$_SESSION['email'] = $row['email'];
				$_SESSION['user-agent'] = md5(md5($_SERVER['HTTP_USER_AGENT']).session_id().SALT);
			} else {
				// information in cookie is wrong. Cookie is bad, force logout and destroy cookie
				$_SESSION['logged_in'] = false;
				$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $config['basedomain'] : false;
				setcookie('sched_user', '', time()-42000, '/', $domain, 0, 0);
			}
}

// for security, check hashed user-agent, and force logout if it changes during session.
if(isset($_SESSION['user_id']) && $_SESSION['logged_in'] === true) {
	if(md5(md5($_SERVER['HTTP_USER_AGENT']).session_id().SALT) !== $_SESSION['user-agent']) {
		$_SESSION['logged_in'] = false;
	}
}
	
if($_SESSION['logged_in'] === true) {
	$logged_in = true;
} else $logged_in = false;



$url_structure = array();

?>
