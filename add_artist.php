<?php
  session_start();
  include_once('db_control.php');
  $dbcontrol = new DBController();

  $artist_id = $_GET['artist_id'];
  $artist_name = $_GET['artist_name'];
  $user_id = $_SESSION['id'];

  $add_sql = 'INSERT IGNORE INTO artists
              SET spotify_id = "' . $artist_id . '",
              name = "' . $artist_name . '";';
  $res = $dbcontrol->insertQuery($add_sql);
  
  $verify_sql = 'SELECT * FROM artists
                 WHERE spotify_id = "' . $artist_id . '";';
  $verify_res = $dbcontrol->runQuery($verify_sql);

  if (count($verify_res) == 1) {
    $link_sql = 'INSERT IGNORE INTO user_links
                 SET artist_id = "' . $verify_res[0]["id"] . '",
                 user_id = "' . $user_id . '";';
    $link_res = $dbcontrol->insertQuery($link_sql);
  }

  echo json_encode(array("status" => true, "added" => true, "link_id" => $link_res[0]['link_id']));
?>