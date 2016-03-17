<?php
include_once('config.php');
include_once('functions.php');

$actionSuccess = false;

ob_start();

if (isset($_REQUEST['Name'])) {
    // Try login
    $User = GetUser($_REQUEST['Name']);
    if ($User && $User['Hash'] === TerribleHash($_REQUEST['Password'], $User['Salt'])) {
        echo '<p>Successful login! Cookie lasts 1 hour.</p>';
        // 3-hour cookie
        setcookie('TERRIBLENAME', $_REQUEST['Name'], time()+60*60);
        $actionSuccess = true;
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
    $actionSuccess = true;
}

if (isset($_REQUEST['back']) && $actionSuccess) {
    header('Location: '.$_REQUEST['back']);
    exit;
}
ob_end_flush();

PageHeader();

if ($Name = GetLogin()) {
    echo "<p>Welcome back, $Name! (<a href=\"login.php?signout=1\">signout</a>)</p>";
}

?>
    <h3>Login</h3>
    <form action="login.php<?php echo isset($_REQUEST['back']) ? '?back='.rawurlencode($_REQUEST['back']) : null ?>" method="post">
        <ul>
        <li><label for="Name">Name</label><input name="Name" /></li>
        <li><label for="Password">Password</label><input name="Password" /></li>
        </ul>
        <input type="submit" />
    </form><?php

PageFooter();
