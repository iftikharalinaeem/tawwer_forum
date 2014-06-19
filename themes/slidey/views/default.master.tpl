<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {asset name="Head"}
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body id="{$BodyID}" class="{$BodyClass}">
    <div class="frame">
      <div class="head">
        <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
        {breadcrumbs}
        <div class="page-title"></div>
        <!-- <div id="Search">{searchbox}</div> -->
      </div>
      <div class="body">
        <div class="content-container">
        <div id="Panel">
          <a class="panel-arrow" href="#">&nbsp;</a>
          <div class="panel-circle"></div>
          <div class="panel-content">
            {module name="MeModule" CssClass="FlyoutRight"}
            {asset name="Panel"}
          </div>
        </div>
        <div id="Content">
          {asset name="Content"}
        </div>
      </div>
    </div>
      {asset name="Foot"}
    </div>
    {event name="AfterBody"}
  </body>
</html>
