<VirtualHost *:80>
        ServerAdmin support@vanillaforums.com
        ServerName vanillaforums.com
	ServerAlias *.vanillaforums.com
#	DocumentRoot /srv/www/vanilla_down
        DocumentRoot /srv/www/vanillaforumscom

#	<Directory /srv/www/vanilla_down/>        
        <Directory /srv/www/vanillaforumscom/>
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
	<Directory /srv/www/vanillaforumscom/subdomains/>
	    Satisfy any
	    Allow from nopasswd
	</Directory>

        ErrorLog /srv/log/vanillaforumscom/error.log
        LogLevel warn
        CustomLog /srv/log/vanillaforumscom/access.log combined
        
        ServerSignature Off
	
	# Rewrite subdomain requests to subdirectories except for www.vanillaforums.com
	RewriteEngine On
	RewriteCond %{REQUEST_URI} !^/subdomains/
	RewriteCond %{HTTP_HOST} !^www\.vanillaforums\.com [NC]
	RewriteCond %{HTTP_HOST} ^([^.]+)\.vanillaforums\.com
	RewriteCond /srv/www/subdomains/%1 -d
	RewriteRule (.*) /subdomains/%1/$1 [L]

	# Redirect direct user-agent requests for www.vanillaforums.com/subdomains/<subdomain>/<page> to http://<subdomain>.vanillaforums.com/<page>
	RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /subdomains/(.+)\ HTTP/
	RewriteRule ^subdomains/([^/]+)/(.*)$ http://$1.vanillaforums.com/$2 [R=301,L] 
</VirtualHost>
