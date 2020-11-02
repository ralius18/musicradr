<?php
  session_start();
  include_once('db_control.php');
  $dbcontrol = new DBController();

  $link_id = $_GET['link_id'];

  $sql = 'DELETE FROM user_links WHERE link_id = "' . $link_id . '" AND user_id = "' . $_SESSION['id'] . '";';
  $result = $dbcontrol->deleteQuery($sql);
  
  echo json_encode(array("status" => true, "removed" => true));
?>