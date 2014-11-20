
<?php

require_once("helper.php");

   
    //echo '<p>Does this work or not?</p>';
    /*** assign the image id ***/
    $conn=connect();

    $sql = "SELECT photo FROM images WHERE photo_id = 1";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if (!$row) {
        header('Status: 404 Not Found');
    } else {
        $img = $row['PHOTO']->load();
        header("Content-type: image/jpeg");
        echo $img;
    }