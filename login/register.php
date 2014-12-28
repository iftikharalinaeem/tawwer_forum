<?php
include_once('config.php');
include_once('functions.php');

if (isset($_REQUEST['Name'])) {
   // Try register
   $User = array(
      'Name' => $_REQUEST['Name'],
      'Email' => $_REQUEST['Email'],
      'Role' => $_REQUEST['Role'],
      'Password' => $_REQUEST['Password']
   );
   WriteUser($User);
   echo '<p>Created user '.$_REQUEST['Name'].'</p>';
}

PageHeader();

if ($Name = GetLogin()) {
   echo "<p>Welcome back, $Name! (<a href=\"login.php?signout=1\">signout</a>)</p>";
}

?>
   <h3>Register a new user</h3>
   <form action="register.php">
      <ul>
      <li><label for="Name">Name</label><input name="Name" /></li>
      <li><label for="Email">Email</label><input name="Email" /></li>
      <li><label for="Password">Password</label><input name="Password" /></li>
      <li><label for="Role">Role</label><input name="Role" /></li>
      </ul>
      <input type="submit" />
   </form><?php

PageFooter();