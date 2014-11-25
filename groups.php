<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}

$user = $_SESSION['username'];
$php_self = $_SERVER['PHP_SELF'];
$conn=connect();
$sql = 'SELECT group_name, group_id FROM groups WHERE user_name=\'' . $user . '\'';
$stid = oci_parse($conn, $sql);
oci_execute($stid);


$groups = "";
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    
    $groups .= '<option value="'.$group_id.'">'.$group_name.'</option>';
    
    
    //$groups .= '<tr><td><b><i>Group: '.$group_name.'</i></b></td><td>'
    //        .'<input type=\'submit\' name=\'edit'.$group_id.'\' value=\'Edit\' />'
    //        .'<input type=\'submit\' name=\'delete'.$group_id.'\' value=\'Delete\' />'
    //        .'</tr>';
}


oci_close($conn);
    
?>


<html>
<head>
<title>Groups</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<h1><center>Groups</center></h1>

<form id='edit' action="edit_group.php" method='post'
    accept-charset='UTF-8'>
    
<table>
    
    <?php
    if ($groups) {
        echo '<p>Groups:</p>';
        echo '<select name="group_id">';
        echo $groups;
        echo '</select>';
    ?>
    
    <input type='submit' name='editGroup' value='Edit' />
    <input type='submit' name='deleteGroup' value='Delete' />
    
           
    <?php
    }
    else {
        echo '<p>No groups</p>';
    }
    ?>

</table>


</form>


</body>
</html>