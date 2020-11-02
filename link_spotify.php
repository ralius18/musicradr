<?php
  require_once 'curl.php';
  require_once 'db_control.php';

  session_start();

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];

  $dbcontrol = new DBController();

  $user_sql = "SELECT email, spotify_access, spotify_refresh, spotify_expires FROM users WHERE email = '" . $_SESSION['email'] . "';";
  $user = $dbcontrol->runQuery($user_sql)[0];
  $isAvailable = strlen($user['spotify_access']) > 0 && new DateTime() < new DateTime($user['spotify_expires']);

  if (! $isAvailable) {
    if ($_GET['code']) {
      // Need to authorize
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      $headers = array();
      $headers[] = 'Authorization: Basic ' . base64_encode($id_secret);
      $headers[] = 'Content-Type: application/x-www-form-urlencoded';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $redirect = 'http://musicradr/link_spotify.php';
      $body = 'grant_type=authorization_code&redirect_uri=' . urlencode($redirect) . '&code=' . $_GET['code'];
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      $result = json_decode(curl_exec($ch), true);
      if (curl_errno($ch)) {
          echo 'Error:' . curl_error($ch);
      } else if (array_key_exists('error', $result)) {
        curl_close($ch);
        var_dump($result);
        // Do Error
      } else {
        curl_close($ch);

        // Save response away
        $expires = date("Y-m-d H-i-s", time() + $result['expires_in']);
        $sql = "UPDATE users SET spotify_access = '" . $result['access_token'] . "',
        spotify_expires = '" . $expires . "',
        spotify_refresh = '" . $result['refresh_token'] . "'
        WHERE email = '" . $_SESSION['email'] . "'";
        $sql_res = $dbcontrol->updateQuery($sql);
        
        // Get followed artists
        $following = exec_curl('https://api.spotify.com/v1/me/following?type=artist', $result['access_token']);
      }
    } else {
      // Need to refresh
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      $headers = array();
      $headers[] = 'Authorization: Basic ' . base64_encode($id_secret);
      $headers[] = 'Content-Type: application/x-www-form-urlencoded';
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $body = 'grant_type=refresh_token&refresh_token=' . urlencode($user['spotify_refresh']);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      $result = json_decode(curl_exec($ch), true);

      $expires = date("Y-m-d H-i-s", time() + $result['expires_in']);
      $sql = "UPDATE users SET spotify_access = '" . $result['access_token'] . "',
      spotify_expires = '" . $expires . "'
      WHERE email = '" . $_SESSION['email'] . "'";
      $sql_res = $dbcontrol->updateQuery($sql);
    }
  } else {
    $following = array();
    $follow_res = exec_curl('https://api.spotify.com/v1/me/following?limit=50&type=artist', $user['spotify_access']);
    if (array_key_exists('artists', $follow_res)) {
      if (array_key_exists('items', $follow_res['artists'])) {
        $following = array_merge($following, $follow_res['artists']['items']);
        if (array_key_exists('next', $follow_res['artists'])) {
          $next = gettype($follow_res['artists']['next']) == 'string' ? $follow_res['artists']['next'] : '';
        }
      }
    }
    while (strlen($next) > 0) {
      $follow_res = exec_curl($follow_res['artists']['next'], $user['spotify_access']);
      if (gettype($follow_res) == 'array' && array_key_exists('artists', $follow_res)) {
        if (gettype($follow_res['artists']) == 'array' && array_key_exists('items', $follow_res['artists'])) {
          $following = array_merge($following, $follow_res['artists']['items']);
        }
      }
      if (array_key_exists('next', $follow_res['artists'])) {
        $next = gettype($follow_res['artists']['next']) == 'string' ? $follow_res['artists']['next'] : '';
      }
    }
    
    function sortByName($a, $b) {
      return $a['name'] <=> $b['name'];
    }

    usort($following, 'sortByName');

    $cur_sql = "SELECT a.name, a.spotify_id, ul.link_id FROM artists AS a
    LEFT JOIN user_links ul ON a.id = ul.artist_id
    LEFT JOIN users u ON ul.user_id = u.id
    WHERE u.id = " . $_SESSION['id'] . " AND a.is_active IS TRUE
    ORDER BY 
      CASE 
        WHEN a.name REGEXP '^(A|An|The)[[:space:]]' = 1 THEN 
          TRIM(SUBSTR(a.name , INSTR(a.name ,' '))) 
        ELSE a.name
      END
    ";
    $cur_res = $dbcontrol->runQuery($cur_sql);
    $cur_ids = array();
    foreach($cur_res as &$artist){
      array_push($cur_ids, $artist['spotify_id']);
    }

    $to_add = array();

    foreach($following as &$artist) {
      if (!in_array($artist['id'], $cur_ids)) {
        array_push($to_add, $artist);
      }
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Settings</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <form action="logout.php">
    <input type="submit" value="Logout" style="float: right;" action="logout.php" class="btn">
  </form>
  <form action="settings.php">
    <input type="submit" value="Settings" style="float: right;"class="btn">
  </form>
  <form action="list.php">
    <input type="submit" value="Home" style="float: right;" action="list.php" class="btn">
  </form>
  <?php include 'color_switch.php'; ?>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a>

  <br>
  <br>
  <!-- <div class="wrapper"> -->
  
    <?php
    if (count($to_add) > 0) {
      echo '<h4>The following artists will be added:</h4><br>';
      echo '<form action="/import_export.php" method="POST" id="addForm">';
      echo '<input type="hidden" name="submit_type" value="spotify">';
      echo '<ul>';

      foreach($to_add as $i=>$artist) {
        echo '<li>';
        echo '<label><input class="check" type="checkbox" name="artist' . $i . '" value="' . $artist["id"] . '" checked>';
        echo '<a class="artist" href="/artist.php?id=' . $artist["id"] . '">' . $artist["name"] . '</a></label>';
        // echo '<span class="checkbox">' . $artist["name"] . '</span></label>';
        echo '</li>';
      }
      echo '</ul>';
      echo '<br>';
      echo '<br>';
      echo '<input type="button" value="Add Selected Artists" class="btn btn-primary" onclick="confirmAdd()">';
      echo '<input type="reset" value="Clear Selection" style="padding: \'0 0 0 100\'" class="btn">';
      echo '<button type="button" onclick="selectAll()" id="select-button" class="btn">Select All</button>';
    } else {
      echo '<h4>There are no artists to add</h4>';
    }
    ?>
    </ul>
  <!-- </div> -->
  <?php
    include 'footer.php'
  ?>

  <script>
    function selectAll() {
      var checks = document.getElementsByClassName('check')
      for (i = 0; i < checks.length; i++) {
        checks[i].checked = true;
      }
    }
    function confirmAdd() {
      var checks = document.getElementsByClassName('check')
      var checked = []
      for (i = 0; i < checks.length; i++) {
        if (checks[i].checked){
          checked.push(checks[i])
        }
      }
      var plural = checked.length > 1 ? ' artists?' : ' artist?'
      if (checked.length > 0) {
        var res = confirm("Are you sure you want to add " + checked.length + plural)
        if (res == true) {
          document.getElementById('addForm').submit()
        }
      }
    }
  </script>
</body>