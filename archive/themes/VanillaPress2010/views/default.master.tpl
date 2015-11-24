<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}

</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">

<div id="wrapper" class="hfeed">

	<div id="header">
		<div id="masthead">
			<div id="branding" role="banner">
								<h1 id="site-title">
					<span>
						<a href="{link path="/"}" rel="home">{logo}</a>
					</span>
				</h1>

				<div id="site-description">Just another Vanilla Forum</div>
					{capture assign="BannerDefault"}<img src="{link path="/themes/VanillaPress2010/design/vanilla_offices.png" withdomain="true"}" />{/capture}
										{text code="Banner" default=$BannerDefault}
								</div><!-- #branding -->

			<div id="access" role="navigation">
			  				
								<div class="menu">
                                <ul>
                                <li><a href="http://localhost/wordpress/" title="Home">Home</a></li>
                                <li><a href="http://localhost/wordpress/about/" title="About">About</a></li>
                                <li><a href="http://localhost/wordpress/blog/" title="blog">blog</a></li>
                                <li class="current_page_item"><a href="http://localhost/wordpress/forum/" title="forum">forum</a></li>
                                <li><a href="http://localhost/wordpress/contact-us/" title="Contact Us">Contact Us</a></li>
                                
                                </ul>
            </div>

			</div><!-- #access -->
		</div><!-- #masthead -->
	</div><!-- #header -->


  <div id="Body">
    <div id="Content">
      {asset name="Content"}
    </div>
    <div id="Panel">

    
    {asset name="Panel"}
    
  	    <div class="Box">
         <ul id="Bookmark_List" class="PanelInfo PanelDiscussions">
    
             {dashboard_link}
             {discussions_link}
             {activity_link}
             {inbox_link}
             {profile_link}
             {custom_menu}
             {signinout_link}
          </ul>
          </div>  
  
    
    </div>
  </div>
	<div id="footer" role="contentinfo">
  
  
  <div id="colophon">



			<div id="site-info">
				<span>
						<a href="{link path="/"}" rel="home">{logo}</a>
					</span>
			</div><!-- #site-info -->

			<div id="site-generator">
								<a href="{vanillaurl}">
					Powered by Vanilla				</a>
			</div><!-- #site-generator -->

		</div><!-- #colophon -->

  
    
    {asset name="Foot"}
 </div>
</div>

</div>

</body>
</html>