<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Music Radr - About</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
  <?php

    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
      echo '<form action="login.php">
              <input type="submit" value="Login" style="float: right;" action="login.php" class="btn">
            </form>
            <form action="register.php">
              <input type="submit" value="Register" style="float: right;" action="register.php" class="btn">
            </form>';
    } else {
      $email = $_SESSION['email'];
      echo '<form action="logout.php">
              <input type="submit" value="Logout" style="float: right;"class="btn">
            </form>
            <form action="settings.php">
              <input type="submit" value="Settings" style="float: right;"class="btn">
            </form>
            <form action="list.php">
              <input type="submit" value="Home" style="float: right;"class="btn">
            </form>';
            
      include 'color_switch.php';
      if (isset($_POST['content'])) {
        $ini_arr = parse_ini_file("private/config.ini");
        $mail_subject = "&#127925; Radr - Contact Form";
        $mail_content = "
        <h2>&#127925; Radr</h2>
        <h4>Contact from " . $email . "</h4>
        <p>" . $_POST['content'] . "</p>";
        $fname = "/tmp/" . $current_id . "_register.txt";
        $fp = fopen($fname, 'w');
        fwrite($fp, "From: Music Radr Contact <" . $ini_arr['contact_email'] . ">\nSubject: " . $mail_subject . "\nContent-Type: text/html\nReply-To: " . $email . "\n\n" . $mail_content);
        fclose($fp);
        $cmd = "curl --url 'smtps://smtp.zoho.com.au:465'\
              --ssl-reqd\
              --mail-from '" . $ini_arr['contact_email'] . "'\
              --mail-rcpt '" . $ini_arr['contact_email'] . "'\
              --upload-file " . $fname . "\
              --user '" . $ini_arr['contact_email'] . ":" . $ini_arr['contact_pass'] . "'";
        system($cmd);
      }
    }
  ?>
  <a href="list.php">
    <img src="resources/logo-light.png" width="300px">
  </a>
  <br>
  <br>
  <?php include 'about_details.php'; ?>
  <p>
    You can contact me about the service with any feedback or issues by using the form on this page if you are logged in.<br>
    You can also donate to help keep the project up and running by using the donate button at the bottom of any page.
  </p>
  <p>
    The service uses a <a href="https://www.ruby-lang.org" target="_blank">Ruby</a> program to find new releases as a daily process,
    and uses the <a href="https://developer.spotify.com/documentation/web-api/" target="_blank">Spotify Web API</a> to get all the necessary data.
  </p>
  <p>
    The linking to <a href="https://www.azlyrics.com">AZ Lyrics</a> for each song is just an automatic attempt and is
    not guaranteed to work 100% of the time. This site is not affiliated in any way with AZ Lyrics, they are just the most
    reliable and simplest lyrics website that I know of.
  </p>
  <br>
  <hr>
  <?php if (isset($_SESSION['loggedin'])) { ?>
  <div id="contact">
    <h3>Contact</h3>
    <div class="wrapper">
      <?php
        if (isset($_POST['message'])){
          echo "<div class='added'>" . $_POST['message'] . "</div><br>";
        }
      ?>
      <form action="about.php" class="form-group" method="POST">
        <input type="hidden" name="message" value="Thank you for your message">
        <label for="content">Message</label>
        <textarea rows="4" cols="50" class="form-control" name="content" id="content"></textarea>
        <br>
        <input type="submit" value="Send" class="btn btn-primary">
      </form>
    </div>
    <hr>
    <?php } ?>
  </div>
  <div id="privacy">
    <h3>Privacy Policy</h3>
    <br>
    <p>
      &#127925; Radr (The service) is protected by reCAPTCHA and the Google
      <a href="https://policies.google.com/privacy">Privacy Policy</a> and
      <a href="https://policies.google.com/terms">Terms of Service</a> apply.<br>
      The service requires a valid email address to send notifications about new releases. To use the service, your email address must be verified.<br>
      The service will never sell or share your email address.<br>
      The service will only use your email address to send new release notifications.<br>
      You can unsubscribe from all notification types on the settings page.<br>
      Only your current password is stored, and is stored using a password hash, and can only be hacked using brute force, so it is recommended to use a strong password.<br>
      The service does not track or store any information about you apart from your email address, which is required for the service.<br>
      The service is is no way affiliated with Spotify, Google Play Music or Apple Music.
    </p>
  </div>
  <?php
    include 'footer.php';
  ?>
</body>

<script>
  // Remove form resubmit on page refresh
  if ( window.history.replaceState ) {
      window.history.replaceState( null, null, window.location.href );
  }
</script>
