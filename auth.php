<?php

  require_once ('db_control.php');

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];

  $ch_auth = curl_init();
  curl_setopt($ch_auth, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
  curl_setopt($ch_auth, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch_auth, CURLOPT_POST, 1);
  curl_setopt($ch_auth, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
  $headers = array();
  $headers[] = 'Authorization: Basic ' . base64_encode($id_secret);
  $headers[] = 'Content-Type: application/x-www-form-urlencoded';
  curl_setopt($ch_auth, CURLOPT_HTTPHEADER, $headers);
  $auth_result = curl_exec($ch_auth);
  if (curl_errno($ch_auth)) {
      echo 'Error:' . curl_error($ch_auth);
  }
  curl_close($ch_auth);

  $token = json_decode($auth_result, true)["access_token"];

  function auth_user($id) {
    $dbcontrol = new DBController();
    $user_sql = "SELECT email, spotify_access, spotify_refresh, spotify_expires FROM users WHERE id = '" . $id . "';";
    $user = $dbcontrol->runQuery($user_sql)[0];

    $ini_arr = parse_ini_file("private/config.ini");
    $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];

    $isAvailable = strlen($user['spotify_access']) > 0;

    if (! $isAvailable) {
      # not authorised
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
        WHERE id = " . $id . ";";
        $sql_res = $dbcontrol->updateQuery($sql);
      }
    } else {
      // already authorised, check access is ok, refresh if needed
      // $access_res = exec_curl('https://api.spotify.com/v1/me/player', $user['spotify_access']);
      $token_expired = new DateTime($user['spotify_expires']) < new DateTime();
      if ($token_expired) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://accounts.spotify.com/api/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = array();
        $headers[] = 'Authorization: Basic ' . base64_encode($id_secret);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $body = 'grant_type=refresh_token&refresh_token=' . $user['spotify_refresh'];
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
          spotify_expires = '" . $expires . "'
          WHERE id = " . $id . ";";
          $sql_res = $dbcontrol->updateQuery($sql);
        }
      }
    }
  }
?>