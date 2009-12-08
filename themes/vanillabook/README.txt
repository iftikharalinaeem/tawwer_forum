How to create a theme:

Part 1: CSS / Design
================================================================================
1. Copy this "default" theme folder and rename it to your theme name.
2. Open the "about.php" file and edit the information to reflect your theme.
3. Grab the css file you want to edit from the appropriate application folder
   and copy it into your theme's "design" folder. For example, to edit the
   Vanilla css, grab the /applications/vanilla/design/vanilla.css file and copy
   it to /themes/yourtheme/design/vanilla.css.
4. Go to your Dashboard, Themes, and apply your new theme.
5. Edit the css to look however you wish!

Other things you should know:

 + The user profile pages have their own css file so you can customize them
   separately from the Vanilla css. That file is located in
   /applications/garden/design/profile.css. To edit it, copy it from there to
   /themes/yourtheme/design/profile.css and editing it. 
   
 + The Activity screen also has it's own css file located at
   /applications/garden/design/activity.css. To edit it, copy it from there to
   /themes/yourtheme/design/activity.css and editing it there.
   
 + If you want to edit the look & feel of the garden/administrative screens, you
   can accomplish it by copying the /applications/garden/design/garden.css file
   to /themes/yourtheme/design/garden.css and editing it there.
   /

Part 2: HTML / Views
================================================================================
If you don't like the way we've structured our Html, you can edit that too. Our
pages are made up of two parts:

 1. Master Views - these represent everything that wraps the main content of the
   page. If all you want to do is add a menu or banner above Vanilla, this is
   all you need to alter. To do so, copy the default master view from
   /applications/garden/views/default.master.tpl to
   /themes/yourtheme/views/default.master.tpl and edit it there.
   
 2. Views - these represent all of the content in each page. Every application
   has a "views" folder that contains all of the html for every page. So, for
   example, if you wanted to edit the html for the discussion list, you could
   copy the views from /applications/vanilla/views/discussions to
   /themes/yourtheme/views/discussions and edit them there.
   
The master views use Smarty templates by default, but you can also rename them
to a ".php" extension and edit them as php instead if you want full control.