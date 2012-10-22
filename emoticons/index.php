<html>
   <head>
      <title>Emoticon Contact Sheet</title>
      <style>
         body {
            background: rgba(0,0,0,.75);
            color: #eee;
         }
         h1 {
            text-shadow: 0 1px 1px #000;
         }
         .Emoticon {
            float: left;
            margin: 2px;
            text-align: center;
            min-width: 75px;
            padding: 2px;
            border-radius: 2px;
            background: #fff;
            box-shadow: 0 2px 5px #000;
         }
         .Img {
            background: #bbb;
            padding: 4px;
            border-radius: 1px;
            box-shadow: 0 1px 0 rgba(0,0,0,.3) inset;
         }
         .Label {
            font-family: Verdana;
            font-size: 9px;
            color: #000;
            padding: 2px;
         }
      </style>
   </head>
   <body>
      <h1>Emoticon Contact Sheet</h1>
      <div class="Emoticons">
      <?php
      $Paths = glob(dirname(__FILE__).'/*.gif');
      foreach ($Paths as $Path) {
         $Src = basename($Path);
         $Label = htmlspecialchars(basename($Path, '.gif'));
         echo <<<EOT
<div class="Emoticon">
   <div class="Img"><img src="$Src" /></div>
   <div class="Label">$Label</div>
</div>
EOT;
      }
      ?>
      </div>
   </body>
</html>
