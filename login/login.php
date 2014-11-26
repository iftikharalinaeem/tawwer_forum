<?php
include_once('config.php');
include_once('functions.php');

if (isset($_REQUEST['Name'])) {
   // Try login
   $User = GetUser($_REQUEST['Name']);
   if ($User && $User['Hash'] === TerribleHash($_REQUEST['Password'], $User['Salt'])) {
      echo '<p>Successful login! Cookie lasts 1 hour.</p>';
      // 3-hour cookie
      setcookie('TERRIBLENAME', $_REQUEST['Name'], time()+60*60);
   } elseif (!$User) {
      echo '<p>User not found.</p>';
   } else {
      echo '<p>Password doesn\'t match. Expected: '.$User['Password'].'</p>';
   }
}

if (isset($_REQUEST['signout'])) {
   // Goodbye!
   echo '<p>Logged out.</p>';
   setcookie('TERRIBLENAME', NULL, time()-1);
}

PageHeader();

if ($Name = GetLogin()) {
   echo "<p>Welcome back, $Name! (<a href=\"login.php?signout=1\">signout</a>)</p>";
}

?>
   <h3>Login</h3>
   <form action="login.php">
      <ul>
      <li><label for="Name">Name</label><input name="Name" /></li>
      <li><label for="Password">Password</label><input name="Password" /></li>
      </ul>
      <input type="submit" />
   </form><?php

PageFooter();