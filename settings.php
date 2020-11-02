<?php
  require_once("db_control.php");
  
  session_start();

  if (!$_SESSION["loggedin"]) {
    header("location: index.php");
  }

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
  $dbcontrol = new DBController();
  if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $message = 'Changes have been saved';
    $type = "success";

    if ($_POST["update-type"] == "notifications") {
      # Handle Notification updates
      $albums_update = isset($_POST["noti-album"]) ? 1 : 0;
      $singles_update = isset($_POST["noti-single"]) ? 1 : 0;
      $comps_update = isset($_POST["noti-compilation"]) ? 1 : 0;
      $noti_update_sql = "UPDATE users SET 
                          album_notifications = '" . $albums_update . "',
                          single_notifications = '" . $singles_update . "',
                          comp_notifications = '" . $comps_update . "'
                          WHERE id = " . $_SESSION["id"] . ";";
      $update_res = $dbcontrol->updateQuery($noti_update_sql);
    } else if ($_POST["update-type"] == "password") {
      # Validate password change
      $pass_err = "";
      $pass_sql = "SELECT id, password FROM users WHERE id = " . $_SESSION["id"] . ";";
      $pass_res = $dbcontrol->runQuery($pass_sql);
      
      if (password_verify($_POST["old-pass"], $pass_res[0]["password"])) {
        $new_pass = trim($_POST["new-pass"]);
        $conf_pass = trim($_POST["conf-pass"]);
        if (strlen($new_pass) >= 8) {
          if ($new_pass == $conf_pass) {
            # Passes all checks
            $pass_update_sql = "UPDATE users SET
                                password = '" . password_hash($new_pass, PASSWORD_DEFAULT) . "'
                                WHERE id = " . $_SESSION["id"] . ";";
            $pass_update_res = $dbcontrol->updateQuery($pass_update_sql);
          } else {
            $message = 'Passwords did not match';
            $type = 'failure';
          }
        } else {
          $message = 'New passwords must be longer than 8 characters long';
          $type = 'failure';
        }
      } else {
        $message = 'Old password was not correct';
        $type = 'failure';
      }
          
    } else if ($_POST["update-type"] == "country") {
      $country_update_sql = 'UPDATE users SET
                            country = "' . $_POST['country'] . '"
                            WHERE id = ' . $_SESSION["id"] . ';';
      $country_update_res = $dbcontrol->updateQuery($country_update_sql);
    }

  }

  if (array_key_exists('code', $_GET)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    $headers = array();
    $headers[] = 'Authorization: Basic ' . base64_encode($id_secret);
    $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $redirect = 'http://musicradr/settings.php';
    $body = 'grant_type=authorization_code&redirect_uri=' . urlencode($redirect) . '&code=' . $_GET['code'];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    $result = json_decode(curl_exec($ch), true);

    if (curl_errno($ch)) {
      $message = curl_error($ch);
      $type = 'failure';
    } else if (array_key_exists('error', $result)) {
      curl_close($ch);
      var_dump($result);
      $type = 'failure';
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
    }
  }

  $noti_sql = "SELECT album_notifications, single_notifications, comp_notifications
              FROM users WHERE id = " . $_SESSION["id"] . ";";
  $noti_res = $dbcontrol->runQuery($noti_sql);
  $noti_album = $noti_res[0]["album_notifications"] == 1 ? true : false;
  $noti_single = $noti_res[0]["single_notifications"] == 1 ? true : false;
  $noti_comp = $noti_res[0]["comp_notifications"] == 1 ? true : false;

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
  <title>Music Radr - Settings</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <form action="logout.php">
    <input type="submit" value="Logout" style="float: right;" action="logout.php" class="btn">
  </form>
  <form action="list.php">
    <input type="submit" value="Home" style="float: right;" action="list.php" class="btn">
  </form>
  <?php include 'color_switch.php'; ?>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a>
  <h1>
    Settings
  </h1>
  <!-- TODO: Add check for message type -->
  <?php
    if (isset($message)) {
      echo '<div class="added" style="text-align: center; width: 300px; margin: auto;">' . $message . '</div>';
    }
  ?>
  <div class="wrapper">
    <h3 class="header">Link Spotify Account</h3><br>
    <form action="https://accounts.spotify.com/authorize" method="GET">
        <input type="hidden" name="client_id" value="<?php echo $ini_arr["client_id"]; ?>">
        <input type="hidden" name="response_type" value="code">
        <input type="hidden" name="redirect_uri" value="http://musicradr/settings.php">
        <input type="hidden" name="scope" value="user-follow-read%20user-read-currently-playing%20user-read-playback-state%20user-modify-playback-state%20user-read-recently-played">
        <!-- <input type="hidden" name="show_dialog" value="true"> -->
      <div class="form-group">
        <!-- TODO: Change if already linked -->
        <input type="submit" class="btn btn-primary" value="Login to Spotify">
      </div>
    </form>
  </div>
  <hr>
  <div class="wrapper">
    <h3 class="header">Change Password</h3>
    <form action="settings.php" method="POST">
      <input type="hidden" name="update-type" value="password">
      <div class="form-group">
        <label for="old-pass">Old Password</label>
        <input type="password" class="form-control" id="old-pass" name="old-pass" required>
      </div>
      <div class="form-group">
        <label for="new-pass">New Password</label>
        <input type="password" class="form-control" id="new-pass" name="new-pass" required>
      </div>
      <div class="form-group">
        <label for="conf-pass">Confirm Password</label>
        <input type="password" class="form-control" id="conf-pass" name="conf-pass" required>
      </div>
      <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Save">
      </div>
    </form>
  </div>
  <hr>
  <h3 class="header">Release Types</h3>
  <div class="wrapper">
    <p>Select what types of releases you would like to be notified about</p>
    <br>
    <form action="settings.php" method="POST">
      <input type="hidden" name="update-type" value="notifications">
      <div class="form-group">
        <ul style="column-count: 3;">
          <label>
            <input type="checkbox" name="noti-album" class="form-control hidden" id="noti-album" <?php if ($noti_album) { ?>checked<?php } ?>>
            <span class="checkbox">Album</span>
          </label>
          <label>
            <input type="checkbox" name="noti-single" class="form-control hidden" id="noti-single" <?php if ($noti_single) { ?>checked<?php } ?>>
            <span class="checkbox">Single</span>
          </label>
          <label>
            <input type="checkbox" name="noti-compilation" class="form-control hidden" id="noti-compilation" <?php if ($noti_comp) { ?>checked<?php } ?>>
            <span class="checkbox">Compilation</span>
          </label>
        </ul>
        <br>
        <input type="submit" class="btn btn-primary" value="Save">
      </div>
    </form>
  </div>
  <hr>
  <h3 class="header">Country</h3>
  <div class="wrapper">
    <form action="settings.php" method="POST">
      <input type="hidden" name="update-type" value="country">
      <select name="country" class="form-control">
        <?php
          $country_sql = "SELECT * FROM countries ORDER BY name;";
          $countries = $dbcontrol->runQuery($country_sql);
          $user_sql = "SELECT country FROM users WHERE id = " . $_SESSION['id'];
          $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];
          foreach ($countries as &$c) {
            $option = '<option value="' . $c['code'] . '"';
            if ($c['code'] == $user_country) {
              $option .= ' selected';
            }
            $option .= '>' . $c['name'] . '</option>';
            echo $option;
          }
        ?>
      </select>
      <br>
      <p>Only countries
        <a href="https://support.spotify.com/us/using_spotify/getting_started/full-list-of-territories-where-spotify-is-available/" target="_blank">
        available on Spotify</a> are listed
      </p>
      <input type="submit" class="btn btn-primary" value="Save" style="display: block; margin: auto;">
    </form>
  </div>
  <hr>
  <!-- <h3 class="header">Link Accounts</h3>
  <div class="wrapper">
    <p>Coming soon!<p>
  </div>
  <hr> -->
  <h3 class="header">Delete Account</h3>
  <div class="wrapper">
    <form action="delete_account.php" id="delete-account" method="POST">
      <p>This will remove all artists you are following and all settings will be deleted</p>
      <p>I highly recommend exporting your current list so you can import it if you want to register again</p>
      <button type="button" class="btn btn-secondary" onclick="confirmDelete()">Delete Account</button>
    </form>
  </div>
  <?php
    include 'footer.php'
  ?>
</body>

<script>
  function confirmDelete() {
    var str = 'Are you sure you want to delete your account?'
    if (confirm(str)) {
      document.getElementById('delete-account').submit()
    }
  }
</script>