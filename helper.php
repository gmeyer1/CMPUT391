<?php
/*
 * CMPUT 391 Project Group 6
 * Written by Glenn Meyer and Dylan Stankievech
 * November 26, 2014 * 
 * 
 */

function redirect($url, $statusCode = 303)
{
   // Will redirect the user to the specified location
   header('Location: ' . $url, true, $statusCode);
   
   // die incase the user's browser chooses not to follow the redirect
   die();
}

function connect(){
    // Connect to the database
    $conn = oci_connect('gmeyer1', 'winteriscoming9');
    if (!$conn) {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }

    // Return the connection to database
    return $conn;
}
?>