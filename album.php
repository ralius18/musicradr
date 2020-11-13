<?php
  session_start();
  require_once('db_control.php');

  $dbcontrol = new DBController();

  $album_id = $_GET['id'];

  function format_ms($time) {
    $mss = $time % 1000;
    $time = floor($time / 1000);
    $secs = $time % 60;
    $time = floor($time / 60);
    $mins = $time % 60;
    $time = floor($time / 60);
    $hrs = $time % 60;
    $time_str = $mins . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
    if ($hrs > 0) {
      $time_str = $hrs . ':' . $time_str;
    }
    return $time_str;
  }

  // $artist_sql = 'SELECT * FROM artists WHERE spotify_id = "' . $artist_id . '";';
  // $artist_name = $dbcontrol->runQuery($artist_sql)[0]['name'];

  $user_sql = 'SELECT id, country FROM users WHERE id = ' . $_SESSION["id"] . ';';
  $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
  // $auth_cmd = 'curl -X "POST" -H "Authorization: Basic ' . base64_encode($id_secret) . '" -d grant_type=client_credentials https://accounts.spotify.com/api/token';
  // $token = json_decode(shell_exec($auth_cmd), true)["access_token"];

  include_once 'auth.php';
  include_once 'curl.php';

  // $artist_cmd = "curl -X 'GET'\
  //               -H 'Authorization: Bearer " . $token . "'\
  //               https://api.spotify.com/v1/artists/" . $artist_id;
  // $artist_res = json_decode(shell_exec($artist_cmd), true);

  // $album_cmd = 'curl -X "GET" -H "Authorization: Bearer ' . $token . '" "https://api.spotify.com/v1/albums/' . $album_id . '?market=' . $user_country . '"'; // TODO: use market as well
  // $album = json_decode(shell_exec($album_cmd), true);
  // $album = $album;

  $album = exec_curl('https://api.spotify.com/v1/albums/' . $album_id . '?market=' . $user_country, $token);
  $album_exists = !isset($album['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Music Radr - <?php echo $album_exists ? $album['name'] : 'Not Found'; ?></title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="styles.css">
    <script type="text/javascript" src="main.js"></script>
</head>
<body>
  <div class="body">
    <form action="logout.php">
      <input type="submit" value="Logout" style="float: right;" action="logout.php" class="btn">
    </form>
    <form action="list.php">
      <input type="submit" value="Home" style="float: right;" action="list.php" class="btn">
    </form>
    <form action="albums.php">
      <input type="submit" value="Albums" style="float: right;" action="albums.php" class="btn">
    </form>
    <?php include 'color_switch.php'; ?>
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <br>
    <br>
    <?php
      if ($album_exists) {
    ?>
    <table style="width: 100%;">
      <tr>
        <td style="border-bottom: none">
          <div class="artists-table">
          <?php
            $total_time = 0;
            echo '<table class="album-table">';
            foreach($album['tracks']['items'] as $track) {
              echo '<tr>';

              echo '<td class="album">';
              echo $track['track_number'];
              echo '</td>';

              echo '<td class="album">';
              $artist_name = $album['artists'][0]['name'];
              $artist_lower = strtolower($artist_name);
              $artist_lower = preg_replace('/^([Tt]he|[Aa])\b/', '', $artist_lower);
              $artist_lower = preg_replace('/[^A-Za-z0-9]*/', '', $artist_lower);
              $track_lower = strtolower(preg_replace('/\([feat|with].*\)/', '', $track['name']));
              $track_lower = preg_replace('/[^A-Za-z0-9]*/', '', $track_lower);
              echo '<a href="https://azlyrics.com/lyrics/' . $artist_lower . '/' . $track_lower . '.html" target="_blank">' . $track['name'] . '</a>';
              echo '</td>';

              echo '<td class="explicit">';
              if ($track['explicit']) {
                echo '<span class="explicit">EXPLICIT</span>';
              }
              echo '</td>';

              $time = $track['duration_ms'];
              $total_time += $time;
              echo '<td class="album">';
              echo format_ms($time);
              echo '</td>';

              if (sizeof($track['artists']) > 1) {
                echo '<td>';
                foreach($track['artists'] as $artist) {
                  echo '<a href="artist.php?id=' . $artist['id'] . '">' . $artist['name'] . "</a><br>";
                }
                echo '</td>';
              }

              // echo '<td class="album">';
              // echo '</td>';

              echo '</tr>';
            }
            echo '<tr>';
            echo '<td>';
            echo '<b>Total</b>';
            echo '</td>';
            echo '<td></td>';
            echo '<td></td>';
            echo '<td>';
            echo '<b>' . format_ms($total_time) . '</b>';
            echo '</td>';
            echo '</tr>';
            echo '</table>';

            echo '<br>';

            $total_pages = ceil($album['tracks']['total'] / $album['tracks']['limit']);
            if ($total_pages > 1){
              $url = strtok(ltrim($_SERVER['REQUEST_URI'], '/'), '?');
              // 'Previous' page button
              echo '<table class="pages"><tr>';
              echo '<td class="td-pages" style="width: 15%">';
              if ($page > 1){ ?>
                <form action="<?php $url ?>" method="GET" class="form-inline next">
                <input type="hidden" name="id" value="<?php $album_id ?>">
                <input type="hidden" name="page" value="<?php $page-1 ?>">
                <input type="submit" value="Next" class="btn">
                </form>
                <?php
              }
              echo '</td>';
              echo '<td class="td-pages">';
              echo '<table><tr>';
              // TODO: Space out numbers, and place in between buttons
              $uri_base = 'album.php?id=' . $album_id . '&page=';
              for ($i = 1; $i <= $total_pages; $i++) {
                echo '<td>';
                if ($i != $page) {
                  echo '<a href="' . $uri_base . $i . '">' . $i;
                } else {
                  echo $i;
                }
                echo '</td>';
              }
              echo '</tr></table>';
              echo '</td>';
              // 'Next' page button
              echo '<td class="td-pages" style="width: 15%">';
              if ($album['tracks']['next'] != NULL) { ?>
                <form action="<?php $url ?>" method="GET" class="form-inline next">
                <input type="hidden" name="id" value="<?php $album_id ?>">
                <input type="hidden" name="page" value="<?php $page+1 ?>">
                <input type="submit" value="Next" class="btn">
                </form>
                <?php
              }
              echo '</td>';
              echo '</tr></table>';
            }
          ?>
          </div>
        </td>
        <td class="artist-td" style="border-bottom: none">
            <img src="<?php echo $album['images'][0]['url']; ?>" width="200px" style="border-radius: 4px;">
          </a>
          <br>
          <br>
          <?php
            $data = array('id' => $album['id'], 'size' => 30);
            include 'album_heart.php'
          ?>
          <br>
          <h3 style="text-align: center"><?php echo $album['name']; ?></h3>
          <?php 
              if ($album['release_date_precision'] == 'day') {
                $date = new DateTime($album['release_date']);
                echo $date->format('d M Y');
              } else if ($album['release_date_precision'] == 'month'){
                $date = DateTime::createFromFormat('Y-m', $album['release_date']);
                echo $date->format('M Y');
              } else {
                $date = DateTime::createFromFormat('Y', $album['release_date']);
                echo $date->format('Y');
              }
              echo '<br>';
            foreach($album['artists'] as $artist) {
              echo '<a href="artist.php?id=' . $artist['id'] . '">' . $artist['name'] . "</a><br>";
            }
          ?>
          <br>
          <a class="icon" href="spotify:album:<?php echo $album['id']; ?>">
            <img src="resources/spotify.png" width="30px" title="Open in Spotify">
          </a>
          <br>
          <br>
          <!-- <iframe src="https://open.spotify.com/embed/album/<?php echo $album_id; ?>"
            width="300"
            height="380"
            frameborder="0"
            allowtransparency="true"
            allow="encrypted-media"
          ></iframe> -->
        </td>
      </tr>
    </table>
    <?php
      } else {
        echo '<h3>Album Not Found</h3>';
      }
      include 'footer.php'
    ?>
  </div>  
  <?php include 'playing.php' ?>
</body>