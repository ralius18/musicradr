<?php
  session_start();
  include_once('db_control.php');
  $dbcontrol = new DBController();

  $new_links = $_POST['new_links'];
  $user_id = $_SESSION['id'];

  for ($i = 0; $i < count($new_links); ++$i) {
    $update_sql = 'UPDATE album_links
                   SET sort_order = ' . ($i + 1) . '
                   WHERE link_id = ' . $new_links[$i] . ';';
    $res = $dbcontrol->updateQuery($update_sql);
  }
  
  $verify_sql = 'SELECT * FROM album_links
                 WHERE user_id = ' . $user_id . ';';
  $verify_res = $dbcontrol->runQuery($verify_sql);

  if (error_get_last() != NULL) {
    echo error_get_last();
  }
  echo json_encode(array("status" => true, "added" => true, "link_id" => $verify_res));
?>