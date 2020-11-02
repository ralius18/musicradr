<?php
  include_once('db_control.php');
  $dbcontrol = new DBController();
  session_start();
  if (isset($_POST['spotify_id']) && isset($_POST['name'])) {
    $add_sql = 'INSERT IGNORE INTO artists
            SET spotify_id = "' . $_POST['spotify_id'] . '",
            name = "' . $_POST['name'] . '";';
    $res = $dbcontrol->insertQuery($add_sql);

    $verify_sql = 'SELECT * FROM artists
                  WHERE spotify_id = "' . $_POST['spotify_id'] . '";';
    $verify_res = $dbcontrol->runQuery($verify_sql);

    if (count($verify_res) == 1) {
      $link_sql = 'INSERT IGNORE INTO user_links
                  SET artist_id = "' . $verify_res[0]["id"] . '",
                  user_id = "' . $_SESSION["id"] . '";';
      $link_res = $dbcontrol->insertQuery($link_sql);
    }

    // while ($row = $verify_res->fetch_assoc()) {
    //   $link_sql = 'INSERT IGNORE INTO music.user_links
    //               SET artist_id = "' . $row['id'] . '",
    //               user_id = "' . $_SESSION['id'] . '";';
    //   $link_res = $dbcontrol->insertQuery($add_sql);
    //   // $link_res = $connection->query($link_sql);
    // }

    echo '<form action="list.php" method="POST" id="form">';
    echo '<input type="hidden" name="added" value="' . $_POST['name'] . '">';
    echo '</form>';
  } else {
    echo '<form action="list.php" method="POST" id="form">';
    echo '<input type="hidden" name="error" value="Error adding artist">';
    echo '</form>';
  }
?>
<link rel="stylesheet" href="styles.css">
<script>document.getElementById("form").submit()</script>