<?php
  session_start();
  include_once('db_control.php');
  $dbcontrol = new DBController();

  $album_id = $_GET['album_id'];
  $user_id = $_SESSION['id'];

  $add_sql = 'INSERT IGNORE INTO album_links (album_id, user_id, sort_order)
              VALUES ("' . $album_id . '", ' . $user_id . ',
                (SELECT (CASE WHEN MAX(sort_order) IS NULL THEN 1 ELSE MAX(sort_order)+1 END) FROM album_links al WHERE al.user_id = ' . $user_id . ')
              );';
  $res = $dbcontrol->insertQuery($add_sql);
  
  $verify_sql = 'SELECT * FROM album_links
                 WHERE album_id = "' . $album_id . '"
                 AND user_id = ' . $user_id . ';';
  $verify_res = $dbcontrol->runQuery($verify_sql);

  if (error_get_last() != NULL) {
    echo error_get_last();
  }
  echo json_encode(array("status" => true, "added" => true, "link_id" => $verify_res[0]['link_id']));
?>