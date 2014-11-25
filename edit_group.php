<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}
else if (!isset($_POST['group_id'])) {
    redirect('groups.php');
}

$user = $_SESSION['username'];
$php_self = $_SERVER['PHP_SELF'];
$group_id = $_POST['group_id'];
$group_name = "";
$group_owner = "";
$users = "";
$message = "";
$deleted = 0;
$deleted_users = 0;

$conn=connect();

$sql = 'select group_name, user_name from groups where group_id=\'' . $group_id . '\'';
    
$stid = oci_parse($conn, $sql);
oci_execute($stid, OCI_DEFAULT);  
if ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_name = $row['GROUP_NAME'];
    $group_owner = $row['USER_NAME'];
    if ($group_owner != $user && $user != "admin") {
        redirect('groups.php');
    }
}
else { 
    redirect('groups.php');
}
oci_free_statement($stid);

if (isset($_POST['deleteGroup'])) {
    echo 'About to delete group ' . $group_name;
    $sql = 'DELETE FROM group_lists WHERE group_id = \'' . $group_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
    if($row) {
        $deleted_users = 1;
    }
    else {
        $message .= "Could not remove users from group " . $group_name . "<br/>";
    }
    
    oci_free_statement($stid);
    
    $sql = 'DELETE FROM groups WHERE group_id = \'' . $group_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
    if($row) {
        if (oci_commit($conn)) {
            $deleted = 1;
        }
        else {
            $message .= "Could not commit to delete group " . $group_name;
        }  
    }
    else {
        $message .= "Could not delete group " . $group_name;
    }
    
    oci_free_statement($stid);
}
else if (isset($_POST['addUser'])) {
    $user_name = $_POST['user_name'];
    $notice = $_POST['notice'];
    $date = date('d.M.y');
    
    $sql = 'SELECT friend_id FROM group_lists WHERE friend_id = \'' . $user_name . '\' and group_id = \'' . $group_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $message = $user_name . " is already in group " . $group_name;
    }
    else {

        $sql = 'SELECT user_name FROM users WHERE user_name = \'' . $user_name . '\'';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
        if ($row) {

            oci_free_statement($stid);

            $sql = 'INSERT INTO group_lists VALUES (\''.$group_id.'\',\''.$user_name.'\',\''.$date.'\',\''.$notice.'\')'; 

            $stid = oci_parse($conn, $sql);
            $res=oci_execute($stid);

            if (!$res) {
                $err = oci_error($stid); 
                $message .= htmlentities($err['message']);
                $message .= "<br/>Could not add " . $user_name . " to group";
            }
            else{ 
                $message = 'Added ' . $user_name . ' to group';
            }

        }
        else {
            $message = $user_name . ' does not exist';
        }
    }
    oci_free_statement($stid);
}
else if (isset($_POST['removeUser'])) {
    $user_name = $_POST['user_name'];

    $sql = 'DELETE FROM group_lists WHERE friend_id = \'' . $user_name . '\' and group_id = \'' . $group_id . '\'';
            
    $stid = oci_parse($conn, $sql);
    $res=oci_execute($stid);

    if (!$res) {
        $err = oci_error($stid); 
        $message .= htmlentities($err['message']);
        $message .= "<br/>Could not remove " . $user_name . " from group";
    }
    else{ 
        $message = 'Removed ' . $user_name . ' from group';
    }
    oci_free_statement($stid);
    
}

if (!$deleted) {
    $sql = 'select friend_id from group_lists where group_id=\'' . $group_id . '\'';

    $stid = oci_parse($conn, $sql);
    oci_execute($stid, OCI_DEFAULT);

    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $friend_id = $row['FRIEND_ID'];
        $users .= '<option value="'.$friend_id.'">'.$friend_id.'</option>';
    }

    oci_free_statement($stid);
}

oci_close($conn);

?>


<html>
<head>
<title>Group</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<form name="GroupsForm" action="groups.php" method="get" >

<input type="submit" value="Groups">

</form>
    
    
<?php echo $message . '<br/>'?>
    
<h1><center>
        
<?php
if ($deleted) {
    echo 'Deleted group ' . $group_name;
}
else {
?>
    
    
Edit Group


<?php
    echo ': ' . $group_name . '<br/>Group owner: ' . $group_owner;
    
?>

</center></h1>
      
<?php
        if ($users) {
?>

<form id='remove' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>
    
<input type='hidden' name='group_id' value="<?php echo $group_id ?>" />    

<table>
    
People in group:
<select name="user_name">
    
<?php
        echo $users;
?>
</select>
    
<input type='submit' name='removeUser' value='Remove' />
    
           
    <?php
    }
    else {
        echo '<p>No group members</p>';
    }
    
    ?>

</table>


</form>
    
    <p>Add User:</p>    

<form id='add' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<input type='hidden' name='group_id' value="<?php echo $group_id ?>" />

<table>
    <tr valign=top align=left>
    <td>
        <b><i>Username: </i></b></td>
    <td>
        <input type='text' name='user_name' value="" id='user_name' maxlength="24"/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Notice: </i></b></td>
    <td>
        <input type='text' name='notice' value="" id='notice' maxlength="1024" /><br>
    </td>
    </tr>
    
    <tr valign=top align=left>
    <td><input type="submit" name="addUser" value="Add"></td>
    </tr>


</form>
    
<?php

}

?>
    

</body>
</html>