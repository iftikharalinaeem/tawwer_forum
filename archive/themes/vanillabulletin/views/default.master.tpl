<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>
<body id="{$BodyID}" class="{$BodyClass}{if $LightsDown} LightsDown{/if}">
<div class="BannerFrame">
   <div class="Banner">
      <div class="Row">
         <strong class="SiteTitle"><a href="{link path="/"}">{logo}</a></strong>
      </div>
   </div> 
</div>
<div id="Frame">
   <div id="Head">
      <div class="Row">
         <ul id="Menu">
            <li>{link path="/categories" text="Forum"}</li>
            {discussions_link text="What's New?"}
            {activity_link}
            {custom_menu}
            {if $User.SignedIn}
               <li>{link path="/drafts" text="Drafts"}</li>
               {signinout_link}
            {/if}
         </ul>
         <div id="MeWrap">
            {module name="MeModule" CssClass="Inline FlyoutRight"}
         </div>
      </div>
   </div>
   <div class="BreadcrumbWrap">
      {breadcrumbs}
      <div id="Search">{searchbox}</div>
   </div>
   <div id="Body">
      <div class="Row">
         {if $_NoPanel}
            <div id="Content" class="Column">
               {asset name="Panel"}
         {else}
            <div id="Panel" class="Column PanelColumn">{asset name="Panel"}</div>
            <div id="Content" class="Column ContentColumn">
         {/if}
         {asset name="Content"}
         {if InSection(array('CategoryList', 'DiscussionList'))}
            {module name="OnlineModule"}
         {/if}
         </div>
      </div>
   </div>
</div>
<div id="Foot">
   {asset name="Foot"}
   <a class="PoweredByVanilla" href="http://vanillaforums.com">Powered by Vanilla</a>
</div>
{event name="AfterBody"}
</body>
</html>