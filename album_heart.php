<?php
  // Needs: $data = array('id' => ___, 'size' => 20)

  include_once('db_control.php');
  $dbcontrol = new DBController();
  $get_sql = 'SELECT al.link_id, al.album_id, u.id FROM album_links al
              LEFT JOIN users u ON al.user_id = u.id
              WHERE al.album_id = "' . $data['id'] . '"
              AND u.id = "' . $_SESSION['id'] . '"';
  $res = $dbcontrol->runQuery($get_sql);
  if (is_null($res)) {
    echo '<button class="heart" id="' . $data['id'] . '-heart" onclick="add_album(\'' . $data['id'] . '\')">
    <img src="resources/heart.svg" width="' . $data['size'] . 'px" height="' . $data['size'] . 'px">
    </button>';
  } else {
    echo '<button class="heart" id="' . $data['id'] . '-heart" onclick="remove_album(\'' . $res[0]['link_id'] . '\', \'' . $data['id'] . '\')">
    <img src="resources/heart-filled.svg" width="' . $data['size'] . 'px" height="' . $data['size'] . 'px">
    </button>';
  }
?>