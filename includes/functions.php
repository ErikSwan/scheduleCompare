<?php

require_once("config.php");


function int_ok($val)
{
    if(func_num_args() !== 1)
        exit(__FUNCTION__.'(): not passed 1 arg');

    return ($val !== true) && ((string)abs((int) $val)) === ((string) ltrim($val, '-0'));
}

// to save time and space
function myhash($data) {
	global $config;
	return hash('sha256', hash('sha256', $data).$config['salt']);
}

function match($pattern, $subject, &$return_array) {
    $result = preg_match($pattern, $subject, $matches);
    if($result !== 0 && $result !== false) {
        $return_array = explode('/',trim($matches[0], " \t\n\r\0\x0B/"));
        return true;
    }
    else return false;
}

// Functions the same as sprintf, but escapes inputs automatically (for cleaner code) 
function sprintf_escape()
{
    $args = func_get_args();
    if (count($args) < 2)
        return false;
    $query = array_shift($args);
    $args = array_map('mysql_real_escape_string', $args);
    array_unshift($args, $query);
    $query = call_user_func_array('sprintf', $args);
    return $query;
}

function absolute_url( $page = '/' ) {
	$url = 'http://'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
	$url = rtrim($url, '/\\');
	$url .= '/' . $page;
	return $url;
}

function logged_in($header = 'Sorry!', $message = 'You need to be logged in to perform this action.', $no_quit = false) {
	if($_SESSION['logged_in'] == true) {
		return true;
	} else if(!$no_quit) {
		display_header($header);
		echo "<p>".$message."</p>\n";
		display_login_form(null, ltrim($_SERVER['REQUEST_URI'], '/'));
		display_footer();
		exit;
	} else return false;
}

// for building dropdown boxes (form output)
function build_teacher_list() {
	$sql = "SELECT teachers.teacher_id, teachers.first_name, teachers.last_name, departments.department_name FROM ".TABLE_PREFIX."teachers INNER JOIN ".TABLE_PREFIX.
	"departments ON teachers.department_id = departments.department_id ORDER BY teachers.last_name ASC, teachers.first_name ASC";
	$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
	$output = "\t<option value=\"none\" id=\"initialselect\">Select a teacher...</option>\n";
	while($row = mysql_fetch_assoc($result)) {
		$output .= "\t<option value=\"".$row['teacher_id'].'" class="'.str_replace(' ', '-', $row['department_name']).'">'.$row['last_name'].', '.$row['first_name']."</option>\n";
	}
	return $output;
}

function display_header($title = NULL, $h2_addition = NULL, $meta = NULL) {
	global $config;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="description" content="A tool for students at EPHS to automatically and easily compare their schedules online. Built by Erik Swan." />
<title>scheduleCompare<?php
if(isset($title)) {
	echo " | ".$title;
}
?></title>
<link href="/css/screen.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="/js/jquery.hoverIntent.minified.js"></script>
<script type="text/javascript">
		$(document).ready(function(){

			// global javascript
			window.setTimeout(function() {
				$('.fadeout').fadeOut(800);
			}, 1500);

			$(document).ready(function(){
				$('.jshide').hide();
				$('.js').show();
				$('.fadeout .closebutton').click(function() {
					alert("clicked");
					$(this).parent().fadeOut(800);
				});

			});

			$('#nav li ul').hide();
			$('#nav li').hoverIntent(function(e) {
				$(this).children('ul').stop(true, true).slideDown('fast');

			}, function(e) {
				$(this).children('ul').stop(true, true).slideUp('fast');
			});

		});
</script>
<?php if($_SESSION['page'] == 'schedule/edit' || $_SESSION['page'] == 'account') {
	?>
	<script type="text/javascript">

		// generated from database
		var classes = {<?php
		// dynamically generate this javascript variable based on classes in database.
		require_once(MYSQL);
		
		$sql = "SELECT classes.class_id, classes.class_name, departments.department_name FROM  ".TABLE_PREFIX."classes INNER JOIN ".TABLE_PREFIX."departments on classes.department_id = departments.department_id";
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

		while($row = mysql_fetch_assoc($result)) {
			echo "\n\t\t\t".$row['class_id']." : ['".addslashes($row['class_name'])."', '".str_replace(' ', '-', $row['department_name'])."' ],";
		}
		?>

		};

		// generated from database
		var teachers = {<?php
		// dynamically generate this javascript variable based on teachers in database.
		require_once(MYSQL);

		$sql = "SELECT t.teacher_id, CONCAT(t.first_name, ', ', t.last_name) AS teacher_name, d.department_name FROM  ".TABLE_PREFIX."teachers as t INNER JOIN ".TABLE_PREFIX."departments as d USING(department_id)";
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

		while($row = mysql_fetch_assoc($result)) {
			echo "\n\t\t\t".$row['teacher_id']." : ['".addslashes($row['teacher_name'])."', '".str_replace(' ', '-', $row['department_name'])."' ],";
		}
		?>

		};
	</script>
	<?php
}
if($_SESSION['page'] == 'schedule/edit') {
	?>
	<script type="text/javascript">
		// schedule javascript
		$(document).ready(function(){
			$('a.addskinny').click(function() {
				$(this).parent().siblings('.skinny').fadeIn();
				$(this).fadeOut();
				return false;
			});
			$('a.removeskinny').click(function() {
				$(this).parent().siblings('.classname').html("Open Hour");
				$(this).parent().siblings('.classinput').val('');
				$(this).parent().siblings('.teacherselect').each(function() {
						this.selectedIndex = 0;
				});
				$(this).parent().parent().fadeOut(400, function() {
					$(this).siblings().children('.addskinny').fadeIn(); // lol, needs work
				});
				return false;
			});
			$('#schedule_table td .teacherselect option').not('#initialselect').hide();
			$('#schedule_table td .classinput').change(function() {
				var class_id = Number($(this).val().replace(/-[0-9]*/, ''));
				if(class_id in classes) {
					$(this).siblings('.classname').html(classes[class_id][0]);
					$(this).siblings('.teacherselect').children('option').show();
					$(this).siblings('.teacherselect').children('option').not('.'+classes[class_id][1]+', #initialselect').hide();
					$(this).siblings('.teacherselect').each(function() {
						this.selectedIndex = 0;
					});
					$(this).val(class_id);
				} else if (class_id == 0) {
					$(this).siblings('.classname').html("Open hour");
					$(this).siblings('.teacherselect').children('option').show();
					$(this).siblings('.teacherselect').children('option').not('#initialselect').hide();
					$(this).siblings('.teacherselect').each(function() {
						this.selectedIndex = 0;
					});
					$(this).val('');

				} else {
					$(this).siblings('.classname').html('<span class="error">Class not found</span>');
					$(this).siblings('.teacherselect').children('option').show();
					$(this).siblings('.teacherselect').each(function() {
						this.selectedIndex = 0;
					});
					//$(this).val(class_id); - could confuse people if our database is wrong and they get frustrated that the application
					// is seemingly changing their input and then marking it as invalid
				}
			});
		
			$('#schedule_table td .classinput').each(function() {
				var class_id = $(this).val();
				if(class_id in classes) {
					$(this).siblings('.classname').html(classes[class_id][0]);
					$(this).siblings('.teacherselect').children('option').show();
					$(this).siblings('.teacherselect').children('option').not('.'+classes[class_id][1]+', #initialselect').hide();
				} else if(class_id != 0) {
					$(this).siblings('.classname').html('<span class="error">Class not found</span>');
					//$(this).val(class_id); - could confuse people if our database is wrong and they get frustrated that the application
					// is seemingly changing their input and then marking it as invalid
				}
			});


		});
	</script>
	<?php
}
if($_SESSION['page'] == 'account') {
	?>
	<script type="text/javascript">
		// account javascript
		
	</script>
	<?php
}

if(!empty($meta)) {
	?>
<meta http-equiv="refresh" content="2;url=<?php echo BASE.$meta; ?>" />
	<?php
}
?>
</head>

<body>
<div id="container">
<div id="header">
<a id="logo" title="Schedule Compare Tool, scheduleCompare" href="<?php echo BASE; ?>"><img src="/images/logo.png" alt="scheduleCompare" /></a>
<div id="login">
	<?php $l = $_SESSION['logged_in'];
	$p = $_SESSION['page'];

	 if(!$l) { ?>
      <a title="Login to scheduleCompare" href="/login/" id="login_text">Login</a> or
      <a title="Register with scheduleCompare" href="/register"><img src="/images/register_button.png" id="register_button" alt="Register" /></a>
    <?php } else { ?>
      Welcome, <?php echo $_SESSION['first_name']; ?>! &mdash; <a title="Logout of scheduleCompare" href="/logout/" id="login_text">Logout</a>
      <?php } ?>
</div>
	<ul id="nav">
		<li><a href="/" <?php if($p=='home') echo "class='selected'"; ?>>Home</a></li>
		<?php if(!$l) { ?><li><a href="/register/" <?php if($p=='register') echo "class='selected'"; ?>>Register</a></li><?php } ?>
		<li><a href="/members/" <?php if($p=='members') echo "class='selected'"; ?>>Members</a></li>
		<?php if($l) { ?><li><a href="/schedule/" <?php if($p=='schedule') echo "class='selected'"; ?>>My Schedule <img src="/images/down_arrow.png" /></a>
			<ul class="children">
				<li><a href="/schedule/edit" <?php if($p=='schedule/edit') echo "class='selected'"; ?>>Edit</a></li>
			</ul>
		</li><?php } ?>
		<?php if($l) { ?><li><a href="/account/" <?php if($p=='account') echo "class='selected'"; ?>>My Account</a></li><?php } ?>
		<li><a href="/contact/">Contact</a></li>
	</ul>
</div>
	<div id="page">
<?php if(isset($title)) { ?>
<h2><?php echo $title.$h2_addition; ?></h2>
<?php } 
}

function display_footer() {
	global $starttime;
?>
	</div>
	<div id="seperator"><!-- needed for sticky footer --></div>
</div>
<div id="footer">
	<div id="footer_content">
		<?php $l = $_SESSION['logged_in'];
		$p = $_SESSION['page'];
		?>
		<img src="/images/logo_mark.png" class="logo_mark" />
		<div id="facebook"><fb:like href="http://schedules.erikswan.net/" show_faces="false" colorscheme="dark"></fb:like></div>
		<p class="footer-links">
		<a href="/" <?php if($p=='home') echo "class='selected'"; ?>>Home</a> |
		<?php if(!$l) { ?><a href="/register/" <?php if($p=='register') echo "class='selected'"; ?>>Register</a> | <?php } ?>
		<a href="/members/" <?php if($p=='members') echo "class='selected'"; ?>>Members</a> |
		<?php if($l) { ?><a href="/schedule/" <?php if($p=='schedule') echo "class='selected'"; ?>>My Schedule</a> | <?php } ?>
		<?php if($l) { ?><a href="/account/" <?php if($p=='account') echo "class='selected'"; ?>>My Account</a> | <?php } ?>
		<a href="/contact/">Contact</a> | 
		<?php if(!$l) { ?><a href="/login/" <?php if($p=='login') echo "class='selected'"; ?>>Login</a><?php } ?>
		<?php if($l) { ?><a href="/logout/" <?php if($p=='logout') echo "class='selected'"; ?>>Logout</a><?php } ?>
		</p>
		<p style="clear:right; width:500px;">Code and Design by <a href="http://www.facebook.com/erikswan">Erik Swan</a>.
		Original idea and class and teacher database provided by <a href="http://www.facebook.com/alex.reinking">Alex Reinking</a>, with modification.
		If you like this tool, click the Like button! <span class="low_key">Execution Time: <?php
$mtime = microtime();
$mtime = explode(" ",$mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = ($endtime - $starttime);
echo(round($totaltime, 5));
if(DEBUG) {
	?><p><?php
	echo "SESSION ID: ".session_id()."<br />";
	echo "SESSION ARRAY: ";
	print_r($_SESSION);
	?></p><?php
}
?> seconds</span></p>
	</div>
</div>
<script src="http://static.getclicky.com/js" type="text/javascript"></script>
<script type="text/javascript">clicky.init(244000);</script>
<noscript><p><img alt="Clicky" width="1" height="1" src="http://in.getclicky.com/244000ns.gif" /></p></noscript>
<div id="fb-root"></div>
<script type="text/javascript">
      window.fbAsyncInit = function() {
        FB.init({appId: '134246449951701', status: true, cookie: true,
                 xfbml: true});
      };
      (function() {
        var e = document.createElement('script');
        e.type = 'text/javascript';
        e.src = document.location.protocol +
          '//connect.facebook.net/en_US/all.js';
        e.async = true;
        document.getElementById('fb-root').appendChild(e);
      }());
</script>
</body>
</html>
<?php
}

function display_error($code = 404, $message = '') {
	
	$codes = array(404);
	if(!in_array($code, $codes)) $code = 404;
	
	$title_string = array(
		404 => 'Error - Page Not Found'
	);
	$error_string = array(
		404 => "Sorry, but I couldn't find the page you were looking for."
	);
	$headers = array(
		404 => "HTTP/1.1 404 Not Found"
	);
	
	header($headers[$code]);
	display_header($title_string[$code]);
	?>
    <?php if(isset($message)) { ?>
    <p><?php echo $error_string[$code].' '.$message; ?></p>
    <?php }
	display_footer();
	
	ob_end_flush();
	exit;
}

function display_registration_form($errors = null) {
	$a = isset($_POST['submit']);
	?>
    <p>Registration is simple. We need your email address so you can login and so we can send you lost password emails, and we need your full name so you are identifiable. You may optionally provide a Facebook profile URL, which will be linked on your profile page so that people who don't recognize you by name can perhaps see a face.</p>
    <p>Fields marked with <em class="red">*</em> are required.</p>
    <?php 
    if(!empty($errors)) {
	    echo "\n<ul class=\"error\">\n";
	    foreach($errors as $error) {
		    echo "<li>".$error."</li>\n";
	    }
	    echo "</ul>";
    }
    ?>
    <form action="/register" method="post" name="registration" id="registration">
    <label for="first_name"><em>*</em> First name:</label>
    <input name="first_name" id="first_name" type="text" <?php if($a) echo 'value="'.$_POST['first_name'].'"'; ?> size="30" />
    <p class="low_key">(Real name, please. Fake accounts will be deleted.)</p>
    <label for="last_name"><em>*</em> Last name:</label>
    <input name="last_name" id="last_name" type="text" <?php if($a) echo 'value="'.$_POST['last_name'].'"'; ?> size="30" />
    <br />
    <label for="email"><em>*</em> Email:</label>
    <input name="email" id="email" type="text" <?php if($a) echo 'value="'.$_POST['email'].'"'; ?> size="30" />
    <p class="low_key">(This will be used to login. It will <strong>NOT</strong> be displayed publicly,<br />
    and you will <strong>NOT</strong> be sent email unless you forget your password.<br />Your privacy is important to us.)</p>
    <label for="grade"><em>*</em> Grade:</label>
    <select name="grade" id="grade">
	    <option value="9" <?php if($a AND $_POST['grade'] == 9) echo 'selected="selected"'; ?>>9 / Freshman</option>
	    <option value="10" <?php if($a AND $_POST['grade'] == 10) echo 'selected="selected"'; ?>>10 / Sophomore</option>
	    <option value="11" <?php if($a) { if($_POST['grade'] == 11) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>11 / Junior</option>
	    <option value="12" <?php if($a AND $_POST['grade'] == 12) echo 'selected="selected"'; ?>>12 / Senior</option>
    </select>
    <p class="low_key">(This will allow us to do some cool filtering and matching. Please be honest.)</p>
    <label for="password"><em>*</em> Password:</label>
    <input name="password" id="password" type="password" <?php if($a) echo 'value="'.$_POST['password'].'"'; ?> size="30" />
    <p class="low_key">(Try to make it secure.)</p>
    <label for="confirm_password"><em>*</em> Confirm Password:</label>
    <input name="confirm_password" id="confirm_password" type="password" <?php if($a) echo 'value="'.$_POST['confirm_password'].'"'; ?> size="30" />
    <br />
    <label for="fb_url">Facebook URL:</label>
    <input name="fb_url" id="fb_url" type="text" <?php if($a) echo 'value="'.$_POST['fb_url'].'"'; ?> size="50" />
    <p class="low_key">(For schweet linkage.)</p>
    <p><input name="whole_schedule_perm" type="radio" value="1" <?php if($a AND $_POST['whole_schedule_perm'] === '1') echo 'checked="checked"'; ?> /> Yes, I want anyone who registers on the site to be able to view my entire schedule.<br />
	    <input name="whole_schedule_perm" type="radio" value="0" <?php if($a AND $_POST['whole_schedule_perm'] === '0') echo 'checked="checked"'; ?> /> No, I want to keep my whole schedule private. Other members will only be able to see which specific classes they have with me.
    </p>
    <p><input name="submit" class="submit" type="submit" value="Sign me up!" /></p>
    </form>
    <?php
}

function display_login_form($errors = null, $redirect = 'schedule/') {
	?>
    <p>You may log in using the form below. If you forgot your password, click on the forgot password link
    to get an email sent to the email address on record. If you do not have an account, you may <a href="/register">register here</a>.</p>
    <?php
	$a = isset($_POST['submit']);
	if(!empty($errors)) {
		    echo "\n<ul class=\"error\">\n";
		    foreach($errors as $error) {
			    echo "<li>".$error."</li>\n";
		    }
		    echo "</ul>";
	    }
    ?>
    <form action="/login" method="post" name="login" id="login">
    <label for="email">Email:</label>
    <input name="email" id="email" type="text" <?php if($a) echo 'value="'.$_POST['email'].'"'; ?> size="30" /><br />
    <label for="password">Password:</label>
    <input name="password" id="password" type="password" size="30" />
    <p><input name="rememberme" type="checkbox" value="1" <?php if($a AND $_POST['rememberme'] === '1') { ?> checked="checked" <?php } ?> /> Remember me.</p>
    <p><a href="/recover-password/">I forgot my password.</a></p>
    <input name="redirect" type="hidden" value="<?php echo $redirect; ?>" />
    <p><input name="submit" class="submit" type="submit" value="Login" /></p>
    </form>
    <?php
}

function build_schedule_form($errors = null, $fromdb = false) {

	$teacher_form = build_teacher_list();
	$a = isset($_POST['submit']);
	if(!empty($errors)) {
		    echo "\n<ul class=\"error\">\n";
		    foreach($errors as $error) {
			    echo "<li>".$error."</li>\n";
		    }
		    echo "</ul>";
	    }
	 ?>
	 <script type="text/javascript" src="/js/autocompletion.js"></script>
	 <script type="text/javascript" >
	 function setValue(str,myName)
	 {
	     document.getElementById(myName).value=str;
	     $('#' + myName).change();
	     document.getElementById("autoSuggestionsList").innerHTML="";
	 }
	 </script>
	 <div id="autoSuggestionsList" style="position: relative; top: -4px; width='100px;'" > </div>
	 
	<form action="/schedule/edit" method="post" name="schedule" id="schedule">
	<table border="0" id="schedule_table">
	<thead>
	<tr>
	<th>&nbsp;</th>
	<th>Term 1</th>
	<th>Term 2</th>
	<th>Term 3</th>
	<th>Term 4</th>
	</tr>
	</thead>
	<tbody>
		<!-- SEPERATOR -->
	<?php
	if($fromdb) {
		$sql = sprintf_escape("SELECT * FROM ".TABLE_PREFIX."schedules WHERE user_id=%u LIMIT 1", $_SESSION['user_id']);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
		$schedule = mysql_fetch_assoc($result);
	}
		for($i = 1; $i <= 4; $i++) {
			?>
			<tr>
			<td>Hour <?php echo $i; ?></td>
			<?php
			for($j = 1; $j <= 4; $j++) {
				if($fromdb) {
					$sql = sprintf_escape("SELECT `class-teacher_1`, `class-teacher_2` FROM `".TABLE_PREFIX."class-hours` WHERE combo_id=%u", $schedule['t'.$j.'_h'.$i]);
					$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
					$row = mysql_fetch_assoc($result);

					$sql = sprintf_escape("SELECT class_id, teacher_id FROM `".TABLE_PREFIX."classes-teachers` WHERE `class-teacher_id`=%u", $row['class-teacher_1']);
					$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
					$row_1 = mysql_fetch_assoc($result);
					$sql = sprintf_escape("SELECT class_id, teacher_id FROM `".TABLE_PREFIX."classes-teachers` WHERE `class-teacher_id`=%u", $row['class-teacher_2']);
					$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
					$row_2 = mysql_fetch_assoc($result);
					$class_submit = $row_1['class_id'];
					$class_submit_2 = $row_2['class_id'];
					$new_form = preg_replace('/<option value="'.$row_1['teacher_id'].'"/i', '<option value="'.$row_1['teacher_id'].'" selected="selected"', $teacher_form );
					$new_form_2 = preg_replace('/<option value="'.$row_2['teacher_id'].'"/i', '<option value="'.$row_2['teacher_id'].'" selected="selected"', $teacher_form );
				} else {

					if($a) {
						$class_submit = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class']);
						$class_submit_2 = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class_2']);
						$new_form = preg_replace('/<option value="'.$_POST['t'.$j.'_h'.$i.'_teacher'].'"/i', '<option value="'.$_POST['t'.$j.'_h'.$i.'_teacher'].'" selected="selected"', $teacher_form );
						$new_form_2 = preg_replace('/<option value="'.$_POST['t'.$j.'_h'.$i.'_teacher_2'].'"/i', '<option value="'.$_POST['t'.$j.'_h'.$i.'_teacher_2'].'" selected="selected"', $teacher_form );
					} else {
						$new_form = $teacher_form;
						$new_form_2 = $teacher_form;
					}
				}
				?>
			<td>
			<p class="classname js">Open hour</p>
			Class #: <input name="t<?php echo $j; ?>_h<?php echo $i; ?>_class" class="classinput" id="t<?php echo $j; ?>_h<?php echo $i; ?>_class" onkeyup="getInfo(this.value,200,'t<?php echo $j; ?>_h<?php echo $i; ?>_class')" type="text" <?php if(!empty($class_submit)) echo 'value="'.$class_submit.'"'; ?> size="4" /><br />
			Teacher:<br /><select name="t<?php echo $j; ?>_h<?php echo $i; ?>_teacher" class="teacherselect">
			<?php echo $new_form; ?>
			</select>
			<hr />
			<p class="js"><a href="#" class="addskinny <?php if($a && !empty($class_submit_2)) echo 'jshide'; ?>">+ Add Skinny</a></p>
			<p class="jshide">Skinny:</p>
			<div class="skinny jshide <?php if(!empty($class_submit_2)) echo 'js'; ?>">
			<p class="classname js">Open hour</p>
			Class #: <input name="t<?php echo $j; ?>_h<?php echo $i; ?>_class_2" class="classinput" id="t<?php echo $j; ?>_h<?php echo $i; ?>_class_2" onkeyup="getInfo(this.value,200,'t<?php echo $j; ?>_h<?php echo $i; ?>_class_2')" type="text" <?php if(!empty($class_submit_2)) echo 'value="'.$class_submit_2.'"'; ?> size="4" /><br />
			Teacher:<br /><select name="t<?php echo $j; ?>_h<?php echo $i; ?>_teacher_2" class="teacherselect">
			<?php echo $new_form_2; ?>
			</select>
			<hr />
			<p class="js"><a href="#" class="removeskinny">+ Remove Skinny</a></p>
			</div>
			</td>
				<?php
			}
			?>
			</tr>
			<?php

		}
	?>
		<!-- SEPERATOR -->
	</tbody>
	</table>
	<input name="submit" type="submit" value="Update Schedule" /><a href="/schedule/edit" class="button" style="margin-left: 15px;">Cancel</a>
	</form>
    <?php
}

function print_schedule($display_names = false, $user_id = null) {
	if(!isset($user_id)) {
		$user_id = $_SESSION['user_id'];
	}
	$sql = sprintf_escape("SELECT * FROM ".TABLE_PREFIX."schedules WHERE user_id=%u LIMIT 1", $user_id);
	$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
	$schedule = mysql_fetch_assoc($result);
	?>
	<table border="0" id="schedule_table">
	<thead>
	<tr>
	<th>&nbsp;</th>
	<th>Term 1</th>
	<th>Term 2</th>
	<th>Term 3</th>
	<th>Term 4</th>
	</tr>
	</thead>
	<tbody>
	<?php
	for($i = 1; $i <= 4; $i++) {
		?>
		<tr>
		<td>Hour <?php echo $i; ?></td>
		<?php
		for($j = 1; $j <= 4; $j++) {
			$sql = sprintf_escape("SELECT `class-teacher_1`, `class-teacher_2` FROM `".TABLE_PREFIX."class-hours` WHERE combo_id=%u", $schedule['t'.$j.'_h'.$i]);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			$row_upper = mysql_fetch_assoc($result);

			$sql = sprintf_escape("SELECT c.class_id, c.class_name, CONCAT(t.last_name, ', ', t.first_name) AS teacher_name FROM `".TABLE_PREFIX."classes-teachers` as ct
				INNER JOIN classes AS c USING(class_id)
				INNER JOIN teachers AS t USING(teacher_id)
				WHERE `class-teacher_id`=%u", $row_upper['class-teacher_1']);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			$row = mysql_fetch_assoc($result);
			if(empty($row['class_name'])) {
				$row['class_name'] = 'Open Hour';
			}
			//print_r($row);
			//exit;
			?>
			<td>
				<p class="classname"><?php echo $row['class_name']." <span class=\"low_key\">#".$row['class_id']."</span>"; ?></p>
				<p><?php echo $row['teacher_name']; ?></p>
				<?php
				if($row_upper['class-teacher_2'] != 0) {
				// skinny
				$sql = sprintf_escape("SELECT c.class_id, c.class_name, CONCAT(t.first_name, ', ', t.last_name) AS teacher_name FROM `".TABLE_PREFIX."classes-teachers` as ct
				INNER JOIN classes AS c USING(class_id)
				INNER JOIN teachers AS t USING(teacher_id)
				WHERE `class-teacher_id`=%u", $row_upper['class-teacher_2']);
				$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
				$row = mysql_fetch_assoc($result);
				?>
				<hr />
				<p class="low_key">Skinny:</p>
				<p class="classname"><?php echo $row['class_name']." <span class=\"low_key\">#".$row['class_id']."</span>"; ?></p>
				<p><?php echo $row['teacher_name']; ?></p>
				<?php
			}
				if($display_names) {
					?>

					<hr />
					<?php
					$sql = sprintf_escape("SELECT u.user_id, u.whole_schedule_perm, CONCAT(u.first_name, ' ', u.last_name) AS name FROM `".TABLE_PREFIX."schedules` INNER JOIN users AS u USING(user_id) WHERE %s=%u", 't'.$j.'_h'.$i, $schedule['t'.$j.'_h'.$i]);
					$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
					if(mysql_num_rows($result) <= 1) {
						if($user_id == $_SESSION['user_id']) {
							// the user is viewing their own schedule
						?>
						<p class="low_key">No classmates found.</p>
						<?php
						} else {
							?>
							<p class="low_key">No other students found.</p>
							<?php
						}
					} else {
						if($user_id == $_SESSION['user_id']) {
							// the user is viewing their own schedule
						?>
						<span class="low_key">Classmates:</span><br />
						<?php
						while($row = mysql_fetch_assoc($result)) {
								if($row['user_id'] != $user_id) {
								?>
								<a href="/members/<?php echo str_replace(' ', '-', $row['name']).'.'.$row['user_id']; ?>/"><?php echo $row['name']; ?></a><br />
								<?php
								}
							}
						} else {

							?>
							<span class="low_key">Other students in this class:</span><br />
							<?php
							while($row = mysql_fetch_assoc($result)) {
								if($row['user_id'] != $user_id  && $row['whole_schedule_perm'] == 1) {
								?>
								<a href="/members/<?php echo str_replace(' ', '-', $row['name']).'.'.$row['user_id']; ?>/"><?php echo $row['name']; ?></a><br />
								<?php
								}
							}
						}

					}

				}
			?>
			</td>
			<?php
		}
		
		?>
		</tr>
		<?php
	}
	
	?>
	</tbody>
	</table>
	<?php
}

/* Function that takes two user id's and outputs an array of classes they have together */
// array[class_id]['name']
// array[class_id]['teacher']
// array[class_id]['term']
// array[class_id]['hour']

function schedule_compare($user_id_1, $user_id_2) {
	require_once(MYSQL);

	$sql = sprintf_escape("SELECT t1_h1, t1_h2, t1_h3, t1_h4, t2_h1, t2_h2, t2_h3, t2_h4, t3_h1, t3_h2, t3_h3, t3_h4, t4_h1, t4_h2, t4_h3, t4_h4 FROM ".TABLE_PREFIX."schedules WHERE user_id=%u OR user_id=%u LIMIT 2", $user_id_1, $user_id_2);
	$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

	if(mysql_num_rows($result) !== 2) {
		return false;
	}

	$same = array_intersect_assoc(mysql_fetch_assoc($result), mysql_fetch_assoc($result));
	foreach($same as $hour => $class) {
		$sql = sprintf_escape("SELECT `class-teacher_1`, `class-teacher_2` FROM `".TABLE_PREFIX."class-hours` WHERE combo_id=%u", $class);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
		$row = mysql_fetch_assoc($result);

		$sql = sprintf_escape("SELECT c.class_id, c.class_name, CONCAT(t.last_name, ', ', t.first_name) AS teacher_name FROM `".TABLE_PREFIX."classes-teachers` as ct
			INNER JOIN classes AS c USING(class_id)
			INNER JOIN teachers AS t USING(teacher_id)
			WHERE `class-teacher_id`=%u OR `class-teacher_id`=%u LIMIT 2", $row['class-teacher_1'], $row['class-teacher_2']);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
		while($row = mysql_fetch_assoc($result)) {
			if(empty($row['class_name'])) {
				$row['class_name'] = 'Open Hour';
			}
			preg_match('/^t([0-9])+/i', $hour, $matches);
			$term = $matches[1];
			preg_match('/_h([0-9])+$/i', $hour, $matches);
			$real_hour = $matches[1];

			$output[(int)$row['class_id']] = array('name' => $row['class_name'], 'teacher' => $row['teacher_name'], 'term' => (int)$term, 'hour' => (int)$real_hour);
		}
	}
	if(empty($output)) $output = false;
	return $output;

}

?>