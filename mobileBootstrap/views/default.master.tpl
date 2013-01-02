<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
   <head>
      {asset name='Head'}
   </head>
   <body id="{$BodyID}" class="{$BodyClass}">
      <div id="Frame">
         <div class="NavBar NavBarInverse">
            <ul class="SiteMenu">
               {profile_link}
               {inbox_link}
               {custom_menu}
            </ul>

            <ul class="SiteMenu PullRight">
               {if !$User.SignedIn}
                  <li class="SignInItem">{link path="signin" class="SignIn"}</li>
               {/if}

               {if InSection(array('ConversationList', 'Conversation'))}
                  {if CheckPermission('Vanilla.Discussion.Add')}
                     <li><a href="{link path="/messages/add" hastag="0"}" title="New Message"><span class="Sprite SpMessage"></span></a></li>
                  {/if}
               {else}
                  {if CheckPermission('Vanilla.Discussion.Add')}
                     <li><a href="{link path="/post/discussion" hastag="0"}" title="New Discussion"><span class="Sprite SpDiscussion"></span></a></li>
                  {/if}
               {/if}
            </ul>
         </div>
         <div class="NavBar">
            {breadcrumbs}
         </div>
         <div id="Body">
            <div id="Content">
               {asset name="Content"}
            </div>
         </div>
         <div id="Foot">
            <div class="FootMenu">
               {nomobile_link wrap="span"}
               {dashboard_link wrap="span"}
               {signinout_link wrap="span"}
            </div>
            <a class="PoweredByVanilla" href="{vanillaurl}"><span>Powered by Vanilla</span></a>
            {asset name="Foot"}
         </div>
      </div>
      {event name="AfterBody"}
   </body>
</html>
