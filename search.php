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
    $after = $_POST['after'];
    $after = str_replace('-', '/', $after);
    $before = $_POST['before'];
    $before = str_replace('-', '/', $before);
    $searchType = $_POST['searchType'];

    //$message = "keywords: " . $keywords . ", after: " . $after . ", before: " . $before;
    //Search for images based on submitted conditions
    
    $conn=connect();
    
    if ($keywords != '') {
        $key_array = explode(' ', $keywords);
        
        //$sql = 'SELECT r.photo_id, i.thumbnail, r.tot_score from ( SELECT photo_id, SUM(score) as tot_score from ( SELECT photo_id, ((SCORE(1) * 6) + (SCORE(2) * 3) + SCORE(3)) score FROM images WHERE CONTAINS (subject, \''.$key_array[0].'\', 1) > 0 OR CONTAINS (place, \''.$key_array[0].'\', 2) > 0 OR CONTAINS (description, \''.$key_array[0].'\', 3) > 0';
        
        $contains = $key_array[0];
        
        foreach ($key_array as $key) {
            if ($key_array[0] != $key) {
                //$sql = $sql . ' UNION ALL SELECT photo_id, ((SCORE(1) * 6) + (SCORE(2) * 3) + SCORE(3)) score FROM images WHERE CONTAINS (subject, \''.$key.'\', 1) > 0 OR CONTAINS (place, \''.$key.'\', 2) > 0 OR CONTAINS (description, \''.$key.'\', 3) > 0';
                $contains = $contains . ' | ' . $key;
                
            }
        }
        
        $sql = 'SELECT photo_id, thumbnail, ((SCORE(1) * 6) + (SCORE(2) * 3) + SCORE(3)) score FROM images WHERE CONTAINS (subject, \''.$contains.'\', 1) > 0 OR CONTAINS (place, \''.$contains.'\', 2) > 0 OR CONTAINS (description, \''.$contains.'\', 3) > 0';
        
        //$sql = $sql . ') GROUP BY photo_id) r JOIN images i ON i.photo_id = r.photo_id';
        
        //$sql = $sql . ' and (i.owner_name = \''.$user.'\' or i.permitted = 1 or i.permitted in (SELECT group_id FROM group_lists WHERE friend_id = \''.$user.'\'))';
        $sql = $sql . ' and (owner_name = \''.$user.'\' or permitted = 1 or permitted in (SELECT group_id FROM group_lists WHERE friend_id = \''.$user.'\'))';
        
        if ($after != '') {
            //$sql = $sql . ' and i.timing > TO_DATE(\''.$after.'\', \'yyyy/mm/dd\')';
            $sql = $sql . ' and timing > TO_DATE(\''.$after.'\', \'yyyy/mm/dd\')';
        }
        else if ($before != '') {
            //$sql = $sql . ' and i.timing < TO_DATE(\''.$before.'\', \'yyyy/mm/dd\')';
            $sql = $sql . ' and timing < TO_DATE(\''.$before.'\', \'yyyy/mm/dd\')';
        }
        
        if ($searchType == 'newest') {
            //$sql = $sql . ' ORDER BY i.timing DESC';
            $sql = $sql . ' ORDER BY timing DESC';
        }
        else if ($searchType == 'oldest') {
            //$sql = $sql . ' ORDER BY i.timing';
            $sql = $sql . ' ORDER BY timing';
        }
        else {
            //$sql = $sql . ' ORDER BY r.tot_score DESC';
            $sql = $sql . ' ORDER BY score DESC';
        }
    }
    else {
        $sql = 'SELECT photo_id, thumbnail FROM images';
        
        $sql = $sql . ' WHERE (owner_name = \''.$user.'\' or permitted = 1 or permitted in (SELECT group_id FROM group_lists WHERE friend_id = \''.$user.'\'))';
        
        if ($after != '') {
            $sql = $sql . ' and timing > TO_DATE(\''.$after.'\', \'yyyy/mm/dd\')';
        }
        else if ($before != '') {
            $sql = $sql . ' and timing < TO_DATE(\''.$before.'\', \'yyyy/mm/dd\')';
        }  
        
        if ($searchType == 'newest') {
            $sql = $sql . ' ORDER BY timing DESC';
        }
        else if ($searchType == 'oldest') {
            $sql = $sql . ' ORDER BY timing';
        }
    }
    
    $message = $sql;
    
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    
    $images = '<br><tr><td>Search Results: </td></tr>';
    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $id = $row['PHOTO_ID'];
        $data = $row['THUMBNAIL']->load();
        $images .= '<tr><td><a href=display.php?photo_id=' . $id . '><img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/></a></td></tr>';            
    }
    
    oci_free_statement($stid);
    oci_close($conn);
}
else {
    $conn=connect();
    $sql = 'SELECT thumbnail, photo_id FROM images WHERE (owner_name = \''.$user.'\' or permitted = 1 or permitted in (SELECT group_id FROM group_lists WHERE friend_id = \''.$user.'\'))';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    
    //Currently will show all images in database, need to find 5 most viewed
    $images = '<br><tr><td>Popular Images: </td></tr>';
    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $id = $row['PHOTO_ID'];
        $data = $row['THUMBNAIL']->load();
        $images .= '<tr><td><a href=display.php?photo_id=' . $id . '><img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/></a></td></tr>';            
    }
    
    oci_close($conn);
}
    
?>

<html>
<head>
<title>Search</title>
</head>

<body>
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
<h1><center>Search</center></h1>

    <?php
        
        echo 'Hello ' . $_SESSION['username'];// . ', message: ' . $message;// . ' session id: ' . session_id() . ", message: " . $message;
        
    ?>
    
</p>
<p><?php echo $message ?></p>

<form name="SearchForm" action="<?php echo $php_self?>" method="post" >
<table
    
<tr valign=top align=left>
<td><b><i>Search method:</i></b></td>
<td>
<input type="radio" name="searchType" value="newest">Newest<br>
<input type="radio" name="searchType" value="oldest">Oldest<br>
</td>
</tr>

<tr valign=top align=left>
<td><b><i>Keywords:</i></b></td>
<td><input type="text" name="keywords" value="" autofocus ><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>After Date:</i></b></td>
<td><input type="date" name="after"><br></td>
</tr>

<tr valign=top align=left>
<td><b><i>Before Date:</i></b></td>
<td><input type="date" name="before"><br></td>
</tr>

<tr valign=top align=left>
    <td><input type="submit" name="submitSearch" value="Search"></td>
</tr>
</table>




<?php

echo '<table>';
echo $images;
echo '</table>';

?>

</table>
</form>

</body>
</html>