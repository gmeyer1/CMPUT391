<?php
include_once ("helper.php"); 
$message = "";
$images = "";
$php_self = $_SERVER['PHP_SELF'];
session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}
$user = $_SESSION['username'];

if(!empty($_POST) && isset($_POST['submitSearch'])) {
    // The user submitted information
    $keywords = $_POST['keywords'];
    $key_array = explode(' ', $keywords);
    $users = $_POST['users'];
    $user_array = explode(' ', $users);
    $after = $_POST['after'];
    $after = str_replace('-', '/', $after);
    $before = $_POST['before'];
    $before = str_replace('-', '/', $before);
    $showYear = $_POST['showYear'];
    $showMonth = $_POST['showMonth'];
    $showWeek = $_POST['showWeek'];
    $showUsers = $_POST['showUsers'];
    $showSubjects = $_POST['showSubjects'];
    
    $sql = 'SELECT';
    
    $check = 0;
    
    if ($showUsers) {
        $sql .= ' owner_name';
        $check = 1;
    }
    
    if ($showSubjects) {
        if ($check == 0) {
            $sql .= ' subject';
            $check = 1;
        }
        else {
            $sql .= ', subject';
        }
    }
    
    if ($showYear) {
        if ($check == 0) {
            $sql .= ' EXTRACT(YEAR FROM timing) year';
            $check = 1;
        }
        else {
            $sql .= ', EXTRACT(YEAR FROM timing) year';
        }
    }
    
    if ($showMonth) {
        if ($check == 0) {
            $sql .= ' EXTRACT(MONTH FROM timing) month';
            $check = 1;
        }
        else {
            $sql .= ', EXTRACT(MONTH FROM timing) month';
        }
    }
    
    if ($showWeek) {
        if ($check == 0) {
            $sql .= ' TO_CHAR(timing,\'WW\') week';
            $check = 1;
        }
        else {
            $sql .= ', TO_CHAR(timing,\'WW\') week';
        }
    }
    
    if ($check == 0) {
        $sql .= ' COUNT(*) count FROM images';
    }
    else {
       $sql .= ', COUNT(*) count FROM images';
    }
    
    $check = 0;
    
    if ($users != '') {
        $contains = '\''.$user_array[0].'\'';
        
        foreach ($user_array as $owner) {
            if ($user_array[0] != $owner) {
                $contains = $contains.', \''.$owner.'\'';                
            }
        }
        
        $sql .= ' WHERE owner_name in ('.$contains.')';
        $check = 1;
    }
    
    if ($keywords != '') {
        $contains = $key_array[0];
        
        foreach ($key_array as $key) {
            if ($key_array[0] != $key) {
                $contains = $contains.' | '.$key;
            }
        }
        
        if ($check == 0) {
            $sql .= ' WHERE CONTAINS (subject, \''.$contains.'\', 1) > 0';
            $check = 0;
        }
        else {
            $sql .= ' AND CONTAINS (subject, \''.$contains.'\', 1) > 0';
        }
    }
    
    if ($after != '' && $before != '') {
        if ($check == 0) {
            $sql .= ' WHERE timing BETWEEN TO_DATE(\''.before.'\', \'yyyy/mm/dd\') AND TO_DATE(\''.after.'\', \'yyyy/mm/dd\')';
            $check = 1;
        }
        else {
            $sql .= ' AND timing BETWEEN TO_DATE(\''.before.'\', \'yyyy/mm/dd\') AND TO_DATE(\''.after.'\', \'yyyy/mm/dd\')';
        }
    }
    
    //SHOULD WE ADD ORDER BY? IF SO, ADD HERE
    
    $check = 0;
    
    if ($showUsers) {
        $sql .= ' GROUP BY owner_name';
        $check = 1;
    }
    
    if ($showSubjects) {
        if ($check == 0) {
            $sql .= ' GROUP BY subject';
            $check = 1;
        }
        else {
            $sql .= ', subject';
        }
    }
    
    if ($showYear) {
        if ($check == 0) {
            $sql .= ' GROUP BY year';
            $check = 1;
        }
        else {
            $sql .= ', year';
        }
    }
    
    if ($showMonth) {
        if ($check == 0) {
            $sql .= ' GROUP BY month';
            $check = 1;
        }
        else {
            $sql .= ', month';
        }
    }
    
    if ($showWeek) {
        if ($check == 0) {
            $sql .= ' GROUP BY week';
            $check = 1;
        }
        else {
            $sql .= ', week';
        }
    }
    
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    
    
    oci_free_statement($stid);
    oci_close($conn);
}

?>