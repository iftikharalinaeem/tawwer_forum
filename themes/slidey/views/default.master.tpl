<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {asset name="Head"}
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body id="{$BodyID}" class="{$BodyClass}">
    <div id="Frame">
      <div id="Head">
        {breadcrumbs}
        <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
        <!-- <div id="Search">{searchbox}</div> -->
      </div>
      <div id="Body">
        <div id="Panel">
          <a id="panel-arrow" href="#" onclick="slide();">&nbsp;</a>
          <div id="panel-circle" href="#">&nbsp;</div>
          <div id="panel-content">
            {module name="MeModule"}
            {asset name="Panel"}
          </div>
        </div>
        <div id="Content">
          {asset name="Content"}
        </div>
      </div>
      {asset name="Foot"}
    </div>
    {event name="AfterBody"}
  </body>
</html>
