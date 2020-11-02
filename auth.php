<?php
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
?>