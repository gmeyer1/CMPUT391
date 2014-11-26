<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}

$user = $_SESSION['username'];
$php_self = $_SERVER['PHP_SELF'];
$conn=connect();


if (isset($_POST['addGroup'])) {
    $group_name = $_POST['group_name'];
    $curr_id = 0;
    
    $sql = 'SELECT user_name FROM groups WHERE user_name = \'' . $user . '\' and group_name = \'' . $group_name . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $message = "Group " . $group_name . " already exists";
        $valid = false;
    }
    else if (empty($group_name)) {
        $message = "Group name cannot be empty";
        $valid = false;
    }
    else {
    
        $sql = 'SELECT MAX(group_id) FROM groups';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        $row = oci_fetch_array($stid);

        if($row) {
            $curr_id = $row['MAX(GROUP_ID)'];
        }

        $curr_id++;

        oci_free_statement($stid);

        $date = date('d.M.y');
        $sql = 'INSERT INTO groups VALUES (\''.$curr_id.'\',\''.$user.'\',\''.$group_name.'\',\''.$date.'\')'; 

        $stid = oci_parse($conn, $sql);
        $res=oci_execute($stid);

        if (!$res) {
            $err = oci_error($stid); 
            $message .= htmlentities($err['message']);
            $message .= "<br/>Could not create group " . $group_name;
        }
        else{ 
            $message = 'Created group ' . $group_name;
        }
        oci_free_statement($stid);
    }
}



if ($user == 'admin') {
    $sql = 'SELECT group_name, group_id FROM groups';
}
else {
    $sql = 'SELECT group_name, group_id FROM groups WHERE user_name=\'' . $user . '\'';
}

$stid = oci_parse($conn, $sql);
oci_execute($stid);


$groups = '';
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    
    $groups .= '<option value="'.$group_id.'">'.$group_name.'</option>';
}

oci_free_statement($stid);



$sql = 'SELECT g.group_id, g.group_name, g.user_name, l.notice FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE l.friend_id=\'' . $user . '\'';
    

$stid = oci_parse($conn, $sql);
oci_execute($stid);


$member_of = '';
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    $group_owner = $row['USER_NAME'];
    $notice = $row['NOTICE'];
    
    $member_of .= '<tr><td><b><i>' . $group_name . '</i></b></td><td><b><i>' . $group_owner . '</i></b></td><td><b><i>' . $notice . '</i></b></td></tr>';
}

oci_free_statement($stid);


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

<?php echo $message . "<br/>" ?>

<form id='edit' action="edit_group.php" method='post'
    accept-charset='UTF-8'>
    
<table>
    
    <?php
    if ($groups) {
        echo 'Owner of Groups:';
        echo '<select name="group_id">';
        echo $groups;
        echo '</select>';
    ?>
    
    <input type='submit' name='editGroup' value='Edit' />
    <input type='submit' name='deleteGroup' value='Delete' />
    
           
    <?php
    }
    else {
        echo '<p>Owner of no groups</p>';
    }
    ?>
    
</table>


</form>

<form id='add' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<table border="1">
    <caption>Create New Group</caption>
    <tr valign=top align=left>
    <td>
        <b><i>Group Name: </i></b></td>
    <td>
        <input type='text' name='group_name' value="" id='group_name' maxlength="24"/><br>
    </td>
    <td><input type="submit" name="addGroup" value="Create"></td>
    </tr>


</form>


<?php


if ($member_of) {
    
    echo '<table border="1">';
    echo '<caption>Member of Groups:</caption>';
    echo '<tr><td><b><i>Group Name</i></b></td><td><b><i>Group Owner</i></b></td><td><b><i>Notice</i></b></td></tr>';
    echo $member_of;
    echo '</table>';
}
else {
    echo '<b><i>Member of no groups</i></b>';
}
?>



</body>
</html>