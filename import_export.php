<?php
  session_start();

  if (!$_SESSION["loggedin"]) {
    header("location: index.php");
  }
  
  include_once('db_control.php'); 

  $ini_arr = parse_ini_file("private/config.ini");

  // Generate content to write to file
  $dbcontrol = new DBController();
  $link_sql = "SELECT a.name, a.spotify_id, ul.link_id FROM artists AS a
              LEFT JOIN user_links ul ON a.id = ul.artist_id
              LEFT JOIN users u ON ul.user_id = u.id
              WHERE u.id = " . $_SESSION['id'] . " AND a.is_active IS TRUE
              ORDER BY name";
  $link_res = $dbcontrol->runQuery($link_sql);
  $file_content = '';
  foreach ($link_res as &$artist) {
    $file_content .= $artist['spotify_id'] . '\n';
  }

  function addArtists($arr) {
    $dbcontrol = new DBController();
    $count = 0;
    $err_count = 0;
    foreach($arr as &$spotify_id) {
      if (preg_match('/^[A-Za-z0-9]*$/', $spotify_id)) {
        $artist_sql = "SELECT id, spotify_id FROM artists WHERE spotify_id = '" . $spotify_id . "'";
        $artist_res = $dbcontrol->runQuery($artist_sql);
        if (is_array($artist_res)) {
          // Exists in DB already
          $artist_id = $artist_res[0]['id'];
        } else {
          // Check it's a valid ID
          $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
          $auth_cmd = "curl -X 'POST'\
                      -H 'Authorization: Basic " . base64_encode($id_secret) . "'\
                      -d grant_type=client_credentials\
                      https://accounts.spotify.com/api/token";
          $token = json_decode(shell_exec($auth_cmd), true)["access_token"];
          $artist_cmd = "curl -X GET\
                        -H 'Authorization: Bearer " . $token . "'\
                        'https://api.spotify.com/v1/artists/" . trim($spotify_id) . "'";
          $artist_cmd_res = json_decode(shell_exec($artist_cmd), true);
          // If not, return error
          if (isset($artist_cmd_res['error'])) {
            $import_error = $artist_cmd_res['error']['message'];
            $err_count++;
            continue;
          } else {
            // Else add into DB
            $artist_add_sql = "INSERT IGNORE INTO artists
                              SET spotify_id = '" . $spotify_id . "',
                              name = '" . $artist_cmd_res['name'] . "'";
            $artist_add_res = $dbcontrol->insertQuery($artist_add_sql);
            $new_artist_res = $dbcontrol->runQuery($artist_sql);
            $artist_id = $new_artist_res[0]['id'];
          }
        }
        $add_sql = 'INSERT IGNORE INTO user_links
                    SET artist_id = "' . $artist_id . '",
                    user_id = "' . $_SESSION["id"] . '";';
        $add_res = $dbcontrol->insertQuery($add_sql);
        $count++;
      } else {
        $err_count++;
      }
    }
    return array('count' => $count, 'err_count' => $err_count);
  }

  // Import process
  $import_error = '';

  if (isset($_POST['submit'])) {
    $filename = $_FILES['importFile']['tmp_name'];
    if (mime_content_type($filename) == 'text/plain') {
      $contents = file_get_contents($filename);
      $arr = explode("\n", $contents);
      $add_res = addArtists($arr);
      $message = $add_res['count'] . " artists successfully added";
      if ($add_res['err_count'] > 0) {
        $message .= "<br>" . $add_res['err_count'] . " artists could not be added";
      }
      $type = 'added';
    } else {
      $message = 'File is not the correct format';
      $type = 'error';
    }
  } else if (isset($_POST['submit_type']) && $_POST['submit_type'] == 'spotify') {
    $to_add = array();
    foreach($_POST as $key=>$value) {
      if (substr($key, 0, 6) === 'artist') {
        array_push($to_add, $value);
      }
    }
    $add_res = addArtists($to_add);
    $message = $add_res['count'] . " artists successfully added";
    if ($add_res['err_count'] > 0) {
      $message .= "<br>" . $add_res['err_count'] . " artists could not be added";
    }
    $type = 'added';
  }
?>

<script>
  // Remove form resubmit on page refresh
  if ( window.history.replaceState ) {
      window.history.replaceState( null, null, window.location.href );
  }
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>&#127925; Radr - Import & Export</title>
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
  <h1>Import & Export</h1>
  <div class="wrapper">
    <?php
      if (isset($message)) {
        echo "<div class='" . $type . " center'>" . $message . "</div><br>";
      }
    ?>
    <h3 class="header">Link Spotify Account</h3><br>
    <form action="https://accounts.spotify.com/authorize" method="GET">
        <input type="hidden" name="client_id" value="<?php echo $ini_arr["client_id"]; ?>">
        <input type="hidden" name="response_type" value="code">
        <input type="hidden" name="redirect_uri" value="http://musicradr/link_spotify.php">
        <input type="hidden" name="scope" value="user-follow-read">
        <!-- <input type="hidden" name="show_dialog" value="true"> -->
      <div class="form-group">
        <!-- TODO: Change if already linked -->
        <input type="submit" class="btn btn-primary" value="Login to Spotify">
      </div>
    </form>
  </div>
  <hr>
  <div class="wrapper">
    <h3 class="header">Import</h3>
    <br>
    <p>
      The file should be a plain .txt file with a list of Spotify artist IDs, one artist ID per line
    <form action="import_export.php" method="POST" enctype="multipart/form-data">
      <div id="filename" class="center" style="color: #999">Select a file</div>
      <br>
      <input type="hidden" name="type" value="import">
      <label class="btn fake-btn">
        Browse<input type="file" name="importFile" hidden style="display: none !important" onchange="selectFile(this)">
      </label>
      <br>
      <div id="submit-div"></div>
    </form>
    <hr>
    <h3 class="header">Export</h3>
    <br>
    <button type="button" id="export" class="btn btn-primary">Export to file</button>
  </div>
  <?php
    include 'footer.php'
  ?>
</body>

<script>
  function selectFile(file) {
    var submitDiv = document.getElementById("submit-div")
    var submitBtn = document.getElementById("submit-btn")
    if (file.files[0].size < 1000000) {
      var filename = file.value
      document.getElementById("filename").innerHTML = filename.substr(filename.lastIndexOf('\\')+1)
      document.getElementById("filename").style = 'color: #999'
      // Add Import button
      if (submitBtn == null) {
        var element = document.createElement("INPUT")
        element.setAttribute('type', 'submit')
        element.setAttribute('value', 'Import from file')
        element.setAttribute('class', 'btn btn-primary')
        element.setAttribute('name', 'submit')
        element.setAttribute('id', 'submit-btn')
        submitDiv.appendChild(element)
      }
    } else {
      document.getElementById("filename").innerHTML = 'Cannot upload files bigger than 1 MB'
      document.getElementById("filename").style = "color: #b62d2d"
      if (submitBtn != null) {
        submitDiv.removeChild(submitDiv.firstChild)
      }
    }
  }

  function download(filename, content) {
    var element = document.createElement('a')
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content))
    element.setAttribute('download', filename);
    element.style.display = 'none'
    document.body.appendChild(element)

    element.click()

    document.body.removeChild(element)
  }

  document.getElementById("export").addEventListener("click", function(){
    var filename = "musicradr_artists.txt"
    var content = "<?php echo $file_content; ?>"
    
    download(filename, content)
  }, false)
</script>