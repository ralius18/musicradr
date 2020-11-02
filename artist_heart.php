<?php
  # TODO: Move SQL out of here
  include_once('db_control.php');
  $dbcontrol = new DBController();
  $get_sql = 'SELECT a.spotify_id, ul.link_id FROM artists a
              LEFT JOIN user_links ul ON a.id = ul.artist_id
              LEFT JOIN users u ON ul.user_id = u.id
              WHERE a.spotify_id = "' . $data['id'] . '"
              AND u.id = "' . $_SESSION['id'] . '"';
  $res = $dbcontrol->runQuery($get_sql);
  if (is_null($res)) {
    echo '<button class="heart" id="' . $data['id'] . '-heart" onclick="add_artist(\'' . $data['id'] . '\', \'' . $data['name'] . '\')" title="Add to Followed Artists">
    <img src="resources/heart.svg" width="' . $data['size'] . 'px" height="' . $data['size'] . 'px">
    </div>
    </button>';
  } else {
    echo '<button class="heart" id="' . $data['id'] . '-heart" onclick="remove_artist(\'' . $res[0]['link_id'] . '\', \'' . $data['id'] . '\')" title="Remove from Followed Artists">
    <img src="resources/heart-filled.svg" width="' . $data['size'] . 'px" height="' . $data['size'] . 'px">
    </button>';
  }
?>