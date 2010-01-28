<?php
@ob_end_clean();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <title>Bonk</title>
   <?php
   if ($CssPath !== FALSE)
      echo '<link rel="stylesheet" type="text/css" href="',Asset($CssPath),'" />';
   ?>
</head>
<body>
   <div id="Content">
      <div class="SplashInfo">
         <h1>Uh Oh</h1>
         <p>It seems we're having some trouble serving your request at the moment. Try again later.</p>
      </div>
   </div>
</body>
</html>