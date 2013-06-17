<!DOCTYPE html>
<html>
<head>
  {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
   <div id="Frame">
      <div class="Head">
         <div class="Row">
            <span class="OpenSource"></span>
            <a href="{link path="/"}" class="Home">Vanilla Forums: Community Forums Evolved</a>
            <div class="Menu">
               <a href="{link path="/addons"}">Addons</a>
               <a href="{link path="/discussions"}">{t c="Community"}</a>
               <a href="{link path="/docs"}">Documentation</a>
               <a href="http://vanillaforums.com/blog">{t c="Blog"}</a>
               <a href="/download" class="Download">{t c="Download"}</a>
               <a href="http://vanillaforums.com" class="Hosting">Hosting<span>Start Using Vanilla today</span></a>
            </div>
            {*
            <?php
            echo '<div class="Menu">';
               echo Anchor('Addons', 'addons');
               echo Anchor('Community', 'discussions');
               echo Anchor('Documentation', 'docs');
               echo Anchor('Blog', 'http://vanillaforums.com/blog');
               echo Anchor('Hosting'.Wrap('Start using Vanilla today'), 'http://vanillaforums.com', array('class' => 'Hosting'));
               // echo Anchor('Download', 'download', array('class' => 'Download'));
            echo '</div>';
            ?>*}
         </div>
      </div>
      <div id="Body">
         <div class="BreadcrumbsWrapper">
            <div class="Row">
               {breadcrumbs}
               <div class="MeModuleWrap">
                  {module name="MeModule" CssClass="Inline FlyoutRight"}
               </div>
            </div>
         </div>
         
         <div class="Row">
            <div class="Column PanelColumn" id="Panel">
               {asset name="Panel"}
            </div>
            <div class="Column ContentColumn" id="Content">
               {if InSection(array('DiscussionList', 'CategoryList'))}
                  {searchbox_advanced}
               {/if}
               {asset name="Content"}
            </div>
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