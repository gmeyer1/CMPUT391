<?PHP
require_once("helper.php");

$message = 'Fill in the following information to register';
$registered = true;
$php_self = $_SERVER['PHP_SELF'];

if(!empty($_POST) && isset($_POST['submitRegister'])) {

	$firstName = $_POST['firstName'];
	$lastName = $_POST['lastName'];
	$email = $_POST['email'];
	$username = $_POST['username'];
	$password = $_POST['password'];
	$address = $_POST['address'];
	$phone = $_POST['phone'];
	

	//Check if properly registered
	//what are the restrictions on input?
	//can any be left blank?
	//check for valid input of every field?
	//sanitize for possible sql injection, etc?
	
	//if (incorrect registration) {
	//	$registered = false;
	//}
	//else {
	//	insert new row into database
	//}


	if($registered) {
		redirect('login.php');
	}
	else {
		//More useful message?
		$message = "Invalid information";
	}
}


?>


<html>
<head>
<title>Register</title>
</head>

<body>
<h1><center>Register</center></h1>


<form id='register' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>
	
<p><?php echo $message ?></p>
	
<table>
	<tr valign=top align=left>
		<td>
			<b><i>First Name*: </i></b></td>
		<td>
			<input type='text' name='firstName' id='firstName' maxlength="24" /><br>
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Last Name*: </i></b></td>
		<td>
			<input type='text' name='lastName' id='lastName' maxlength="24" /><br>
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Email*: </i></b></td>
		<td>
			<input type='text' name='email' id='email' maxlength="128" /><br>
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>UserName*:</i></b></td>
		<td>
			<input type='text' name='username' id='username' maxlength="24" />
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Password*:</i></b></td>
		<td>
			<input type='password' name='password' id='password' maxlength="24" />
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Address*:</i></b></td>
		<td>
			<input type='text' name='address' id='address' maxlength="128" />
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Phone*:</i></b></td>
		<td>
			<input type='text' name='phone' id='phone' maxlength="10" />
		</td>
	</tr>
	</table>


<input type='submit' name='submitRegister' value='Submit' />
 
</form>

</body>
</html>