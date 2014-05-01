<VirtualHost *:80>
        ServerAdmin support@foodzie.com
        ServerName forums.foodzie.com
        DocumentRoot /srv/www/subdomains/foodzieproducers

        <Directory /srv/www/subdomains/foodzieproducers/>
                Options FollowSymLinks MultiViews
		# Options MultiViews
                AllowOverride All
	        # Temporarily passwd protecting these sites                                                                                                                                             
	        # AuthUserFile /home/mark/.htpasswd                                                                                                                                                       
	        # AuthGroupFile /dev/null                                                                                                                                                                 
	        # AuthName "Soon, Friends. Soon."                                                                                                                                                         
	        # AuthType Basic                                                                                                                                                                          
	        # <Limit GET>                                                                                                                                                                             
		#        require valid-user                                                                                                                                                                      
	        # </Limit>   
        </Directory>

        ErrorLog /srv/log/vanillaforumscom/error.log
        LogLevel warn
        CustomLog /srv/log/vanillaforumscom/access.log combined
        
        ServerSignature Off
</VirtualHost>
