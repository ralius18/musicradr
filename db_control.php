<?php
class DBController {
  private $conn;
  private $ini_arr = array();

  function __construct() {
    $this->ini_arr = parse_ini_file("private/config.ini");
    $this->conn = $this->connectDB();
    $this->conn->set_charset("utf8mb4");
  }

  function connectDB() {
    $conn = mysqli_connect(
      $this->ini_arr['db_host'],
      $this->ini_arr['db_user'],
      $this->ini_arr['db_pass'],
      $this->ini_arr['db_name'],
      $this->ini_arr['db_port']
    );
    if (!$conn) {
      die('Could not connect to database - ' . mysqli_connect_errno() . ': ' . mysqli_connect_error());
    }
    return $conn;
  }

  function runQuery($query) {
    $result = mysqli_query($this->conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
      $resultset[] = $row;
    }
    if (!empty($resultset)) {
      return $resultset;
    }
  }

  function numRows($query) {
	  $result  = mysqli_query($this->conn, $query);
		$rowcount = mysqli_num_rows($result);
		return $rowcount;	
  }
  
  function updateQuery($query) {
	  $result = mysqli_query($this->conn, $query);
    if (!$result) {
      die('Invalid query: ' . mysqli_error($this->conn));
    } else {
      return $result;
    }
  }
  
  function insertQuery($query) {
	  $result = mysqli_query($this->conn, $query);
		if (!$result) {
		  die('Invalid query: ' . mysqli_error($this->conn));
		} else {
		  return mysqli_insert_id($this->conn);
		}
  }
  
  function deleteQuery($query) {
	  $result = mysqli_query($this->conn, $query);
		if (!$result) {
		  die('Invalid query: ' . mysqli_error($this->conn));
		} else {
			return $result;
		}
  }
  
  function dumpQuery() {
    $host = $this->ini_arr['db_host'];
    $user = $this->ini_arr['db_user'];
    $pass = $this->ini_arr['db_pass'];
    $dbname = $this->ini_arr['db_name'];
    $tables = '*';
    $link = mysqli_connect($host,$user,$pass, $dbname);

    // Check connection
    if (mysqli_connect_errno()) {
      echo "Failed to connect to MySQL: " . mysqli_connect_error();
      exit;
    }

    mysqli_query($link, "SET NAMES 'utf8'");

    //get all of the tables
    if($tables == '*') {
      $tables = array();
      $result = mysqli_query($link, 'SHOW TABLES');
      while($row = mysqli_fetch_row($result)) {
        $tables[] = $row[0];
      }
    } else {
      $tables = is_array($tables) ? $tables : explode(',',$tables);
    }

    $return = '';
    //cycle through
    foreach($tables as $table) {
      $result = mysqli_query($link, 'SELECT * FROM '.$table);
      $num_fields = mysqli_num_fields($result);
      $num_rows = mysqli_num_rows($result);

      $return.= 'DROP TABLE IF EXISTS '.$table.';';
      $row2 = mysqli_fetch_row(mysqli_query($link, 'SHOW CREATE TABLE '.$table));
      $return.= "\n\n".$row2[1].";\n\n";
      $counter = 1;

      //Over tables
      for ($i = 0; $i < $num_fields; $i++) {   //Over rows
        while($row = mysqli_fetch_row($result)) {   
          if($counter == 1) {
            $return.= 'INSERT INTO '.$table.' VALUES(';
          } else {
            $return.= '(';
          }

          //Over fields
          for($j=0; $j<$num_fields; $j++) {
            $row[$j] = addslashes($row[$j]);
            $row[$j] = str_replace("\n","\\n",$row[$j]);
            if (isset($row[$j])) {
              $return.= '"'.$row[$j].'"' ;
            } else {
              $return.= '""';
            }
            if ($j<($num_fields-1)) {
              $return.= ',';
            }
          }

          if($num_rows == $counter) {
            $return.= ");\n";
          } else {
            $return.= "),\n";
          }
          ++$counter;
        }
      }
      $return.="\n\n\n";
    }

    return $return;
  }
}
?>
