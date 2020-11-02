<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr</title>
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

<body>
  <!-- TODO: Make login a popup window -->
  <?php

    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
      echo '<form action="login.php">
          <input type="submit" value="Login" style="float: right;" action="login.php" class="btn">
        </form>
        <form action="register.php">
          <input type="submit" value="Register" style="float: right;" action="register.php" class="btn">
        </form>';
    } else {
      header("location: list.php");
      // echo '<form action="logout.php">
      //     <input type="submit" value="Logout" style="float: right;"class="btn">
      //   </form>
      //   <form action="settings.php">
      //     <input type="submit" value="Settings" style="float: right;"class="btn">
      //   </form>
      //   <form action="import_export.php">
      //     <input type="submit" value="Import/Export" style="float: right;"class="btn">
      //   </form>';
    }
  ?>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a>
  <br>
  <br>

  <!-- TODO: Add description for home page -->
  <?php include 'about_details.php'; ?>

  <?php
    include 'footer.php';
  ?>
  
</body>
</html>
