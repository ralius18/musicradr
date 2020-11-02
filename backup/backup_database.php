<?php
  require_once('db_control.php');

  session_start();
  $dbcontrol = new DBController();

  $output = $dbcontrol->dumpQuery();

  header('Content-Type: application/octet-stream');
  header('Content-Transfer-Encoding: Binary');
  header('Content-Length: '. (function_exists('mb_strlen') ? mb_strlen($output, '8bit'): strlen($output)) );
  header("Content-disposition: attachment; filename=music_sql_backup_" . date('H-i-s') . '_' . date('d-m-Y') . '.sql');
  echo $output;
?>