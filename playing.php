<?php

require_once 'curl.php';
require_once 'db_control.php';

$dbcontrol = new DBController();
$user_sql = 'SELECT * FROM users WHERE id = ' . $_SESSION["id"] . ';';
$user = $dbcontrol->runQuery($user_sql)[0];

# If linked to account and show playing option enabled
if (strlen($user['spotify_access']) > 0) {
  echo '<script>$(".body").css("padding-bottom", "10%")</script>';
  $item = exec_curl('https://api.spotify.com/v1/me/player', $user['spotify_access'])['item'];
  // /currently-playing
  if (!$item) {
    $item = exec_curl('https://api.spotify.com/v1/me/player/recently-played', $user['spotify_access'])['items'][0]['track'];
  }
  # Display bottom bar
  # TODO: Hide/show button
  echo '<div class="playing">';
  // var_dump($res);
  echo '  <div class="playing-left">';
  echo '    <img src="' . $item['album']['images'][0]['url'] . '" height=100%>';
  echo '    <div class="playing-text">';
  // TODO: Link track album
  echo '      <b>' . $item['name'] . '</b><br>';
  // TODO: Link each artist
  echo '      ' . implode(',', array_map(function($artist) {
    return '<a href="/artist.php?id=' . $artist['id'] . '">' . $artist['name'] . '</a>';
  }, $item['artists']));
  echo '    </div>';
  echo '  </div>';
  echo '  <div class="playing-center>';
  echo '  </div>';
  echo '  <div class="playing-right>';
  echo '  </div>';
  echo '</div>';
}

?>