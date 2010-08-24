<?php
/*
	Schedule Comparison Tool 0.1
	Copyright (C) 2010 Erik Swan
	
	Use without permission is strictly prohibited
*/

error_reporting(E_ALL & ~E_NOTICE & ~8192);

require_once('includes/overhead.php');


// ~~~~~~~~~~~~~~~~~~~~ HOME PAGE ~~~~~~~~~~~~~~~~~~~~ //
if($request_uri == '') {
	$_SESSION['page'] = 'home';
	display_header("Welcome!");
	?>
<p>Welcome to the EPHS Schedule Compare Tool!</p>
<p><strong>You can get started by <a href="/register/">Registering</a>, or <a href="/login/">Logging in</a> if you are already registered.</strong></p>
<p>This is a tool I (Erik Swan) built to help anyone at the high school compare
their schedules with friends. No longer do you have to post your schedule in a note,
tag your friends, and then go searching their profile pages for their schedule or wait
for them to respond. This tool allows anyone to sign up, submit their schedule through an easy-to-use
interface, and then instantly see whether anyone else who has registered shares classes with them.</p>
<p>In addition, by clicking on the name of anyone on the site, you will be taken to their schedule page,
where, if the user allows, you can view their entire schedule as well as see an assembled list of the classes
you share with that person.</p>
<p>Despite the long development time, this is still a very early version of this tool, and there may be various bugs or quirks.<br/>
If you find anything that needs fixing, please do not hesitate to <a href="/contact/">let me know</a>.<br/>
Any kind of feedback, positive or negative, is always welcome. Thanks.</p>
    <?php
	display_footer();

// ~~~~~~~~~~~~~~~~~~~~ CONTACT PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['contact'], $request_uri, $url_structure)) {
	$a = isset($_POST['submit']);
	$l = $_SESSION['logged_in'];
	if($a or $l) $e = true;
	$_SESSION['page'] = 'contact';
	if($l) {
		require_once(MYSQL);
		$sql = sprintf_escape("SELECT user_id, email, first_name, last_name FROM ".TABLE_PREFIX."users WHERE user_id='%u' LIMIT 1", $_SESSION['user_id']);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
		$row = mysql_fetch_assoc($result);
		$name = $row['first_name'].' '.$row['last_name'];
		$email = $row['email'];

	}
	if($a) {
		$name = $_POST['name'];
		$email = $_POST['email'];
		$comments = $_POST['comments'];
		$errors = array();

		$validation = true;
		if(empty($name)) {
			$errors[] = "You must enter your name.";
			$validation = false;
		}
		if(preg_match('/^[A-Za-z ]+$/', $name) === 0) {
			$errors[] = "Your name is invalid.";
			$validation = false;
		}
		if(empty($comments)) {
			$errors[] = "You must enter a message.";
			$validation = false;
		}
		if(strlen($comments) > 10000) {
			$errors[] = "Your message is too long.";
			$validation = false;
		}
		if(empty($email) or preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $email) === 0) {
			$errors[] = "You must enter a valid email address.";
			$validation = false;
		}
		if($validation) {
			$to = "eriksrocks@gmail.com";
			$subject = "Contact form from Schedule Tool";
			$headers = "From: \"".$name."\" <".$email.">";
			mail($to, $subject, $comments, $headers);
			display_header("Message sent", null, 'home');
			?>
			<p>Your message has been sent.</p>
			<p class="low_key"><a href="/<?php echo $redirect; ?>">Click here</a> if your browser does not redirect you automatically.</p>
			<?php
			display_footer();
		} else $display_form = true;
	} else $display_form = true;
	if($display_form) {
		display_header("Contact");
		?>
		<p>You may contact me using the form below. Fields marked with <em class="red">*</em> are required.</p>
		<?php
		if(!empty($errors)) {
			echo "\n<ul class=\"error\">\n";
			foreach($errors as $error) {
				echo "<li>".$error."</li>\n";
			}
			echo "</ul>";
		}
		?>
		<form action="/contact/" method="post" name="contact" id="contact">
		<label for="name"><em>*</em> Name:</label>
		<input name="name" id="name" type="text" <?php if($e) echo 'value="'.$name.'"'; ?> size="30" /><br />
		<label for="email"><em>*</em> Email:</label>
		<input name="email" id="email" type="text" <?php if($e) echo 'value="'.$email.'"'; ?> size="30" /><br />
		<label for="comments"><em>*</em> Comments:</label><br />
		<textarea name="comments" cols="50" rows="6"><?php if($a) echo $comments; ?></textarea>
		<p><input name="submit" id="submit" type="submit" value="Submit" /></p>
	    <?php
	}
	display_footer();

// ~~~~~~~~~~~~~~~~~~~~ HOME PAGE REDIRECT ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['home'], $request_uri, $url_structure) || $request_uri == 'index.php' ) {
	header("HTTP/1.1 301 Moved Permanently");
	header('Location: '.BASE);
	exit;

// ~~~~~~~~~~~~~~~~~~~~ REGISTER PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['register'], $request_uri, $url_structure)) {
	$_SESSION['page'] = 'register';
	display_header("Register");

	if(isset($_POST['submit'])) {
		// form submitted, let's validate it
		$validation = true; // Logic is much easier this way
		$errors = array();
		if(empty($_POST['first_name']) || preg_match("/^[a-zA-z'\-]{2,}$/", $_POST['first_name']) === 0 || empty($_POST['last_name']) || preg_match("/^[a-zA-z'\-]{2,}$/", $_POST['last_name']) === 0) {
			$errors[] = 'You must enter a valid first and last name.';
			$validation = false;
		} else {
			$first_name = $_POST['first_name'];
			$last_name = $_POST['last_name'];
		}
		if(empty($_POST['email']) || preg_match("/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i", $_POST['email']) === 0) {
			$errors[] = 'You must enter a valid email address.';
			$validation = false;
		} else {
			$email = $_POST['email'];
		}
		if(!($_POST['grade'] >= 9 AND $_POST['grade'] <= 12)) {
			$errors[] = 'You must enter your grade.';
			$validation = false;
		} else {
			$grade = $_POST['grade'];
		}
		if(empty($_POST['whole_schedule_perm']) && $_POST['whole_schedule_perm'] !== '0') {
			$errors[] = 'You must choose whether you want to allow members to view your entire schedule or not.';
			$validation = false;
		} else {
			$whole_schedule_perm = $_POST['whole_schedule_perm'];
		}
		if(empty($_POST['password'])) {
			$errors[] = 'You must enter a password.';
			$validation = false;
		} else {
			if(empty($_POST['confirm_password']) || $_POST['password'] != $_POST['confirm_password']) {
				$errors[] = 'The passwords do not match.';
				$validation = false;
			} else {
				$password = $_POST['password'];
			}
		}
		if(!empty($_POST['fb_url'])) {
			preg_match('/:\/\/(.[^\/]+)/i', str_replace('www.', '', $_POST['fb_url']), $temp_matches);
			if($temp_matches[1] != 'facebook.com') {
				$errors[] = 'The URL you provided is not a valid Facebook URL.';
				$validation = false;
			} else {
				$fb_url = $_POST['fb_url'];
			}
		}

		require_once(MYSQL);
		
		$sql = sprintf_escape("SELECT user_id FROM ".TABLE_PREFIX."users WHERE email='%s'", $email);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

			if(mysql_fetch_row($result) !== false) {
				// this email address is already registered
				$errors[] = 'This email address is already registered. If you believe this is an error, please <a href="/contact/">contact me</a>.';
				$validation = false;
			}

		if($validation) {
			// connect to database and add new user, then display success message.
			$sql = sprintf_escape("INSERT INTO `".$config['dbname']."`.`".TABLE_PREFIX.
			"users` (`first_name`, last_name, email, `pass`, `fb_url`, whole_schedule_perm, grade, `registration_date`,`ip_address`) VALUES ('%s', '%s', '%s', '%s', '%s', %u, %u, NOW(), INET_ATON('%s'));",
			$first_name, $last_name, $email, myhash($password), urlencode($fb_url), intval($whole_schedule_perm), $grade, $_SERVER['REMOTE_ADDR']);

			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			if(mysql_affected_rows() === 1) {
				?>
                <p>You have successfully registered. <a href="/login/">Click here</a> to login.</p>
                <?php
			} else {
				?>
                <p>Something went wrong during registration. Please contact me.</p>
                <?php
			}
			
		} else display_registration_form($errors);
	} else display_registration_form();
	
	display_footer();
	
// ~~~~~~~~~~~~~~~~~~~~ LOGIN PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['login'], $request_uri, $url_structure)) {
	if(!empty($_POST['redirect'])) {
		$redirect = $_POST['redirect'];
	} else $redirect = 'schedule/';

	if(!logged_in(null, null, true)) {
		$_SESSION['page'] = 'login';
		if(isset($_POST['submit'])) {

			//login attempt
			$validation = true;

			if(empty($_POST['email'])) {
				$errors[] = 'You must enter your email address.';
				$validation = false;
			} else $email = $_POST['email'];
			if(empty($_POST['password'])) {
				$errors[] = 'You must enter a password.';
				$validation = false;
			} else $password = $_POST['password'];

			if($validation) {
				require_once(MYSQL);
				$sql = sprintf_escape("SELECT user_id, email, first_name, pass FROM ".TABLE_PREFIX."users WHERE email='%s' AND pass='%s' LIMIT 1", $email, myhash($password));
				$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
				if(mysql_num_rows($result) === 1) {
					$row = mysql_fetch_assoc($result);
					session_regenerate_id(); // security
					$_SESSION['logged_in'] = true;
					$_SESSION['user_id'] = $row['user_id'];
					$_SESSION['first_name'] = $row['first_name'];
					$_SESSION['email'] = $row['email'];
					$_SESSION['user-agent'] = md5(md5($_SERVER['HTTP_USER_AGENT']).session_id().SALT);
					if($_POST['rememberme'] === '1') {
						$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $config['basedomain'] : false;
						setcookie("sched_user", $row['user_id'].','.$row['pass'].','.md5(md5($_SERVER['HTTP_USER_AGENT']).SALT), time()+60*60*24*100, '/', $domain, 0, 0);
					}
					display_header("Welcome ".$row['first_name'].'!', null, $redirect);
					?>
					<p>You have been successfully logged in!</p>
					<p class="low_key"><a href="/<?php echo $redirect; ?>">Click here</a> if your browser does not redirect you automatically.</p>
					<?php
				} else {
					display_header("Login");
					$errors[] = 'The username and password do not match.';
					display_login_form($errors, $redirect);
				}
			} else {
				display_header("Login");
				display_login_form($errors, $redirect);
			}
		} else {
			display_header("Login");
			display_login_form();
		}
	display_footer();
	} else {
		header('Location: '.BASE.$redirect);
	}

// ~~~~~~~~~~~~~~~~~~~~ LOGOUT PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['logout'], $request_uri, $url_structure)) {

	if(logged_in(null, null, true)) {
		//user is logged in
		$_SESSION['page'] = 'logout';
		$_SESSION['logged_in'] = false;
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
		session_destroy();
		$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $config['basedomain'] : false;
		setcookie('sched_user', '', time()-42000, '/', $domain, 0, 0);
		display_header("Logged Out", null, 'home');
		?>
		<p>You have been successfully logged out.</p>
		<p class="low_key"><a href="/">Click here</a> if your browser does not redirect you automatically.</p>
		<?php
		display_footer();
	} else {
		header("Location: ".BASE."login/");
	}
	
// ~~~~~~~~~~~~~~~~~~~~ ACCOUNT PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['account'], $request_uri, $url_structure)) {
	$_SESSION['page'] = 'account';

	logged_in('Sorry!', 'You have to be logged in to edit your account.');


	require_once(MYSQL);
	$sql = sprintf_escape("SELECT user_id, email, first_name, last_name, grade, fb_url, pass, whole_schedule_perm FROM ".TABLE_PREFIX."users WHERE user_id='%u';", $_SESSION['user_id']);
	$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
	$user_data = mysql_fetch_assoc($result);
	$password = $user_data['pass'];
	$fb_url = urldecode($user_data['fb_url']);
	$grade = $user_data['grade'];
	$first_name = $user_data['first_name'];
	$last_name = $user_data['last_name'];
	$email = $user_data['email'];
	$whole_schedule_perm = $user_data['whole_schedule_perm'];

	if(isset($_POST['submit'])) {
		// form submitted
		display_header("My Account");
		$validation = true;
		$errors = array();
		if(empty($_POST['current_password']) && !empty($_POST['password'])) {
			$errors[] = 'You must enter your current password if you want to change your password.';
			$validation = false;
		}
		if(empty($_POST['password']) && !empty($_POST['current_password']) ) {
			$errors[] = 'You must enter a new password if you want to change your password.';
			$validation = false;
		} else {
			if($_POST['password'] != $_POST['confirm_password']) {
				$errors[] = 'The passwords do not match.';
				$validation = false;
			} else {
				$current_password = $_POST['current_password'];
				$password = empty($_POST['password']) ? $password : myhash($_POST['password']);
			}
		}
		if(!empty($_POST['fb_url'])) {
			preg_match('/:\/\/(.[^\/]+)/i', str_replace('www.', '', $_POST['fb_url']), $temp_matches);
			if($temp_matches[1] != 'facebook.com') {
				$errors[] = 'You did not enter a valid Facebook URL.';
				$validation = false;
			} else {
				$fb_url = $_POST['fb_url'];
			}
		} else {
				$fb_url = $_POST['fb_url'];
			}
		if(!($_POST['grade'] >= 9 AND $_POST['grade'] <= 12)) {
			$errors[] = 'You must enter your grade.';
			$validation = false;
		} else {
			$grade = $_POST['grade'];
		}
		if(empty($_POST['whole_schedule_perm']) && $_POST['whole_schedule_perm'] !== '0') {
			$errors[] = 'You must choose whether you want to allow members to view your entire schedule or not.';
			$validation = false;
		} else {
			$whole_schedule_perm = $_POST['whole_schedule_perm'];
		}
		if($validation) {
			$sql = sprintf_escape("SELECT user_id, email, pass FROM ".TABLE_PREFIX."users WHERE email='%s' AND pass='%s';", $email, myhash($current_password));
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			if(mysql_num_rows($result) !== 1 && !empty($_POST['current_password'])) {
				$errors[] = 'The current password is wrong. Unable to change password.';
				$display_form = true;
			} else {
				$sql = sprintf_escape("UPDATE ".TABLE_PREFIX."users SET pass='%s', fb_url='%s', grade=%u, whole_schedule_perm=%u WHERE user_id='%u' LIMIT 1", $password, $fb_url, $grade, $whole_schedule_perm, $_SESSION['user_id']);
				$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
				?>
	    <p class="info_notice fadeout">User data updated.<a href="#" class="js closebutton imagelink" ><img src="/images/x.png" /></a></p>
	    <?php
				if(isset($_COOKIE['sched_user'])) {
					// user is using 'remember me' feature
					// update cookie with new information so that it is still valid
					$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $config['basedomain'] : false;
					setcookie("sched_user", $_SESSION['user_id'].','.$password.','.md5(md5($_SERVER['HTTP_USER_AGENT']).SALT), time()+60*60*24*100, '/', $domain, 0, 0);
				}
				$display_form = true;
			}
		} else $display_form = true;
			
	} else if($url_structure[1] == 'delete') {
		if($url_structure[2] == 'confirm') {
			// got confirmation, delete account
			$sql = sprintf_escape("DELETE FROM ".TABLE_PREFIX."users WHERE user_id='%u' LIMIT 1", $_SESSION['user_id']);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			$num = mysql_affected_rows();
			$sql = sprintf_escape("DELETE FROM ".TABLE_PREFIX."schedules WHERE user_id='%u' LIMIT 1", $_SESSION['user_id']);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			if($num + mysql_affected_rows() == 2) {
				$_SESSION['logged_in'] = false;
				display_header("Account Deleted");
			?>
			<p>Account deleted. Please click <a href="/">here</a> to return to the main page.</p>
			<?php
			} else {
				display_header("Uh...");
			?>
			<p>Something went wrong. Your account might still exist.</p>
			<?php
			}
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
			);
			session_destroy();
			$domain = ($_SERVER['HTTP_HOST'] != 'localhost') ? $config['basedomain'] : false;
			setcookie('sched_user', '', time()-42000, '/', $domain, 0, 0); // destroy cookie
		} else {
			display_header("Delete Account");
			?>
			<p>Are you sure you want to delete your account? <strong>This is permanent.</strong></p>
			<p><a href="/account/delete/confirm" class="button special">Yeah, nuke it!</a><a href="/account/" class="button">No, not my precious account!</a></p>
			<?php
		}
			
	} else {
		display_header("My Account");
		$display_form = true;
	}
	if($display_form) {
		?>
        <p>This is your account control panel. You may change your password as well as delete your account here.</p>
	<?php if(!empty($errors)) {
	    echo "\n<ul class=\"error\">\n";
	    foreach($errors as $error) {
		    echo "<li>".$error."</li>\n";
	    }
	    echo "</ul>";
    } ?>
        <form action="/account" method="post" name="account" id="account">
    <label for="first_name"><em>*</em> First name:</label>
    <input name="first_name" id="first_name" type="text" value="<?php echo $first_name; ?>" disabled="disabled" size="30" />
    <p class="low_key">(You are not allowed to change your name.<br />Contact me if you need to change it.)</p>
    <label for="last_name"><em>*</em> Last name:</label>
    <input name="last_name" id="last_name" type="text" value="<?php echo $last_name; ?>" disabled="disabled" size="30" />
    <br />
    <label for="email"><em>*</em> Email:</label>
    <input name="email" id="email" type="text" value="<?php echo $email; ?>" disabled="disabled" size="30" />
    <p class="low_key">(You are not allowed to change your email address.<br />Contact me if you need to change it.)</p>
    <label for="grade"><em>*</em> Grade:</label>
    <select name="grade" id="grade">
	    <option value="9" <?php if($grade == 9) echo 'selected="selected"'; ?>>9 / Freshman</option>
	    <option value="10" <?php if($grade == 10) echo 'selected="selected"'; ?>>10 / Sophomore</option>
	    <option value="11" <?php if($grade == 11) echo 'selected="selected"'; ?>>11 / Junior</option>
	    <option value="12" <?php if($grade == 12) echo 'selected="selected"'; ?>>12 / Senior</option>
    </select>
    <p class="low_key">(This will allow us to do some cool filtering and matching. Please be honest.)</p>
    <label for="current_password"><em>*</em> Current Password:</label>
    <input name="current_password" id="current_password" type="password" size="30" />
    <p class="low_key">(You must correctly enter your current password to change it.)</p>
    <label for="password"><em>*</em> New Password:</label>
    <input name="password" id="password" type="password" size="30" />
    <p class="low_key">(Try to make it secure.)</p>
    <label for="confirm_password"><em>*</em> Confirm New Password:</label>
    <input name="confirm_password" id="confirm_password" type="password" size="30" />
    <br />
    <label for="fb_url">Facebook URL:</label>
    <input name="fb_url" id="fb_url" type="text" value="<?php echo $fb_url; ?>" size="50" />
    <p class="low_key">(For schweet linkage.)</p>
    <p><input name="whole_schedule_perm" type="radio" value="1" <?php if($whole_schedule_perm === '1') echo 'checked="checked"'; ?> /> Yes, I want anyone who registers on the site to be able to view my entire schedule.<br />
	    <input name="whole_schedule_perm" type="radio" value="0" <?php if($whole_schedule_perm === '0') echo 'checked="checked"'; ?> /> No, I want to keep my whole schedule private. Other members will only be able to see which specific classes they have with me.
    </p>
    <p><input name="submit" class="submit" type="submit" value="Update" /></p>
    <p>If you choose to delete your account, all of your personal information and your schedule will be removed from the site. Your name will disappear from other people's schedules.
    <strong>This will permanently delete your account. You cannot undo this. Once your account is gone, it's gone.</strong><br />
    </p>
    <p><a href="/account/delete" class="button special">Delete Account</a></p>
    <p></p>
    </form>
    <?php
	}
	display_footer();
	
// ~~~~~~~~~~~~~~~~~~~~ MEMBERS PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['members'], $request_uri, $url_structure)) {
	$_SESSION['page'] = 'members';
	require_once(MYSQL);

	if(isset($url_structure[1]) && preg_match('/([a-zA-z]+-[a-zA-z]+\.[0-9]+)|([0-9]+)/i', $url_structure[1])) {
		logged_in('Sorry!', 'You have to be registered and logged in to view a member\'s schedule/profile.');
		// we're displaying a member's profile page
		$explode_uri = explode('.',$url_structure[1]);
		$get_name = explode('-',$explode_uri[0]);
		$get_user_id = $explode_uri[1];
		$sql = sprintf_escape("SELECT user_id, first_name, last_name, registration_date, fb_url, whole_schedule_perm FROM ".TABLE_PREFIX."users WHERE user_id=%u LIMIT 1", $get_user_id);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

		if(mysql_num_rows($result) === 0) {
			// this user does not exist
			display_header("Error");
			?>
			<p>This user does not exist.</p>
			<?php
		} else {

			$row = mysql_fetch_assoc($result);
			if($get_name[0] != $row['first_name'] || $get_name[1] != $row['last_name']) {
				// the user(id) exists, but the name is wrong or falsified. 301 to the correct URL
				header("HTTP/1.1 301 Moved Permanently");
				header('Location: /members/'.$row['first_name'].'-'.$row['last_name'].'.'.$row['user_id']);
				exit;
			}

			$h2_addition = '';
			if(!empty($row['fb_url'])) {
				$h2_addition = ' &nbsp;<a href="'.urldecode($row['fb_url']).'"  class="imagelink"><img src="/images/facebook-icon-large.png" /></a>';
			}
			display_header($row['first_name'].' '.$row['last_name'], $h2_addition);
			$sql = sprintf_escape("SELECT * FROM ".TABLE_PREFIX."schedules WHERE user_id=%u LIMIT 1", $get_user_id);
			$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
			if(mysql_num_rows($result) === 1) {
				if($row['whole_schedule_perm']) {
					?>
					<p><?php echo $row['first_name'];?>'s schedule:</p>
					<?php
					print_schedule(true, $get_user_id);
				} else {
				?>
				<p><?php echo $row['first_name'];?> has blocked other users from viewing his or her whole schedule.</p>
				<?php }
				if($_SESSION['user_id'] != $get_user_id) {
					?>
					<p>Your classes with <?php echo $row['first_name']; ?>:</p>
					<ul>
					<?php
					$return = schedule_compare($_SESSION['user_id'], $get_user_id);
					if($return != false) {
						foreach($return as $class) {
							?>
							<li><?php echo $class['name']; ?><br />
							<span class="low_key">with <?php echo $class['teacher']; ?> during Term <?php echo $class['term']; ?>, Hour <?php echo $class['hour']; ?></span></li>
							<?php
						}
					} else {
						?>
							<ul>
								<li>You do not have any classes with <?php echo $row['first_name']; ?>.</li>
							</ul>
						<?php
					}
					?>
					</ul>
				<?php
				}
			} else {?>
				<p><?php echo $row['first_name']; ?> has not submitted a schedule yet.</p>
				<?php
			}
		}
	} else {
	display_header("Members");

        $direction = 'ASC';
	$order_by = 'first_name '.$direction.', last_name '.$direction;
	// TODO: add support for sorting ascending/descending, first name and last name sorting
	if($url_structure[2] == 'date') $order_by = 'registration_date';
	$sql = sprintf_escape('SELECT user_id, first_name, last_name, fb_url, registration_date FROM '.TABLE_PREFIX.'users ORDER BY %s', $order_by);
	$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
	
	$num_users = mysql_num_rows($result);
	?>
    <p>There are <?php echo $num_users; ?> users registered.</p>
    <p>Sort by: <a href="/members/sort/abc">Alphabetical</a> | <a href="/members/sort/date">Date Registered</a></p>
    <table width="0" border="0">
    <thead>
  <tr>
    <th>Name</th>
    <th>Date Registered</th>
  </tr>
  </thead>
  <tbody>
  <?php
  while ($row = mysql_fetch_assoc($result)) {
	echo "\t<tr>\n";
	echo "\t\t<td><a href=\"/members/".$row['first_name'].'-'.$row['last_name'].'.'.$row['user_id'].'/">'.$row["first_name"].' '.$row['last_name']."</a>";
	if(!empty($row['fb_url'])) {
		echo " <a href=\"".urldecode($row['fb_url'])."\"  class=\"imagelink\"><img src=\"/images/facebook-icon.jpg\" /></a>";
	}
	echo "</td>\n";
    echo "\t\t<td>".date('M j \a\t g:ia', strtotime($row["registration_date"]))."</td>\n";
	echo "\t</tr>\n";
}
	}
?>
</tbody>
</table>
    <?php
	display_footer();
	
// ~~~~~~~~~~~~~~~~~~~~ (EDIT) SCHEDULE PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else if(match($patterns['schedule'], $request_uri, $url_structure)) {
	
	logged_in('Sorry!', "You have to be logged in to edit your schedule.");
	require_once(MYSQL);
	
	$errors = array();
	$database = false;
	if($url_structure[1] =='edit') {
		$_SESSION['page'] = 'schedule/edit';
		display_header("Edit My Schedule");
		if(isset($_POST['submit'])) {
			$validation = true;
			$open_count = 0; // count open hours
			//$sql = sprintf_escape("INSERT INTO ".TABLE_PREFIX."schedules (user_id, t1_h1, t1_h2, t1_h3, t1_h4, t2_h1, t2_h2, t2_h3, t2_h4, t3_h1, t3_h2, t3_h3, t3_h4, t4_h1, t4_h2, t4_h3, t4_h4) VALUES (%u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u, %u) ", $_SESSION['user_id'], $_SESSION['name']) ;
			for($i = 1; $i <= 4; $i++) {
				for($j = 1; $j <= 4; $j++) {
					$class_submit = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class']);
					$class_submit_2 = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class_2']);
					if(empty($class_submit)) {
						$open_count++;
					}
					if(!empty($class_submit) && !int_ok($class_submit)) {
						$errors[] = 'The Term '.$j.', Hour '.$i.' class ID is not an integer.';
						$validation = false;
					}
					if(!empty($class_submit_2) && !int_ok($class_submit_2)) {
						$errors[] = 'The Term '.$j.', Hour '.$i.' Skinny class ID is not an integer.';
						$validation = false;
					}
					if((empty($_POST['t'.$j.'_h'.$i.'_teacher']) || $_POST['t'.$j.'_h'.$i.'_teacher'] == 'none') && !empty($class_submit)) {
						$errors[] = 'You must input a teacher for Term '.$j.', Hour '.$i;
						$validation = false;
					}
					if((empty($_POST['t'.$j.'_h'.$i.'_teacher_2']) || $_POST['t'.$j.'_h'.$i.'_teacher_2'] == 'none') && !empty($class_submit_2)) {
						$errors[] = 'You must input a teacher for Term '.$j.', Hour '.$i." Skinny";
						$validation = false;
					}
					if($validation) { // things are looking good so far...
						${'t'.$j.'_h'.$i.'_class'} = $class_submit;
						${'t'.$j.'_h'.$i.'_teacher'} = $_POST['t'.$j.'_h'.$i.'_teacher'];
						${'t'.$j.'_h'.$i.'_class_2'} = $class_submit_2;
						${'t'.$j.'_h'.$i.'_teacher_2'} = $_POST['t'.$j.'_h'.$i.'_teacher_2'];
					}
				}
			}
			if($open_count > 8) {
				$errors[] = "You can't possibly have more than 8 open periods. Please finish entering your schedule.";
				$validation = false;
			}
			if($validation) {
				$insert_sql = sprintf_escape("INSERT INTO ".TABLE_PREFIX."schedules (user_id, t1_h1, t2_h1, t3_h1, t4_h1, t1_h2, t2_h2, t3_h2, t4_h2, t1_h3, t2_h3, t3_h3, t4_h3, t1_h4, t2_h4, t3_h4, t4_h4) VALUES(%u, ", $_SESSION['user_id']);
				for($i = 1; $i <= 4; $i++) {
					for($j = 1; $j <= 4; $j++) {
						$class_submit = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class']);
						$class_submit_2 = preg_replace('/-[0-9]*/', '', $_POST['t'.$j.'_h'.$i.'_class_2']);
						
						$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."classes-teachers` WHERE class_id=%u AND teacher_id=%u LIMIT 1", $class_submit, $_POST['t'.$j.'_h'.$i.'_teacher']);
						$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
						if(mysql_num_rows($result) === 0) {
							$sql = sprintf_escape("INSERT INTO `".TABLE_PREFIX."classes-teachers` (class_id, teacher_id) VALUES(%u, %u)", $class_submit, $_POST['t'.$j.'_h'.$i.'_teacher']);
							$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."classes-teachers` WHERE class_id=%u AND teacher_id=%u", $class_submit, $_POST['t'.$j.'_h'.$i.'_teacher']);
							$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
						}
						$row = mysql_fetch_assoc($result);

						if(!empty($class_submit_2)) {
							$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."classes-teachers` WHERE class_id=%u AND teacher_id=%u", $class_submit_2, $_POST['t'.$j.'_h'.$i.'_teacher_2']);
							$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							if(mysql_num_rows($result) === 0) {
								$sql = sprintf_escape("INSERT INTO `".TABLE_PREFIX."classes-teachers` (class_id, teacher_id) VALUES(%u, %u)", $class_submit_2, $_POST['t'.$j.'_h'.$i.'_teacher_2']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
								$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."classes-teachers` WHERE class_id=%u AND teacher_id=%u", $class_submit_2, $_POST['t'.$j.'_h'.$i.'_teacher_2']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							} 
							$row2 = mysql_fetch_assoc($result);
						} else {
							unset($row2);
						}


						if(!isset($row2)) {
							$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."class-hours` WHERE `class-teacher_1`=%u AND `class-teacher_2`=0 LIMIT 1", $row['class-teacher_id']);
							$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							
							if(mysql_num_rows($result) === 0) {
								// this combo doesn't exist yet.
								$sql = sprintf_escape("INSERT INTO `".TABLE_PREFIX."class-hours` (`class-teacher_1`, `class-teacher_2`) VALUES(%u, 0)", $row['class-teacher_id']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
								$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."class-hours` WHERE `class-teacher_1`=%u AND `class-teacher_2`=0 LIMIT 1", $row['class-teacher_id']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							}
							$row = mysql_fetch_assoc($result);
						} else {
							$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."class-hours` WHERE `class-teacher_1`=%u AND `class-teacher_2`=%u LIMIT 1", $row['class-teacher_id'], $row2['class-teacher_id']);
							$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							if(mysql_num_rows($result) === 0) {
								// this combo doesn't exist yet.
								$sql = sprintf_escape("INSERT INTO `".TABLE_PREFIX."class-hours` (`class-teacher_1`, `class-teacher_2`) VALUES(%u, %u)", $row['class-teacher_id'], $row2['class-teacher_id']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
								$sql = sprintf_escape("SELECT * FROM `".TABLE_PREFIX."class-hours` WHERE `class-teacher_1`=%u AND `class-teacher_2`=%u LIMIT 1", $row['class-teacher_id'], $row2['class-teacher_id']);
								$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
							}
							$row = mysql_fetch_assoc($result);
						}

						if($i == 4 && $j == 4) {
							$insert_sql .= sprintf_escape("%u", $row['combo_id']);
						} else {
							$insert_sql .= sprintf_escape("%u, ", $row['combo_id']);
						}

					}
				}
				$insert_sql .= ") ON DUPLICATE KEY UPDATE t1_h1=VALUES(t1_h1), t1_h2=VALUES(t1_h2), t1_h3=VALUES(t1_h3), t1_h4=VALUES(t1_h4), t2_h1=VALUES(t2_h1), t2_h2=VALUES(t2_h2), t2_h3=VALUES(t2_h3), t2_h4=VALUES(t2_h4), t3_h1=VALUES(t3_h1), t3_h2=VALUES(t3_h2), t3_h3=VALUES(t3_h3), t3_h4=VALUES(t3_h4), t4_h1=VALUES(t4_h1), t4_h2=VALUES(t4_h2), t4_h3=VALUES(t4_h3), t4_h4=VALUES(t4_h4)";
				
				$sql = $insert_sql;
				$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);

				$_SESSION['notice'] = "Schedule updated.";
				header("Location: ".BASE."schedule/");
				exit();
			} else $display_form = true;
		} else {
			$display_form = true;
			$database = true;
		}
	} else { // at /schedule/
		$_SESSION['page'] = 'schedule';

		display_header("My Schedule");
		if(!empty($_SESSION['notice'])) {
			?>
			<p class="info_notice fadeout"><?php echo $_SESSION['notice']; ?><a href="#" class="js closebutton imagelink" ><img src="/images/x.png" /></a></p>
			<?php
			$_SESSION['notice'] = '';
		}

		$sql = sprintf_escape("SELECT * FROM ".TABLE_PREFIX."schedules WHERE user_id=%u LIMIT 1", $_SESSION['user_id']);
		$result = mysql_query($sql) or trigger_error('Query failed: ' . mysql_error(), E_USER_ERROR);
		$row = mysql_fetch_assoc($result);

		if(mysql_num_rows($result) === 1) {
			?>
			<p>Below is your schedule. <a href="/schedule/edit">Click here to edit.</a></p>
			<?php
			print_schedule(true);
		} else {
			header("Location: ".BASE."schedule/edit");
			exit();
		}
	}

	if($display_form) {
		?>
		<p>Fill out your schedule by entering the ID numbers of the classes you are taking in the boxes. Then select the teacher you have for that class.
		The class names will be filled in for you automatically for confirmation.</p>
		<noscript>
			<span class="error">You currently have JavaScript disabled. Enabling JavaScript will make filling out your schedule a much more pleasant experience.</span>
		</noscript>
<!--		<div class="scheduleBox">0327-3<br />
		Spanish Level 4A<br />
		Blumreich, Kristin<br />
		Rm: 302
		</div>-->
<!--		<p>You would enter <strong>0327</strong> for the Class #.</p>-->
		<?php
		build_schedule_form($errors, $database);
		?>
		<p></p>
		<p class="faq question">Q: I tried to enter one of my classes and it said it wasn't found, or it accepted the class but the teacher I have isn't listed!</p>
		<p class="faq">A: I apologize. The class and teacher database is from last year, and as such it has entries that are outdated and is missing teachers and classes that are new this year.
		If you run into a problem entering your schedule, please <a href="/contact/">contact me</a> with the following information:</p>
		<ul>
			<li>Your name</li>
			<li>The ID number of the class in question</li>
			<li>The name of the class in question</li>
			<li>The first and last name of the teacher of this class</li>
			<li>Briefly describe the problem (is the class not found, or is the teacher not listed?)</li>
		</ul>
		<p>I will try to fix the problem ASAP. :)</p>
		<?php
	}
		
display_footer();


// ~~~~~~~~~~~~~~~~~~~~ 404 PAGE ~~~~~~~~~~~~~~~~~~~~ //
} else {
	$_SESSION['page'] = 'error';
	// invalid url, let's say the page is not found
	display_error('404');
	// exit, script done
}
	
ob_end_flush();

?>