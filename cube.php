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
if ($user != 'admin') {
    redirect('home.php');
}

//if(!empty($_POST) && isset($_POST['submitData'])) {
if(isset($_POST['submitData'])) {
    // The user submitted information
    $keywords = $_POST['keywords'];
    $key_array = explode(' ', $keywords);
    $users = $_POST['users'];
    $user_array = explode(' ', $users);
    $start = $_POST['start'];
    $start = str_replace('-', '/', $start);
    $end = $_POST['end'];
    $end = str_replace('-', '/', $end);
    $showYear = $_POST['showYear'];
    $showMonth = $_POST['showMonth'];
    $showWeek = $_POST['showWeek'];
    $showUsers = $_POST['showUsers'];
    $showSubjects = $_POST['showSubjects'];
    
    $sql = 'SELECT';
    
    $check = 0;
    
    $columns = '<tr><td><b>';
    
    if ($showUsers) {
        $sql .= ' owner_name';
        $check = 1;
        
        $columns .= 'User';
    }
    
    if ($showSubjects) {
        if ($check == 0) {
            $sql .= ' subject';
            $check = 1;
            
            $columns .= 'Subject';
        }
        else {
            $sql .= ', subject';
            
            $columns .= '</b></td><td><b>Subject';
        }
    }
    
    if ($showYear) {
        if ($check == 0) {
            $sql .= ' EXTRACT(YEAR FROM timing) year';
            $check = 1;
            
            $columns .= 'Year';
        }
        else {
            $sql .= ', EXTRACT(YEAR FROM timing) year';
            
            $columns .= '</b></td><td><b>Year';
        }
    }
    
    if ($showMonth) {
        if ($check == 0) {
            $sql .= ' EXTRACT(MONTH FROM timing) month';
            $check = 1;
            
            $columns .= 'Month';
        }
        else {
            $sql .= ', EXTRACT(MONTH FROM timing) month';
            
            $columns .= '</b></td><td><b>Month';
        }
    }
    
    if ($showWeek) {
        if ($check == 0) {
            $sql .= ' TO_CHAR(timing,\'WW\') week';
            $check = 1;
            
            $columns .= 'Week';
        }
        else {
            $sql .= ', TO_CHAR(timing,\'WW\') week';
            
            $columns .= '</b></td><td><b>Week';
        }
    }
    
    if ($check == 0) {
        $sql .= ' COUNT(*) count FROM images';
        
        $columns .= 'Count';
    }
    else {
       $sql .= ', COUNT(*) count FROM images';
       
       $columns .= '</b></td><td><b>Count';
    }
    
    $columns .= '</b></td></tr>';
    
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
        $contains = '%'.$key_array[0].'%';
        
        foreach ($key_array as $key) {
            if ($key_array[0] != $key) {
                $contains = $contains.' | %'.$key.'%';
            }
        }
        
        if ($check == 0) {
            $sql .= ' WHERE CONTAINS (subject, \''.$contains.'\', 1) > 0';
            $check = 1;
        }
        else {
            $sql .= ' AND CONTAINS (subject, \''.$contains.'\', 1) > 0';
        }
    }
    
    if ($start != '') {
        if ($check == 0) {
            $sql .= ' WHERE timing >= TO_DATE(\''.$start.'\', \'yyyy/mm/dd\')';
            $check = 1;
        }
        else {
            $sql .= ' AND timing >= TO_DATE(\''.$start.'\', \'yyyy/mm/dd\')';
        }
    }
    
    if ($end != '') {
        if ($check == 0) {
            $sql .= ' WHERE timing <= TO_DATE(\''.$end.'\', \'yyyy/mm/dd\')';
            $check = 1;
        }
        else {
            $sql .= ' AND timing <= TO_DATE(\''.$end.'\', \'yyyy/mm/dd\')';
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
            $sql .= ' GROUP BY EXTRACT(YEAR FROM timing)';
            $check = 1;
        }
        else {
            $sql .= ', EXTRACT(YEAR FROM timing)';
        }
    }
    
    if ($showMonth) {
        if ($check == 0) {
            $sql .= ' GROUP BY EXTRACT(MONTH FROM timing)';
            $check = 1;
        }
        else {
            $sql .= ', EXTRACT(MONTH FROM timing)';
        }
    }
    
    if ($showWeek) {
        if ($check == 0) {
            $sql .= ' GROUP BY TO_CHAR(timing,\'WW\')';
            $check = 1;
        }
        else {
            $sql .= ', TO_CHAR(timing,\'WW\')';
        }
    }
    
    $sql .= ' ORDER BY count DESC';
    
    $conn=connect();
        
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    
    while ($row = oci_fetch_array($stid, OCI_ASSOC)) {        
        foreach ($row as $item) {
                if ($row[0] == $item){
                    $results .= '<tr><td>'.$item;
                }
                else {
                    $results .= '</td><td>'.$item;
                }
        }
        $results .= '</td></tr>';
    }


    oci_free_statement($stid);
    oci_close($conn);
        
}

?>

<html>
<head>
<title>Data Analysis</title>
</head>

<body>
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
<h1><center>Data Analysis</center></h1>

<p><?php echo $message ?></p>

<form name="DataForm" action="<?php echo $php_self?>" method="post" >
<table
    
<tr valign=top align=left>
<td><b><i>Display Fields:</i></b></td>
<td>
<input type="checkbox" name="showUsers" value="users">Users<br>
<input type="checkbox" name="showSubjects" value="subjects">Subjects<br>
<input type="checkbox" name="showYear" value="year">Year<br>
<input type="checkbox" name="showMonth" value="month">Month<br>
<input type="checkbox" name="showWeek" value="week">Week<br>
</td>
</tr>

<tr valign=top align=left>
<td><b><i>Users:</i></b></td>
<td><input type="text" name="keywords" value="" autofocus ><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>Keywords:</i></b></td>
<td><input type="text" name="keywords" value="" autofocus ><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>Start Date:</i></b></td>
<td><input type="date" name="start"><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>End Date:</i></b></td>
<td><input type="date" name="end"><br></td>
</tr>

<tr valign=top align=left>
    <td><input type="submit" name="submitData" value="Submit"></td>
</tr>
</table>


<?php

//echo '<p>';
//echo $sql;
//echo '</p>';

    if (isset($_POST['submitData'])) {      
        if ($results) {
            echo '<table border="1">';
            echo '<caption>Query Results:</caption>';
            echo $columns;
            echo $results;
            echo '</table>';
        }
        else {
            echo '<b><i>No results found</i></b>';
        }
    }

?>

</table>
</form>