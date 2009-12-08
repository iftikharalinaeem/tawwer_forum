<VirtualHost *:80>
        ServerAdmin support@carsonified.com
        ServerName forum.carsonified.com
        DocumentRoot /srv/www/subdomains/carsonified

        <Directory /srv/www/subdomains/carsonified/>
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
