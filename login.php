<?php
/*
 * CMPUT 391 Project Group 6
 * Written by Glenn Meyer and Dylan Stankievech
 * November 26, 2014 * 
 * 
 */
include_once ("helper.php"); 
$wrongLogin = true;
$message = "If you have registered, login using the form below";
$php_self = $_SERVER['PHP_SELF'];

if(!empty($_POST) && isset($_POST['submitLogin'])) {
    // The user submitted information
    $user = $_POST['userid'];
    $pass = $_POST['password'];

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // establish connection
    $conn=connect();
    if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    // SQL command to check if valid login
    $sql = 'SELECT * FROM users u WHERE u.user_name = \'' . $user . '\' and u.password = \'' . $pass . '\''; 

    // Prepare sql using conn and returns the statement identifier
    $stid = oci_parse($conn, $sql );

    // Execute a statement returned from oci_parse()
    $res=oci_execute($stid);

    if (!$res) {
        // If there was some problem executing the statement?
        $message = "Invalid username or password";
    }
    else if ($row = oci_fetch_array($stid, OCI_ASSOC)) {
        // If there was a row matching, user supplied correct login
        $wrongLogin = false;
    }

    if ($wrongLogin) {
        // If user supplied incorrect login
        session_start();
        session_regenerate_id();
        session_destroy();
        $message = "Invalid username or password";
    }
    else {
        // Else user supplied valid login, so start a session
        session_start();
        session_regenerate_id();
        $_SESSION['username'] = $user;

        // Free the statement identifier when closing the connection
        oci_free_statement($stid);
        oci_close($conn);

        // Send user to home page after starting session
        redirect('home.php');
    }

    // Free the statement identifier when closing the connection
    oci_free_statement($stid);
    oci_close($conn);	
}

if(!empty($_POST) && isset($_POST['logout'])) {
    // Clears all session information after logout
    session_start();
    session_regenerate_id();
    session_destroy();
    $message = 'Successfully logged out';
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
<td><b><i>Username:</i></b></td>
<td><input type="text" name="userid" value="" autofocus ><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>Password:</i></b></td>
<td><input type="password" name="password" value=""></td>
</tr>

</table>

<input type="submit" name="submitLogin" value="Login">

</form>

<input type=button onClick="parent.location='register.php'" value='Register'>

<input type=button onClick="parent.location='https://github.com/gmeyer1/CMPUT391/wiki/User-Documentation'" value='Help'>

</body>
</html>


