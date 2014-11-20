
<?php

require_once("helper.php");

$message = 'Select an image for upload';
$registered = true;
$php_self = $_SERVER['PHP_SELF'];

/*** some basic sanity checks ***/
if(1)
    {
    
    echo '<p>Does this work or not?</p>';
    /*** assign the image id ***/
    try     {
        /*** connect to the database ***/
        $conn=connect();

        echo '<p>Gets past connect</p>';

        
        /*** The sql statement ***/
        $sql = "SELECT photo_id, photo FROM images WHERE photo_id=1";

        /*** prepare the sql ***/
        $stid = oci_parse($conn, $sql );

        /*** exceute the query ***/
        $res=oci_execute($stid);
        
        echo '<p>Gets past execute</p>';

        $row = oci_fetch_array($stid, OCI_RETURN_NULLS);
        
        echo '<p>Gets past fetch</p>';

        $id = $row['PHOTO_ID'];
        
        echo '<p>Gets past id load</p>'; echo $id;
        
        try {
            $lob = $row['PHOTO']->load();
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        
        echo '<p>Gets past image load</p>';
        
        echo $lob;
    }
    catch(PDOException $e)
        {
        echo $e->getMessage();
        }
    catch(Exception $e)
        {
        echo $e->getMessage();
        }
        }
  else
        {
        echo 'Please use a real id number';
        }