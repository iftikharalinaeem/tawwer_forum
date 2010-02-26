<VirtualHost *:80>
        ServerAdmin support@vanilladev.com
        ServerName vanilladev.com
		  ServerAlias www.vanilladev.com
		  DocumentRoot /srv/www/vanilladev
		  ErrorDocument 404 http://vanillaforums.com/filenotfound
		  <Directory /srv/www/vanilladev/>
					 Options FollowSymLinks MultiViews
					 AllowOverride All
					 # AuthUserFile /home/mark/.htpasswd                                                                                                                                                       
					 # AuthGroupFile /dev/null                                                                                                                                                                 
					 # AuthName "Private!"                                                                                                                                                         
					 # AuthType Basic                                                                                                                                                                          
					 # <Limit GET>                                                                                                                                                                             
					 #		require valid-user                                                                                                                                                                      
					 #</Limit>
		  </Directory>
        ErrorLog /srv/log/vanilladev/error.log
        LogLevel warn
        CustomLog /srv/log/vanilladev/access.log combined
        ServerSignature Off
</VirtualHost>

<VirtualHost *:80>
		  ServerAlias *
		  UseCanonicalName Off
		  LogFormat "%V %h %l %u %t \"%r\" %s %b" vcommon
		  LogLevel debug
		  ErrorLog /srv/log/vhosts/error.log
		  CustomLog /srv/log/vhosts/access.log vcommon
		  VirtualDocumentRoot /srv/www/vhosts/%0/
		  ErrorDocument 404 http://vanillaforums.com/filenotfound
		  <Directory /srv/www/vhosts/etsy.vanilladev.com/>
					 Options FollowSymLinks MultiViews
					 AllowOverride All
					 AuthUserFile /home/mark/.htpasswd                                                                                                                                                       
					 AuthGroupFile /dev/null                                                                                                                                                                 
					 AuthName "Private!"                                                                                                                                                         
					 AuthType Basic                                                                                                                                                                          
					 <Limit GET>                                                                                                                                                                             
					 		require valid-user                                                                                                                                                                      
					 </Limit>
		  </Directory>
</VirtualHost>