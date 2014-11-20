<?php
include_once ("helper.php"); 
$message = "";
$images = "";
$php_self = $_SERVER['PHP_SELF'];
session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}

if(!empty($_POST) && isset($_POST['submitSearch'])) {
    // The user submitted information
    $keywords = $_POST['keywords'];
    $after = $_POST['after'];
    $before = $_POST['before'];
    $searchType = $_POST['searchType'];

    //$message = "keywords: " . $keywords . ", after: " . $after . ", before: " . $before;
    //Search for images based on submitted conditions
    
    
    $images = '<br><tr><td>Search Results: </td></tr>';
    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $id = $row['PHOTO_ID'];
        $data = $row['THUMBNAIL']->load();
        $message = "in loop";
        $images .= '<tr><td><a href=display.php?photo_id=' . $id . '><img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/></a></td></tr>';            
    }
}
else {
    $conn=connect();
    $sql = 'SELECT thumbnail, photo_id FROM images';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    
    //Currently will show all images in database, need to find 5 most viewed
    $images = '<br><tr><td>Popular Images: </td></tr>';
    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $id = $row['PHOTO_ID'];
        $data = $row['THUMBNAIL']->load();
        $images .= '<tr><td><a href=display.php?photo_id=' . $id . '><img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/></a></td></tr>';            
    }
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


<form name="SearchForm" action="<?php echo $php_self?>" method="post" >
<table
    
<tr valign=top align=left>
<td><b><i>Search method:</i></b></td>
<td>
<input type="radio" name="searchType" value="keywords" checked="checked">Keywords<br>
<input type="radio" name="searchType" value="newest">Newest<br>
<input type="radio" name="searchType" value="oldest">Oldest<br>
</td>
</tr>

<tr valign=top align=left>
<td><b><i>Keywords:</i></b></td>
<td><input type="text" name="keywords" value="keywords..." autofocus ><br></td>
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