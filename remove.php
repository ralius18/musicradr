<?php
  include('db_control.php');
  session_start();

  $dbcontrol = new DBController();
  // TODO: Delete from user link instead of artists table
  foreach ($_POST as $id) {
    $sql = 'DELETE FROM user_links WHERE link_id = "' . $id . '" AND user_id = "' . $_SESSION['id'] . '";';
    $result = $dbcontrol->deleteQuery($sql);
  }
  echo '<form action="list.php" method="POST" id="remove">';
  echo '<input type="hidden" name="removed" value="' . count($_POST) . '">';
  echo '</form>';
?>
<link rel="stylesheet" href="styles.css">
<script>document.getElementById("remove").submit()</script>