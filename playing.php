<?php

require_once 'auth.php';
require_once 'curl.php';
require_once 'db_control.php';

$dbcontrol = new DBController();
$user_sql = 'SELECT * FROM users WHERE id = ' . $_SESSION["id"] . ';';
$user = $dbcontrol->runQuery($user_sql)[0];

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

# If linked to account and show playing option enabled
if (strlen($user['spotify_access']) > 0) {
  auth_user($user['id']);
  $access_res = exec_curl('https://api.spotify.com/v1/me/player', $user['spotify_access']);

  $item = $access_res['item'];
  // /currently-playing
  if (!$item) {
    $item = exec_curl('https://api.spotify.com/v1/me/player/recently-played', $user['spotify_access'])['items'][0]['track'];
  }
  # Display bottom bar
  # TODO: Hide/show button
  if ($item) {
    echo '<script>$(".body").css("padding-bottom", "10%")</script>';
    echo '<div class="show-player">';
    echo '  <button id="show-player-btn" class="btn btn-primary" onclick="showPlayer()">Show Player</button>';
    echo '</div>';
    echo '<div class="playing">';
    // var_dump($res);
    echo '  <div class="playing-left">';
    // echo '    <a href="/album?id=' . $item['album']['id'] . '">';
    echo '      <img src="' . $item['album']['images'][0]['url'] . '" height=100%>';
    // echo '    </a>';
    echo '    <div class="playing-text">';
    // TODO: Link track album
    echo '      <b><a class="playing-title" href="/album?id=' . $item['album']['id'] . '">' . $item['name'] . '</a></b><br>';
    // TODO: Link each artist
    echo '      ' . implode(',', array_map(function($artist) {
      return '<a href="/artist.php?id=' . $artist['id'] . '">' . $artist['name'] . '</a>';
    }, $item['artists'])) . '<br>';
    // echo '      <a href="album.php?id=' . $item['album']['id'] . '">' . $item['album']['name'] . '<a>';
    ?>
        </div>
      </div>
      <div class="playing-center">
        <div class="player-stuff">
          <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30" height="30" viewBox="0 0 16 16">
            <path fill="#FFF" d="M8 0c-4.418 0-8 3.582-8 8s3.582 8 8 8 8-3.582 8-8-3.582-8-8-8zM8 14.5c-3.59 0-6.5-2.91-6.5-6.5s2.91-6.5 6.5-6.5 6.5 2.91 6.5 6.5-2.91 6.5-6.5 6.5zM6 4.5l6 3.5-6 3.5z"></path>
          </svg><br>
          <?php
          if ($access_res['progress_ms']) {
            $progress = format_ms($access_res['progress_ms']);
            echo $progress;
            echo '<div id="progress"></div>';
            echo format_ms($item['duration_ms']);
          } else {
            echo '<div id="progress"></div>';
          }
          ?>
        </div>
      </div>
      <div class="playing-right">
        <button id="hide-player" class="btn btn-primary" onclick="hidePlayer()" style="position: absolute; right: 0">Hide Player</button>
      </div>
    </div>
  <?php
  }
}

?>