<?php
include_once ("helper.php"); 
$wrongLogin = true;
$message = "If you have registered, login using the form below";
$php_self = $_SERVER['PHP_SELF'];

if(!empty($_POST) && isset($_POST['submitLogin'])) {
	$user = $_POST['userid'];
	$pass = $_POST['password'];
        
        
        
        
        
	//echo 'User: ' . $user . ' Pass: ' . $pass;
	
	//Check database
	//if (invalid login) {
	//	$wrongLogin = true;
	//}
	
	if ($user == 'user2') {
		$wrongLogin = false;
	}	
	
	if ($wrongLogin) {
		$message = "Invalid username or password";
	}
	else {
		//start session, need to give a cookie?
		redirect('home.php');
	}
		
}


if(!empty($_POST) && isset($_POST['logout'])) {
	$message = 'Successfully logged out';
	
	//delete any session information? ie delete cookie??
	
}


?>


<html>
<head>
<title>Login</title>
</head>

<body>
<h1><center>Login</center></h1>

<form name="LoginForm" action="<?php echo $php_self?>" method="post" >

<p><?php echo $message ?></p>
<table>
<tr valign=top align=left>
<td><b><i>Userid:</i></b></td>
<td><input type="text" name="userid" value="user" autofocus ><br></td>
</tr>
<tr valign=top align=left>
<td><b><i>Password:</i></b></td>
<td><input type="password" name="password" value="pass"></td>
</tr>
</table>
<input type="submit" name="submitLogin" value="Login">
</form>

<hr>

<input type=button onClick="parent.location='register.php'" value='Register'>
