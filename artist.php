<?php
  require_once('db_control.php');

  session_start();
  $dbcontrol = new DBController();

  $artist_id = $_GET['id'];
  $page = isset($_GET['page']) ? $_GET['page'] : 1;
  $offset = $page > 1 ? '&offset=' . ($page-1)*20 : '';

  $artist_sql = 'SELECT * FROM artists WHERE spotify_id = "' . $artist_id . '";';
  $artist_row = $dbcontrol->runQuery($artist_sql)[0];
  $artist_name = $artist_row['name'];

  $link_sql = 'SELECT * FROM user_links ul JOIN artists a ON ul.artist_id = a.id WHERE ul.user_id = ' . $_SESSION['id'] . ' AND a.spotify_id = "' . $artist_id . '";';
  $link_rows = $dbcontrol->numRows($link_sql);

  $user_sql = 'SELECT id, country FROM users WHERE id = ' . $_SESSION["id"] . ';';
  $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
  // $auth_cmd = 'curl -X "POST" -H "Authorization: Basic ' . base64_encode($id_secret) . '" -d "grant_type=client_credentials" https://accounts.spotify.com/api/token';
  // $auth_res = shell_exec($auth_cmd);
  // $token = json_decode($auth_res, true)["access_token"];
  include_once 'auth.php';
  include_once 'curl.php';

  $artist_res = exec_curl('https://api.spotify.com/v1/artists/'. $artist_id, $token);

  // $search_cmd = 'curl -X "GET" -H "Authorization: Bearer ' . $token . '" "https://api.spotify.com/v1/artists/' . $artist_id . '/albums?limit=20&include_groups=album,single,compilation&market=' . $user_country . $offset . '"'; // TODO: use market as well
  // $search_res = json_decode(shell_exec($search_cmd), true);
  // $albums = $search_res["items"];

  $album_res = exec_curl('https://api.spotify.com/v1/artists/'. $artist_id . '/albums?limit=20&include_groups=album,single,compilation&market=' . $user_country . $offset, $token);
  $artist_exists = !isset($album_res['error']);
  if ($artist_exists) {
    $albums = $album_res["items"];
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - <?php echo $artist_exists ? $artist_res['name'] : 'Not Found'; ?></title>
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
      if ($artist_exists) {
    ?>
    <table class="artist-page" style="width: 100%;">
      <tr>
        <td>
          <div class="artists-table">
            <br><br><br><br>
          <?php
            echo '<table>';
            foreach($albums as $album) {
              echo '<tr id=' . $album['id'] . '>';

              echo '<td>';
              $data = array('id' => $album['id'], 'name' => $album['name'], 'size' => '20');
              include 'album_heart.php';
              echo '</a>';
              echo '</td>';

              $album_href = '"album.php?id=' . $album['id'] . '"';
              echo '<td>';
              echo '<a href=' . $album_href . '>';
              echo '<img src="' . $album["images"][0]["url"] . '" width="120px" height="120px" style="border-radius: 4px;">';
              echo '</a>';
              echo '</td>';

              echo '<td class="album album_name">';
              echo '<a href=' . $album_href . '>' . $album['name'] . '</a>';
              echo '</td>';

              echo '<td class="album">';
              echo ucfirst($album['album_type']);
              echo '</td>';

              echo '<td class="album">';
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
              echo '</td>';

              echo '</tr>';
            }
            
            echo '</table>';

            echo '<br>';

            $total_pages = ceil($album_res['total'] / $album_res['limit']);
            if ($total_pages > 1){
              $url = strtok(ltrim($_SERVER['REQUEST_URI'], '/'), '?');
              // 'Previous' page button
              echo '<table class="pages"><tr>';
              echo '<td class="td-pages" style="width: 15%">';
              if ($page > 1){
                echo '<form action="' . $url . '" method="GET" class="form-inline">';
                echo '<input type="hidden" name="id" value="' . $artist_id . '">';
                echo '<input type="hidden" name="page" value="' . ($page-1) . '">';
                echo '<input type="submit" value="Previous" class="btn">';
                echo '</form>';
              }
              echo '</td>';
              echo '<td class="td-pages">';
              echo '<table><tr>';
              // TODO: Space out numbers, and place in between buttons
              $uri_base = 'artist.php?id=' . $artist_id . '&page=';
              for ($i = 1; $i <= $total_pages; $i++) {
                echo '<td style="border-bottom: none">';
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
              if ($album_res['next'] != NULL) {
                echo '<form action="' . $url . '" method="GET" class="form-inline next">';
                echo '<input type="hidden" name="id" value="' . $artist_id . '">';
                echo '<input type="hidden" name="page" value="' . ($page+1) . '">';
                echo '<input type="submit" value="Next" class="btn">';
                echo '</form>';
              }
              echo '</td>';
              echo '</tr></table>';
            }

            // TODO: Use related artists
            ?>
          </div>
        </td>
        <td class="artist-td">
          <img src="<?php echo $artist_res['images'][0]['url']; ?>" width="200px" style="border-radius: 4px;">
          <br>
          <br>
          <?php
            $data = array('id' => $artist_id, 'name' => $artist_res['name'], 'size' => '30');
            include 'artist_heart.php';
          ?>
          <br>
          <h3 style="text-align: center" class="artist_name"><?php echo $artist_res['name']; ?></h3>
          <?php
            echo '<span class="dark-text">' . number_format($artist_res['followers']['total'], 0, '.', ',') . ' followers</span>';
          ?>
          <br>
          <br>
          <?php 
            foreach($artist_res['genres'] as $genre) {
              echo ucwords($genre) . "<br>";
            }
          ?>
          <br>
          <a class="icon" href="spotify:artist:<?php echo $artist_res['id']; ?>">
            <img src="resources/spotify.png" width="30px" title="Open in Spotify">
          </a>
          <?php
            if ($artist_row['google_id'] != NULL) {
              echo '<a class="icon" href="https://play.google.com/music/listen#/artist/' . $artist_row['google_id'] . '" target="_blank">';
              echo '<img src="resources/google.png" width="30px" title="Open in Google Play Music">';
              echo '</a>';
            }
            if ($artist_row['apple_id'] != NULL) {
              echo '<a class="icon" href="https://music.apple.com/us/artist/' . $artist_row['apple_id'] . '" target="_blank">';
              echo '<img src="resources/apple.png" width="30px" title="Open in Apple Music">';
              echo '</a>';
            }
            if ($artist_row['mb_id'] != NULL) {
              echo '<a class="icon" href="https://musicbrainz.org/artist/' . $artist_row['mb_id'] . '" target="_blank">';
              echo '<img src="resources/mb.png" width="30px" title="Open in Music Brainz">';
              echo '</a>';
            }

            // if ($link_rows == 0) {
            //   echo '<form action="add.php" method="POST">';
            //   echo '<input type="hidden" name="name" value="' . $artist_res['name'] . '">';
            //   echo '<input type="hidden" name="spotify_id" value="' . $artist_res['id'] . '">';
            //   echo '<input type="hidden" name="user_id" value="' . $_SESSION['id'] . '">';
            //   echo '<input type="submit" value="+" class = "btn btn-primary" alt="Add to list">';
            //   echo '</form>';
            // } else {
              
            // }
          ?>
          <br>
          <br>
          <iframe id="follow-frame"
            src="https://open.spotify.com/follow/1/?uri=spotify:artist:<?php echo $artist_id; ?>&size=basic&theme=dark&show-count=0"
            width="92"
            height="35"
            scrolling="no"
            frameborder="0"
            style="border: none; overflow: hidden;"
            allowtransparency="true">
          </iframe>
          <br><br>
          <button type="button" class="collapsible"><b>Related Artists</b></button>
          <div class="collapsible-content">
            <ul class="similar-artists">
              <?php
                $related_res = exec_curl('https://api.spotify.com/v1/artists/' . $artist_id . '/related-artists', $token);
                foreach ($related_res['artists'] as $related) {
                  echo '<li><a href="/artist.php?id=' . $related['id'] . '">';
                  echo '<img src="' . $related['images'][0]['url'] . '" width="80px" height="80px" style="border-radius: 4px;">';
                  echo '</a><br>';
                  echo '<a href="/artist.php?id=' . $related['id'] . '">' . $related['name'] . '<a></li>';
                }
              ?>
            </ul>
          </div>
        </td>
      </tr>
    </table>
    <br>
    <br>
    <div class="center" style="font-size: small">
      Spotify sorts releases for an artist by album type (album, then single, then compilation) then release date<br>
      Some releases may appear multiple times times due to the artist releasing versions in different regions or with explicit content
    </div>
    <?php
      } else {
        echo '<h3>Artist Not Found</h3>';
      }
    ?>
    <?php
      include 'footer.php'
    ?>
  </div>
  <?php include 'playing.php' ?>

  <script>
    var coll = document.getElementsByClassName("collapsible")
    var i

    for (i = 0; i < coll.length; i++) {
      coll[i].addEventListener("click", function() {
        this.classList.toggle("active")
        var content = this.nextElementSibling
        if (content.style.maxHeight) {
          content.style.maxHeight = null
          content.style.border = '1px solid transparent';
        } else {
          content.style.maxHeight = content.scrollHeight + 'px'
          content.style.border = '1px solid'
        }
      })
    }
  </script>
</body>