<?PHP
/*
 * CMPUT 391 Project Group 6
 * Written by Glenn Meyer and Dylan Stankievech
 * November 26, 2014 * 
 * 
 */
require_once("helper.php");

$message = "";
$php_self = $_SERVER['PHP_SELF'];
$valid = true;
$success = false;

if(!empty($_POST) && isset($_POST['submitRegister'])) {
    // If the user submitted registration
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $date = date('d.M.y');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password) || empty($address) || empty($phone)) {
        // If any fields are empty, invalid registration
        $valid = false;
        $message = "Fields with a (*) are required";
    }

    // Connect to database
    $conn=connect();
    if (!$conn) {
        $e = oci_error();
        $valid = false;
        $message = $e;
    }
    else if ($valid) {
        // Check if user and email are unique
        $sql = 'SELECT user_name FROM persons WHERE user_name = \'' . $username . '\' or email = \'' . $email . '\'';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
        if ($row) {
            $message = "Username or email already in use";
            $valid = false;
        }

        oci_free_statement($stid);
    }

    if ($valid) {
        // If the user submitted a valid registration, add account
        // SQL command to add user to users table
        $sql = 'INSERT INTO users VALUES (\''.$username.'\',\''.$password.'\',\''.$date.'\')'; 
        
        // Prepare sql using conn and returns the statement identifier
        $stid = oci_parse($conn, $sql );

        // Execute a statement returned from oci_parse()
        $res=oci_execute($stid);

        if (!$res) {
            $err = oci_error($stid); 
            $message .= htmlentities($err['message']);
            $valid = false;
        }
        else {
            $success = true;
        }

        oci_free_statement($stid);

        // SQL command to add user info to persons table
        $sql = 'INSERT INTO persons VALUES (\''.$username.'\',\''.$firstName.'\',\''.$lastName.'\',\''.$address.'\',\''.$email.'\',\''.$phone.'\')'; 
        
        //Prepare sql using conn and returns the statement identifier
        $stid = oci_parse($conn, $sql );

        //Execute a statement returned from oci_parse()
        $res=oci_execute($stid);

        if (!$res) {
            $err = oci_error($stid); 
            $message .= htmlentities($err['message']);
            $valid = false;
        }

        // Free the statement identifier when closing the connection
        oci_free_statement($stid);

    }

    // Close database connection
    oci_close($conn);

    if($success) {
        // If the registration was successful, show success page
        redirect('success.html');
    }
    else {
        // If there was a problem registering
        $message = "Problem registering";
    }
}
else {
    $message = "Fill in the following information to register";
}
?>
<html>
<head>
<title>Register</title>
</head>

<body>
    
<input type=button onClick="parent.location='login.php'" value='Back'>

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


<input type='submit' name='submitRegister' value='Register' />
 
</form>

</body>
</html>