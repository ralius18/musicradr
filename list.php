<?php
  session_start();
  require_once('db_control.php'); 

  $dbcontrol = new DBController();
  $sql = "SELECT a.name, a.spotify_id, ul.link_id FROM artists AS a
  LEFT JOIN user_links ul ON a.id = ul.artist_id
  LEFT JOIN users u ON ul.user_id = u.id
  WHERE u.id = " . $_SESSION['id'] . " AND a.is_active IS TRUE
  ORDER BY 
    CASE 
      WHEN a.name REGEXP '^(A|An|The)[[:space:]]' = 1 THEN 
        TRIM(SUBSTR(a.name , INSTR(a.name ,' '))) 
      ELSE a.name
    END
  ";
  $result = $dbcontrol->runQuery($sql);
  // TODO: Handle special characters
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr</title>
  <script type="text/javascript" src="main.js"></script>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
  <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
</head>

<script>
  // Remove form resubmit on page refresh
  if ( window.history.replaceState ) {
      window.history.replaceState( null, null, window.location.href );
  }
</script>

<body><!-- TODO: Make login a popup window -->
  <div class='body'>
    <?php

      if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        echo '<form action="login.php">
            <input type="submit" value="Login" style="float: right;" action="login.php" class="btn">
          </form>
          <form action="register.php">
            <input type="submit" value="Register" style="float: right;" action="register.php" class="btn">
          </form>';
      } else {
        echo '<form action="logout.php">
            <input type="submit" value="Logout" style="float: right;"class="btn">
          </form>
          <form action="settings.php">
            <input type="submit" value="Settings" style="float: right;"class="btn">
          </form>
          <form action="import_export.php">
            <input type="submit" value="Import/Export" style="float: right;"class="btn">
          </form>
          <form action="albums.php">
            <input type="submit" value="Albums" style="float: right;"class="btn">
          </form>';
        include 'color_switch.php';
      }
    ?>
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <br>
    <?php
      if (array_key_exists('added', $_POST)) {
        echo '<br><p><span class="added">Added ' . $_POST['added'] . '</span><p>';
      } else if (array_key_exists('removed', $_POST)) {
        $plural = ($_POST['removed'] == 1) ? ' artist' : ' artists';
        echo '<br><p><span class="removed">Removed ' . $_POST['removed'] . $plural . '</span><p>';
      } else if (array_key_exists('error', $_POST)) {
        echo '<br><p><span class="error">' . $_POST['error'] . '</span><p>';
      }
    ?>
    <br>
    <?php
      if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        echo '
          <form action="search.php" method="GET">
            <div class="input-group search-bar">
              <input type="text" name="q" id="artistname" class="form-control" max-width="100px" required=true autofocus autocomplete="off">
              <div class="input-group-btn">
                <input type="submit" class="btn btn-primary" value="Search">
              </div>
            </div>
          </form>
          <br>
        ';
        echo '<table style="width: 100%">';
        echo '<tr>';
        echo '<td style="border: none">';
        echo '<h4 style="font-weight: bold">Current Artists (' . $dbcontrol->numRows($sql) . ')</h4>';
        echo '</td>';
        echo '<td style="text-align: right; border: none">';
        echo '<button onclick=randomArtist() class="btn">Random Artist</button>';
        echo '</td>';
        echo '</table>';
        echo '<hr>';
        if (count($result) > 0) {
          echo '<ul>';
          echo '<form action="remove.php" method="POST" id="removeForm">';
          foreach ($result as $i=>$artist) {
            echo '<li>';
            echo '<label><input class="check" type="checkbox" name="artist' . $i . '" value="' . $artist["link_id"] . '">';
            echo '<a class="artist" href="/artist.php?id=' . $artist["spotify_id"] . '">' . $artist["name"] . '</a></label>';
            // echo '<span class="checkbox">' . $artist["name"] . '</span></label>';
            echo '</li>';
          }
          echo '</ul>';
          echo '<br>';
          echo '<br>';
          echo '<input type="button" value="Remove Selected Artists" class="btn" onclick="confirmRemove()">';
          echo '<input type="reset" value="Clear Selection" style="padding: \'0 0 0 100\'" class="btn">';
          echo '<button type="button" onclick="selectAll()" id="select-button" class="btn">Select All</button>';
          echo '</form>';
        } else {
          echo '<p>Search for an artist to begin creating your list</p>';
        }
      } else {
        echo "<br>Please log in or register to create a list"; // TODO: Explanation about site
      }
      include 'footer.php';
      ?>

    <script>
      function selectAll() {
        var checks = document.getElementsByClassName('check')
        for (i = 0; i < checks.length; i++) {
          checks[i].checked = true;
        }
      }
      function confirmRemove() {
        var checks = document.getElementsByClassName('check')
        var checked = []
        for (i = 0; i < checks.length; i++) {
          if (checks[i].checked){
            checked.push(checks[i])
          }
        }
        var plural = checked.length > 1 ? ' artists?' : ' artist?'
        if (checked.length > 0) {
          var res = confirm("Are you sure you want to remove " + checked.length + plural)
          if (res == true) {
            document.getElementById('removeForm').submit()
          }
        }
      }
      function randomArtist() {
        var artists = document.querySelectorAll('a.artist')
        var random = artists[Math.floor(Math.random() * artists.length)]

        window.location = random.href
      }
    </script>
  </div>

<?php include 'playing.php' ?>
</body>
</html>