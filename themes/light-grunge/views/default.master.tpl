<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
 <div id="Frame">
  <div id="Head">
   <div class="Menu">
    <!--Load custom logo from banner options-->
    <h1 class="Title"><a href="{link path="/"}">{logo}</a></h1>
    <!-- Start menu -->
    <ul id="Menu">
      {dashboard_link}
      {discussions_link}
      {activity_link}
      {inbox_link}
      {profile_link}
      {custom_menu}
      {custom_menu}
      {signinout_link}
    </ul>
    <!-- End menu -->
   </div>
  </div>
  <div id="Body">
   <!-- Start body content: helper menu and discussion list -->
   <div id="Content">{asset name="Content"}</div>
   <!-- End body content -->
   <!-- Start panel modules: search, categories, and bookmarked discussions -->
   <div id="Panel">
    <div id="Search">{searchbox}</div>
    {asset name="Panel"}
   </div>
   <!-- End panel -->
  </div>
  <!-- Start foot -->
  <div id="Foot">
   <div>
    <div class="vanilla-ico"></div>
    Powered by
    <a href="{vanillaurl}"><span>Vanilla</span></a>
   </div>
   {asset name="Foot"}
  </div>
  <!-- End foot -->  
 </div>
</body>
</html>
