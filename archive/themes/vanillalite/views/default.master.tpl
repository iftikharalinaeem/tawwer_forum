<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
  {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">
<div id="Background"> </div>
<div id="Frame">
   <div id="Head">
      <div class="Wrap">
         <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
         <ul id="Menu" class="Menu">
            {dashboard_link}{discussions_link}{activity_link}{custom_menu}
         </ul>
       </div>
   </div>
   
   <div id="Body">
      <div class="Wrap">
         <div class="Columns ClearFix">
            <div id="Content" class="Column Column1">{asset name="Content"}</div>
            <div id="Panel" class="Column Column2">
               {module name="UserSessionModule"}
               <!--<div id="Search" class="SearchBox">{searchbox}</div>-->
               {asset name="Panel"}
            </div>
         </div>
      </div>
   </div>

   <div id="Foot">
      <div class="Wrap">
         <div class="PoweredBy"><a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
         {asset name="Foot"}
      </div>
   </div>
</div>
{event name="AfterBody"}
</body>
</html>