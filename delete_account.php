<?php
  require_once("db_control.php");
  
  session_start();

  if (!$_SESSION["loggedin"]) {
    header("location: index.php");
  }

  $dbcontrol = new DBController();
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_SESSION['id'])) {
      $links_sql = "DELETE FROM user_links WHERE user_id = " . $_SESSION['id'];
      $links_res = $dbcontrol->deleteQuery($links_sql);

      $user_sql = "DELETE FROM users WHERE id = " . $_SESSION['id'];
      $user_res = $dbcontrol->deleteQuery($user_sql);

      $message = "Your account has been deleted";
      $type = 'added';
      session_destroy();
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Music Radr - Delete Account</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a>
  <h1>Account Deleted</h1>
  <?php
      if(isset($message)) {
    ?>
      <div class="wrapper <?php echo $type; ?>"><?php echo $message; ?></div>
  <?php
    }
  include 'footer.php';
  ?>
</body>