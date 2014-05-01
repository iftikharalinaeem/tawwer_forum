    _____   _______ ____________  _____________
   /  _/ | / / ___//  _/ ____/ / / /_  __/ ___/
   / //  |/ /\__ \ / // / __/ /_/ / / /  \__ \ 
 _/ // /|  /___/ // // /_/ / __  / / /  ___/ / 
/___/_/ |_//____/___/\____/_/ /_/ /_/  /____/  
                                               
------------------------------- by GRAVITY ----

Welcome to Gravity Insights Community Analytics Plugin for Vanilla Forums. 
Please follow the instructions outlined below to activate your plugin. 

For additional features or support please email us anytime at insights@gravity.com


INSTALLATION ----------------------------------- 

1. Upload the insights directory to your webroot/extensions directory. After the upload it should look like: webroot/extensions/insights

2. Access your admin panel (settings). 
	* On the left hand side click on Extensions 
	* You should now see Gravity Insights in your extension list
	* Click on the checkbox next to Gravity Insights to enable it
	* It should import properly, once it's enabled you're done. You
	  may now log into your insights account (please note it may be a day before your account is fully up and running)
	
	
	
FAQ --------------------------------------------

Q: How to I exclude certain forums I don't want reports on?
A: In the insights/ directory there is a siteconfig.ini file. In there you'll see a line for "hidden_forums =". Just uncomment out that line and put in a comma separated list of category ids you don't want data captured for. Once you save that file and re-upload it those forums will be ignored by the plugin.

Q: How do I uninstall the plugin?
A: Just uncheck the Gravity Insights checkbox in your extensions manager page, then remove the insights directory from your webroot/extensions directory. All clean.
