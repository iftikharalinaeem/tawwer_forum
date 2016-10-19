<?php
session_start();
global $DB;
$DB = new PDO(
    "mysql:host={$C['DB.Host']};dbname={$C['DB.Name']};charset=utf8",
    $C['DB.User'],
    $C['DB.Pass']
);


/**
 * HTML output for page rendering.
 */
function PageHeader() {
   echo '<html>
<head>
   <title>Terrible Login System</title>
   <style>

   </style>
</head>
<body>';
}

/**
 * HTML output for page rendering.
 */
function PageFooter() {
   echo '
   </body>
</html>';
}

/**
 * Query the database.
 *
 * @param string $Query
 * @return PDOStatement|bool
 */
function Query($Query = '') {
   global $DB, $C;
   $Result = $DB->query($Query);
   if (isset($C['Debug']) && $C['Debug']) {
      echo "<pre>".$Query."</pre>";

       if ($DB->errorCode() !== PDO::ERR_NONE) {
           echo "<pre>" . print_r($DB->errorInfo(), true) . "</pre>";
       }
   }
   return $Result;
}

/**
 * Get a user from the DB.
 *
 * @param int|string $UserID
 * @return array|bool
 */
function GetUser($UserID) {
   global $DB;
   if (is_numeric($UserID)) {
      $Result = Query("select * from users where UserID = $UserID");
   } else {
      $Result = Query("select * from users where Name = ".$DB->quote($UserID));
   }

   $User = $Result instanceof PDOStatement ? $Result->fetch(PDO::FETCH_ASSOC) : false;

   return $User;
}

/**
 * Write a user to the DB.
 *
 * @param array $User
 */
function WriteUser($User = array()) {
   global $DB;
   $Exists = GetUser($User['Name']);
   if ($Exists) {
      // Build escaped update SQL.
      $Query = "update users set";
      foreach ($User as $Col => $Val) {
         if ($Col == 'UserID') {
            $UserID = $Val;
            continue;
         }
         $Query .= " $Col = ".$DB->quote($Val).",";
      }
      $Query = rtrim($Query, ',')." where UserID = ".$Exists['UserID'];

      Query($Query);
   } else {
      // Create salt & hash for new user
      $User['Salt'] = mt_rand(1000,9999);
      $User['Hash'] = TerribleHash($User['Password'], $User['Salt']);

      // Build escaped insert SQL string for input data.
      $Cols = implode(',', array_keys($User));
      $Vals = array_values($User);
      foreach ($Vals as &$Val) {
         $Val = $DB->quote($Val);
      }
      $Vals = implode(',', $Vals);

      Query("insert into users ($Cols) values ($Vals)");
   }
}

/**
 * Bonus: Name the terrible forum platform this came from.
 *
 * @param string $RawPass
 * @param string $Salt
 */
function TerribleHash($RawPass, $Salt) {
   return md5(md5($RawPass).$Salt);
}

function GetLogin() {
   if (isset($_COOKIE['TERRIBLENAME']) && $_COOKIE['TERRIBLENAME']) {
      return $_COOKIE['TERRIBLENAME'];
   }
   return false;
}

/**
 * Setup function to create a MySQL table.
 */
function WriteTables() {
   Query("CREATE TABLE users (
      UserID int(11) unsigned NOT NULL AUTO_INCREMENT,
      Name varchar(255),
      Email varchar(255) DEFAULT NULL,
      Password varchar(255) DEFAULT NULL,
      Hash varchar(255) DEFAULT NULL,
      Salt varchar(32) DEFAULT NULL,
      Role varchar(255) DEFAULT NULL,
      Banned tinyint(1) DEFAULT '0',
      PRIMARY KEY (UserID)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
}