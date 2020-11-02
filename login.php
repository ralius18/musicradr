<?php
require_once('db_control.php');

$dbcontrol = new DBController();

// Define variables and initialize with empty values
$email = $password = "";
$err = "";
 
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
// Check if email is empty
  if (empty(trim($_POST["email"]))) {
    $err = "Please enter email address";
  } else {
    $email = trim($_POST["email"]);
  }

  // Check if password is empty
  if (empty(trim($_POST["password"]))) {
    $err = "Please enter password";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate credentials
  if (empty($err)) {
    $sql = "SELECT id, email, password, is_activated FROM users WHERE email = '" . $_POST["email"] . "';";
    $result = $dbcontrol->runQuery($sql);
    if ($dbcontrol->numRows($sql) == 1) {
      // User should be activated before logging in
      if ($result[0]["is_activated"] == true) {
        // Verify password
        if (password_verify($password, $result[0]["password"])) {
          if (!isset($_SESSION)){
            session_start();
          }

          $_SESSION["loggedin"] = true;
          $_SESSION["id"] = $result[0]["id"];
          $_SESSION["email"] = $result[0]["email"];  

          // Redirect user to home page
          header("location: list.php");
        } else {
          $err = "Email or password is incorrect"; // Password
        }
      } else {
        $err = "Please activate your account before attempting to log in";
      }
    } else {
      $err = "Email or password is incorrect"; // Email
    }
  }
}

?>
 
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Login</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="content-wrapper">
  <div class="wrapper">
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <h2 class="header">Login</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
<?php if (!empty($err)) { ?><span class="help-block error"><?php echo $err; ?></span><?php } ?>
      <div class="form-group <?php echo (!empty($err)) ? 'has-error' : ''; ?>">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?php echo $email; ?>">
      </div>
      <div class="form-group <?php echo (!empty($err)) ? 'has-error' : ''; ?>">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
        <a href="forgot.php">Forgot your password?</a>
      </div>
      <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Login">
      </div>
      <p>Don't have an account? <a href="register.php">Register here</a></p>
    </form>
  </div>    
</div>
  <?php
    include 'footer.php'
  ?>
</body>
</html>