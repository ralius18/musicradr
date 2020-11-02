<?php
  if ($_SERVER['REQUEST_METHOD'] == "POST") {
    include_once('db_control.php');

    $dbcontrol = new DBController();

    if (isset($_POST['email'])) {
      $ini_arr = parse_ini_file("private/config.ini");
      $activation_code = md5(rand());
      $id_sql = "SELECT id, email, activation_code FROM users WHERE email = '" . $_POST['email'] . "';";
      $user = $dbcontrol->runQuery($id_sql)[0];
      
      if ($user['activation_code'] == NULL) {
        $update_sql = "UPDATE users SET activation_code = '" . $activation_code . "' WHERE id = " . $user['id'] . ";";
        $update_res = $dbcontrol->updateQuery($update_sql);

        $mail_link = "https://" . $_SERVER["HTTP_HOST"] . "/reset_password.php?id=" . $user['id'] . "&activation_code=" . $activation_code;
        $mail_subject = "Music Radr - Reset Password";

        // Merge mail content
        $fname = 'resources/forgot.html';
        $fhandle = fopen($fname, 'r');
        $mail_content = fread($fhandle, filesize($fname));
        fclose($fhandle);
        $mail_content = str_replace('$$mail_link', $mail_link, $mail_content);
        $mail_content = str_replace('$$from_email', $ini_arr['contact_email'], $mail_content);
        $mail_content = str_replace('$$mail_subject', $mail_subject, $mail_content);
        $tmp_name = '/tmp/' . $user['id'] . '_forgot.txt';
        $tmp_file = fopen($tmp_name, 'w');
        fwrite($tmp_file, $mail_content);
        fclose($tmp_file);
        // Send email using curl
        $cmd = "curl --url 'smtps://smtp.zoho.com.au:465'\
              --ssl-reqd\
              --mail-from '" . $ini_arr['contact_email'] . "'\
              --mail-rcpt '" . $_POST['email'] . "'\
              --upload-file " . $tmp_name . "\
              --user '" . $ini_arr['contact_email'] . ":" . $ini_arr['contact_pass'] . "'";
        system($cmd);
        
        $message = "An email has been sent to " . $_POST['email'] . "<br>Please follow the link in the email to complete the password reset";
      } else {
        $message = "Could not send email";
      }
    } else {

    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Forgot Password</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="wrapper">
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <h2 class="header">Forgot Password</h2>
    <?php 
      if(isset($message)) {
        echo '<div class="added">' . $message . '</div>';
      } else {
    ?>
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group <?php echo (!empty($err)) ? 'has-error' : ''; ?>">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required=true autofocus>
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary" value="Reset Password"><br>
          <input type="button" class="btn" value="Back to Login" onclick="window.location = 'login.php'">
        </div>
      </form>
    <?php } ?>
  </div>
  <?php include 'footer.php' ?>
</body>
</html>