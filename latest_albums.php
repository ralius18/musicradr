<?php
  session_start();
  require_once('db_control.php');

  $dbcontrol = new DBController();
  $sql = "SELECT a.id, a.name, a.spotify_id, a.latest_album, ul.link_id FROM artists AS a
  LEFT JOIN user_links ul ON a.id = ul.artist_id
  LEFT JOIN users u ON ul.user_id = u.id
  WHERE u.id = " . $_SESSION['id'] . " AND a.is_active IS TRUE AND a.latest_album IS NOT NULL
  ORDER BY 
    CASE 
      WHEN a.name REGEXP '^(A|An|The)[[:space:]]' = 1 THEN 
        TRIM(SUBSTR(a.name , INSTR(a.name ,' '))) 
      ELSE a.name
    END
  ";
  $result = $dbcontrol->runQuery($sql);

  $user_sql = 'SELECT id, country FROM users WHERE id = ' . $_SESSION["id"] . ';';
  $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];
  
  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
  include_once 'auth.php';
  include_once 'curl.php';

  $album_ids = array();
  $albums = array();
  // Spotify API doesn't allow more than 20 ids in this list, so need to split out
  if (count($result) > 20) {
    $chunks = array_chunk($result, 20);

    foreach ($chunks as &$list) {
      $id_string = '';
      foreach ($list as &$artist) {
        if (strlen($artist['latest_album']) > 0){
          if (empty($id_string)) {
            $id_string .= $artist['latest_album'];
          } else {
            $id_string .= ',' . $artist['latest_album'];
          }
          array_push($album_ids, $artist['latest_album']);
        }
      }

      $albums_res = exec_curl('https://api.spotify.com/v1/albums?ids=' . $id_string . '&market=' . $user_country, $token)['albums'];
      foreach ($albums_res as &$album) {
        array_push($albums, $album);
      }
    }
  } else {
    $id_string = '';
    foreach ($result as &$artist) {
      if (empty($id_string)) {
        $id_string .= $artist['latest_album'];
      } else {
        $id_string .= ',' . $artist['latest_album'];
      }
      array_push($album_ids, $artist['latest_album']);
    }

    $albums_res = exec_curl('https://api.spotify.com/v1/albums?ids=' . $id_string . '&market=' . $user_country, $token)['albums'];
    foreach ($albums_res as &$album) {
      array_push($albums, $album);
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Albums</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
  <script type="text/javascript" src="resources/jquery-3.4.1.min.js"></script>
  <script type="text/javascript" src="resources/jquery-ui-1.12.0.min.js"></script>
  <script type="text/javascript" src="main.js"></script>
</head>
<body>
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
  <table>
    <tr>
      <th style="cursor: pointer">Artist</th>
      <th style="cursor: pointer"></th>
      <th style="cursor: pointer">Album</th>
      <th style="cursor: pointer">Release Date</th>
    </tr>
    <?php
      foreach ($albums as &$album) {
        echo '<tr>';
        $artist = $album['artists'][0];
        echo '<td><a href="/artist.php?id' . $artist['id'] . '">' . $artist['name'] . '</a></td>';

        echo '<td><a href="/album.php?id' . $album['id'] . '">';
        echo '<img class="album_art" src="' . $album['images'][0]['url'] . '" width="120px" height="120px" style="border-radius: 4px;">';
        echo '</a></td>';

        $name = $album['name'];
        $length = strlen($name);
        if ($length > 50) {
          $name = substr($name, 0, 25) . '...' . substr($name, $length - 25, 25);
        }
        echo '<td><a href="/artist.php?id' . $album['id'] . '">' . $name . '</a></td>';

        echo '<td>' . $album['release_date'] . '</td>';
        echo '</tr>';
      }
    ?>
  <table>
  <?php
    include 'footer.php'
  ?>
</body>

<script>
  const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent

  const comparer = (idx, asc) => (a, b) => ((v1, v2) => 
    v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2)
    )(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx))

  let th_arr = document.querySelectorAll('th')
  // do the work...
  th_arr.forEach(th => th.addEventListener('click', (() => {
    if (th.cellIndex != 1) {
      const table = th.closest('table')
      th_arr.forEach(th => {
        th.innerHTML = th.innerHTML.replace(/ \u25B2| \u25BC/g, '')
      })
      th.innerHTML += !this.asc ? ' \u25B2' : ' \u25BC';
      // Swaps between asc/desc even if changing columns
      Array.from(table.querySelectorAll('tr:nth-child(n+2)'))
          .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
          .forEach(tr => table.appendChild(tr) )
    }
  })))
</script>