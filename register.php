<?php
session_start();
// Include config file
include_once('db_control.php');

if ($_SESSION["loggedin"]) {
  header("location: list.php");
}

$ini_arr = parse_ini_file("private/config.ini");

$dbcontrol = new DBController();
// Define variables and initialize with empty values
$email = $password = $confirm_password = $activation_code = $country = '';
$email_err = $password_err = $confirm_password_err = $captcha_err = "";
// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Validate email
  if (empty(trim($_POST["email"]))) {
    $email_err = "Please enter a valid email address";
  } else {
    $sql = "SELECT * FROM users WHERE email = '" . $_POST["email"] . "'";
    $count = $dbcontrol->numRows($sql);
    if ($count > 0) {
      $email_err = 'That email address is already in use';
    } else {
      $email = trim($_POST["email"]);
    }
  }

  //Validate password
  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter a password";     
  } elseif (strlen(trim($_POST["password"])) < 8) {
    $password_err = "Password must have at least 8 characters";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate confirm password
  if(empty(trim($_POST["confirm_password"]))) {
    $confirm_password_err = "Please confirm password.";     
  } else {
    $confirm_password = trim($_POST["confirm_password"]);
    if (empty($password_err) && ($password != $confirm_password)) {
      $confirm_password_err = "Password did not match.";
    }
  }

  $country = $_POST['country'];

  // Verify reCaptcha
  $captcha_err = '';
  $token = $_POST['token'];
  $secret = $ini_arr['captcha_secret'];
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('secret' => $secret, 'response' => $token)));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $response = curl_exec($ch);
  curl_close($ch);
  $arr_response = json_decode($response, true);
  if ($arr_response['success'] != 1 || $arr_response['score'] < 0.5){
    $captcha_err = "Captcha did not succeed";
    $message = "Captcha did not succeed";
    $type = "error";
  }

  // Check input errors before inserting in database
  if (empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($captcha_err)) {
    $activation_code = md5(rand());
    $sql = "INSERT INTO users (email, password, activation_code, country) VALUES
    ('" . $email . "', '" . password_hash($password, PASSWORD_DEFAULT) . "', '" . $activation_code . "', '" . $country . "');";

    $current_id = $dbcontrol->insertQuery($sql);
    if (!empty($current_id)) {
      $mail_link = "https://" . $_SERVER["HTTP_HOST"] . "/activate.php?id=" . $current_id . "&activation_code=" . $activation_code;
      $mail_subject = "Music Radr - Registration";

      // Merge mail content
      $fname = 'resources/register.html';
      $fhandle = fopen($fname, 'r');
      $mail_content = fread($fhandle, filesize($fname));
      fclose($fhandle);
      $mail_content = str_replace('$$mail_link', $mail_link, $mail_content);
      $mail_content = str_replace('$$from_email', $ini_arr['contact_email'], $mail_content);
      $mail_content = str_replace('$$mail_subject', $mail_subject, $mail_content);
      $tmp_name = "/tmp/" . $current_id . "_register.txt";
      $tmp_file = fopen($tmp_name, 'w');
      fwrite($tmp_file, $mail_content);
      fclose($tmp_file);
      $cmd = "curl --url 'smtps://smtp.zoho.com.au:465'\
             --ssl-reqd\
             --mail-from '" . $ini_arr['contact_email'] . "'\
             --mail-rcpt '" . $email . "'\
             --upload-file " . $tmp_name . "\
             --user '" . $ini_arr['contact_email'] . ":" . $ini_arr['contact_pass'] . "'";
      system($cmd);
      $message = "An email has been sent to " . $email . "<br>Please follow the link in the email to complete your registration";
      $type = 'added';
    }
  }
}
?>

<!-- TODO: Use email address instead of username -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Music Radr - Register</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
  <link rel="stylesheet" href="styles.css">
  <script src="https://www.google.com/recaptcha/api.js?render=6Ld15NEUAAAAACcBG0SkJt1BTR5N9Sdgpk_KEY7h"></script>
</head>
<body>
  <div class="wrapper">
    <a href="list.php">
      <img src="resources/logo-light.png" width="300px">
    </a>
    <h2 class="header">Register</h2>
    <?php 
      if(isset($message)) {
    ?>
      <div class="<?php echo $type; ?>"><?php echo $message; ?></div>
    <?php
      }
    else { ?>
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="register-form">
        <div class="form-group <?php echo (!empty($email_err)) ? 'has-error' : ''; ?>">
          <label>Email</label>
          <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" autofocus>
          <span class="help-block"><?php echo $email_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
          <label>Password</label>
          <input type="password" name="password" class="form-control" value="<?php echo $password; ?>">
          <span class="help-block"><?php echo $password_err; ?></span>
        </div>
        <div class="form-group <?php echo (!empty($confirm_password_err)) ? 'has-error' : ''; ?>">
          <label>Confirm Password</label>
          <input type="password" name="confirm_password" class="form-control" value="<?php echo $confirm_password; ?>">
          <span class="help-block"><?php echo $confirm_password_err; ?></span>
        </div>
        <div class="form-group">
          <label>Country</label>
          <select name="country" class="form-control" required>
          <?php
            $country_sql = "SELECT * FROM countries ORDER BY name;";
            $countries = $dbcontrol->runQuery($country_sql);
            $user_sql = "SELECT country FROM users WHERE id = " . $_SESSION['id'];
            $user_country = $dbcontrol->runQuery($user_sql)[0]['country'];
            echo "<option value='' selected disabled hidden>Select Country</option>";
            foreach ($countries as &$c) {
              $option = '<option value="' . $c['code'] . '"';
              $option .= '>' . $c['name'] . '</option>';
              echo $option;
            }
          ?>
          </select>
          <p>Only countries
            <a href="https://support.spotify.com/us/using_spotify/getting_started/full-list-of-territories-where-spotify-is-available/" target="_blank">
            available on Spotify</a> are listed
          </p>
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary" value="Submit">
        </div>
        <p>Already have an account? <a href="login.php">Log in here</a>.</p>
      </form>
    <?php } ?>
    <br><br><br>
    <div style="font-size: .8em; color: #777">
      This site is protected by reCAPTCHA and the Google
      <a href="https://policies.google.com/privacy">Privacy Policy</a> and
      <a href="https://policies.google.com/terms">Terms of Service</a> apply.<br>
    </div>
  </div>
  <script>
  grecaptcha.ready(function() {
      grecaptcha.execute($ini_arr['captcha_sitekey'], {action: 'homepage'}).then(function(token) {
        var token_node = document.createElement('INPUT')
        token_node.type = 'hidden'
        token_node.name = 'token'
        token_node.value = token
        document.getElementById('register-form').appendChild(token_node)
      });
  });
  </script>
  <?php include 'footer.php' ?>
</body>
</html>
