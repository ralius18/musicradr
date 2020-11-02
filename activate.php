<?php
// Include config file
require_once "db_control.php";

if (isset($_GET["id"]) && isset($_GET["activation_code"])){
  $dbcontrol = new DBController();
  $user_sql = "SELECT * FROM users WHERE id = " . $_GET["id"] . " AND activation_code = '" . $_GET['activation_code'] . "';";
  $user_res = $dbcontrol->numRows($user_sql);
  if ($user_res == 1) {
    $update_sql = "UPDATE users SET is_activated = '1', activation_code = NULL
    WHERE id = '" . $_GET["id"] . "' AND activation_code = '" . $_GET["activation_code"] . "'";
    $result = $dbcontrol->updateQuery($update_sql);
    
    # Log in user
    if (!isset($_SESSION)){
      session_start();
    }
    $verify_sql = "SELECT * FROM users WHERE id = " . $_GET["id"];
    $verify_sql = $dbcontrol->runQuery($verify_sql)[0];
    $_SESSION["loggedin"] = true;
    $_SESSION["id"] = $verify_sql["id"];
    $_SESSION["email"] = $verify_sql["email"];
  } else {
    $err = "Could not activate your account";
  }
} else {
  $err = "The activation url is not correct";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Music Radr - Activate</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a> Account Activated
  <br>
  <br>
  <?php if (isset($err)) {
    echo "<p class='wrapper error'>";
    echo $err;
    echo '</p>';
  } else { ?>
  <p class='wrapper added'>
    Thank you for activating your account.<br>
    You will be redirected to the home page in 5 seconds.<br>
    Alternatively, you can <a href="list.php" style="color: #009c22">click here</a> to return to the home page.
  </p>
  <?php } ?>
  <form action="list.php" id="form">
  </form>
  <?php include 'footer.php' ?>
</body>

<!-- Redirect to home page in 5 seconds -->
<script>
  // setTimeout(function () {
  //   document.getElementById("form").submit()
  // }, 5000)
</script>