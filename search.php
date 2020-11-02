<?php
  session_start();
  if (!$_SESSION["loggedin"]) {
    header("location: index.php");
  }
?>

<!DOCTYPE html>
<meta charset="UTF-8"/>
<head>
  <title>Music Radr - Search</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
  <script type="text/javascript" src="main.js"></script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
  <form action="logout.php">
    <input type="submit" value="Logout" style="float: right;" action="logout.php" class="btn">
  </form>
  <form action="list.php">
    <input type="submit" value="Home" style="float: right;" action="list.php" class="btn">
  </form>
  <?php
    include 'color_switch.php';
    echo '<h1>
          <a href="list.php">
            <img src="resources/logo-light.png" width="300px">
          </a><br><br>Results for \'' . $_GET['q'] . '\'</h1><br>';
    // echo '<form action="' . $_SERVER['HTTP_REFERER'] . '"><input type="submit" value="Back" class="btn"></form><br><br>';
    $ini_arr = parse_ini_file("private/config.ini");
    $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
    // $auth_cmd = 'curl -X "POST" -H "Authorization: Basic ' . base64_encode($id_secret) . '" -d grant_type=client_credentials https://accounts.spotify.com/api/token';

    // Get Auth token
    include_once 'auth.php';
    include_once 'curl.php';

    $formatted_artist = urlencode(strtolower($_GET['q']));
    // $search_cmd = 'curl -X "GET" -H "Authorization: Bearer ' . $token . '" "https://api.spotify.com/v1/search?type=artist&q=' . $formatted_artist . '"';
    // Search for artists

    $arr = exec_curl('https://api.spotify.com/v1/search?type=artist&q='. $formatted_artist, $token)['artists']['items'];

    echo '
      <form action="search.php" method="GET">
        <div class="input-group search-bar">
          <input type="text" name="q" id="artistname" class="form-control" max-width="100px" required=true autofocus autocomplete="off" value="' . $_GET['q'] . '">
          <div class="input-group-btn">
            <input type="submit" class="btn btn-primary" value="Search">
          </div>
        </div>
      </form>
      <br>
    ';

    echo '<table>';
    echo '<tr>';
    // Can sort by popularity, but best match seems better
    // usort($arr, function($a, $b) { return $b['popularity'] - $a['popularity']; });
    // TODO: Add ids to elements for hearts
    foreach ($arr as &$value) {
      $url = 'artist.php?id=' . $value['id'];
      echo '<td>';
      if (sizeof($value["images"]) > 0){
        echo '<a href="' . $url . '">';
        echo '<img src="' . $value["images"][0]["url"] . '" width="120px" height="120px" style="border-radius: 4px;">';
        echo '</a>';
      }
      echo '</td>';
      echo '<td><a href="' . $url . '" class="artist_name">' . $value['name'] . '</a></td>';
      echo '<td>';
      foreach ($value['genres'] as $i=>$genre) {
        if ($i !== 0) {
          echo '<br>';
        }
        echo ucwords($genre);
      }
      echo '</td>';
      echo '<td>';
      $data = array('id' => $value['id'], 'name' => $value['name'], 'size' => '20');
      include 'artist_heart.php';
      echo '</td>';
      echo '</tr>';
    }
    echo '</table>';
  ?>
  <br>
  
  <?php
    include 'footer.php'
  ?>
</body>