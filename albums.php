<?php
  require_once('db_control.php');

  session_start();
  $dbcontrol = new DBController();

  $sql = 'SELECT * FROM album_links WHERE user_id = ' . $_SESSION['id'] . ' ORDER BY sort_order;';
  $res = $dbcontrol->runQuery($sql);

  $ini_arr = parse_ini_file("private/config.ini");
  $id_secret = $ini_arr["client_id"] . ":" . $ini_arr["client_secret"];
  include_once 'auth.php';
  include_once 'curl.php';

  $user_sql = 'SELECT id, country FROM users WHERE id = ' . $_SESSION["id"] . ';';
  $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];

  $album_ids = array();
  $albums = array();
  // Spotify API doesn't allow more than 20 ids in this list, so need to split out
  if (count($res) > 20) {
    $chunks = array_chunk($res, 20);

    foreach ($chunks as &$list) {
      $id_string = '';
      foreach ($list as &$album) {
        if (empty($id_string)) {
          $id_string .= $album['album_id'];
        } else {
          $id_string .= ',' . $album['album_id'];
        }
        array_push($album_ids, array('album_id' => $album['album_id'], 'sort_order' => $album['sort_order'], 'link_id' => $album['link_id']));
      }

      $albums_res = exec_curl('https://api.spotify.com/v1/albums?ids=' . $id_string . '&market=' . $user_country, $token)['albums'];
      foreach ($albums_res as &$album) {
        $matching_link = search_by_id($album['id'], $album_ids);
        $album['sort_order'] = $matching_link['sort_order'];
        $album['link_id'] = $matching_link['link_id'];
        array_push($albums, $album);
      }
    }
  }


  usort($albums_res, 'cmp');

  function search_by_id($album_id, $array) {
    foreach ($array as $key => $val) {
      // var_dump($album_id, $val, $key);
      if ($val['album_id'] === $album_id) {
        return $val;
      }
    }
    return null;
  }

  function cmp($a, $b) {
    return strcmp($a['sort_order'], $b['sort_order']);
  }

  function format_ms($time) {
    $mss = $time % 1000;
    $time = floor($time / 1000);
    $secs = $time % 60;
    $time = floor($time / 60);
    $mins = $time % 60;
    $time = floor($time / 60);
    $hrs = $time % 60;
    $time_str = str_pad($mins, 2, '0', STR_PAD_LEFT) . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
    if ($hrs > 0) {
      $time_str = $hrs . ':' . $time_str;
    }
    return $time_str;
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
  <div>
    <label>
      <input id='show_art_checkbox' type="checkbox" class="form-control hidden" onchange='toggleAlbumArt()' checked='true'>
      <span class="checkbox">Show Album Art</span>
    </label>
    <div class='center save-reset'>
    </div>
  </div>
  <table class="albums_table sortable_table">
    <tr>
      <th>Rank</th>
      <th></th>
      <th>Name</th>
      <th>Artists</th>
      <th>Type</th>
      <th>Release Date</th>
      <th>Length</th>
      <th>Tracks</th>
    </tr>
    <?php
      foreach($albums as &$album) {
        $album_url = 'album.php?id=' . $album['id'];
        echo '<tr class="album_row" id="' . $album['link_id'] . '">';

        // TODO: Remove album heart (and confirm)

        echo '<td class="rank">';
        echo $album['sort_order'];
        echo '</td>';

        echo '<td>';
        echo '<a href=' . $album_url . '>';
        echo '<img class="album_art" src="' . $album["images"][0]["url"] . '" width="120px" height="120px" style="border-radius: 4px;">';
        echo '</a>';
        echo '</td>';

        echo '<td class="album">';
        echo '<a href=' . $album_url . '>' . $album['name'] . '</a>';
        echo '</td>';

        echo '<td class="album">';
        foreach ($album['artists'] as &$artist) {
          $artist_url = 'artist.php?id=' . $artist['id'];
          echo '<a href=' . $artist_url . '>' . $artist['name'] . '</a><br>';
        }
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
  
        $num = 0;
        $length = 0;
        foreach($album['tracks']['items'] as &$track) {
          $length += $track['duration_ms'];
          $num += 1;
        }

        echo '<td class="album">';
        echo format_ms($length);
        echo '</td>';

        echo '<td class="album">';
        echo $num;
        echo '</td>';

        echo '</tr>';
      }
    ?>
  </table>
  <br>
  <br>
  <div class='center save-reset'>
  </div>
  <?php
    include 'footer.php'
  ?>
  <script>
    var fixHelperModified = function(e, tr) {
      var $originals = tr.children(),
          $helper = tr.clone()
      $helper.children().each(function(index) {
        $(this).width($originals.eq(index).width())
      })
      return $helper
    },
    updateIndex = function(e, ui) {
      $('td.rank', ui.item.parent()).each(function(i) {
        $(this).html(i + 1)
      })
      var div = $('.save-reset'),
          buttons = "<div id='buttons'>\
                    <button class='btn btn-primary' onclick='update_album_order()'>Save</button>\
                    <button class='btn' onclick='reset_album_order()'>Reset</button>\
                    </div>"
      if (div.find('#buttons').length === 0){
        div.append(buttons)
      }
    }

    $(".sortable_table tbody").sortable({
      helper: fixHelperModified,
      stop: updateIndex,
      placeholder: 'placeholder',
      opacity: 0.5,
      distance: 2
    }).disableSelection()
  </script>
</body>
