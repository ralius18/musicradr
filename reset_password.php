<?php
  include_once('db_control.php');
  $dbcontrol = new DBController();

  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['new-pass']) && isset($_POST['conf-pass']) && isset($_POST['id']) && isset($_POST['activation_code'])) {
      if ($_POST['new-pass'] == $_POST['conf-pass']) {
        if (strlen($_POST['new-pass']) >= 8) {
          $user_sql = "SELECT id, activation_code FROM users WHERE id = " . $_POST['id'] . " AND activation_code = '" . $_POST['activation_code'] . "';";
          $user_res = $dbcontrol->numRows($user_sql);
          $type = 'added';
          if ($user_res == 1) {
            $pass_sql = "UPDATE users SET
                        activation_code = NULL,
                        password = '" . password_hash($_POST['new-pass'], PASSWORD_DEFAULT) . "'
                        WHERE id = " . $_POST['id'] . " AND activation_code = '" . $_POST['activation_code'] . "';";
            $pass_res = $dbcontrol->updateQuery($pass_sql);

            $message = 'Password has been reset<br>
                        You will be redirected to the login page in 5 seconds<br>
                        Or you can <a href="login.php" style="color: #009c22">click here</a> to return now
                        <form action="login.php" id="form">
                        </form>
                        <script>
                          setTimeout(function () {
                            document.getElementById("form").submit()
                          }, 5000)
                        </script>';
            $type = 'added';
          } else {
            $message = 'Could not reset your password';
            $type = 'error';
          }
        } else {
          $message = 'Password must be 8 characters or longer';
          $type = 'error';
        }
      } else {
        $message = 'Passwords do not match';
        $type = 'error';
      }
    } else {
      $message = '';
      $type = 'error';
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Reset Password</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="wrapper">
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <h2 class="header">Reset Password</h2>

    <?php
      if (isset($type) && $type == 'added') {
        echo '<p class="' . $type . '">' . $message . '</p>';
      } else if ((isset($_GET['activation_code']) && isset($_GET['id'])) || (isset($_POST['activation_code']) && isset($_POST['id']))) {
        if (isset($_GET['activation_code'])) {
          $id = $_GET['id'];
          $activation_code = $_GET['activation_code'];
        } else {
          $id = $_POST['id'];
          $activation_code = $_POST['activation_code'];
        }
        if (isset($message)) {
          echo '<p class="' . $type . '">' . $message . '</p>';
        }
        $sql = 'SELECT id, email, activation_code FROM users WHERE id = ' . $id;
        $res = $dbcontrol->runQuery($sql)[0];
        if ($res['activation_code'] != NULL) {
          // $update_sql = "UPDATE users SET activation_code = NULL WHERE id = " . $id . " AND activation_code = '" . $activation_code . "';";
          // $update_res = $dbcontrol->updateQuery($update_sql);
          ?>
            <form action="reset_password.php" method="POST">
              <input type="hidden" name="id" value="<?php echo $id?>">
              <input type="hidden" name="activation_code" value="<?php echo $activation_code?>">
              <div class="form-group">
                <label for="new-pass">New Password</label>
                <input type="password" class="form-control" id="new-pass" name="new-pass" required>
              </div>
              <div class="form-group">
                <label for="conf-pass">Confirm Password</label>
                <input type="password" class="form-control" id="conf-pass" name="conf-pass" required>
              </div>
              <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Save">
              </div>
            </form>
          <?php
        } else { ?>
          <p class='error'>The link has expired or is not correct</p>
        <?php }
      } else { ?>
        <p class='error'>The link has expired or is not correct</p>
      <?php
      }
    ?>
  </div>
  <?php include 'footer.php' ?>
</body>