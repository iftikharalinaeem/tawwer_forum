<!DOCTYPE html>
<html>
<head>
  {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
   <div id="Frame">
      <div class="NavBar">
         <div class="Row">
            <strong class="SiteTitle"><a href="{link path="/"}">{logo}</a></strong>
            <ul class="SiteMenu">
               <!-- {dashboard_link} -->
               {discussions_link}
               {activity_link}
               <!-- {inbox_link} -->
               {custom_menu}
               <!-- {profile_link}
               {signinout_link}  -->
            </ul>
            <div class="MeWrap">
               {module name="MeModule" CssClass="Inline FlyoutRight"}
            </div>
         </div>
      </div>
      <div id="Body">
         <div class="BreadcrumbsWrapper">
            <div class="Row">
               <div class="SiteSearch">{searchbox}</div>
               {breadcrumbs}
            </div>
         </div>
         <div class="Row">
            <div class="Column PanelColumn" id="Panel">
               {asset name="Panel"}
            </div>
            <div class="Column ContentColumn" id="Content">{asset name="Content"}</div>
         </div>
      </div>
      <div id="Foot">
         <div class="Row">
            <a href="{vanillaurl}" class="PoweredByVanilla" title="Community Software by Vanilla Forums">Powered by Vanilla</a>
            {asset name="Foot"}
         </div>
      </div>
   </div>
   {event name="AfterBody"}
</body>
</html>