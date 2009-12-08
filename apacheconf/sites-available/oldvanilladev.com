<VirtualHost *:80>
        ServerAdmin support@vanilladev.com
        ServerName vanilladev.com
	ServerAlias *.vanilladev.com
        DocumentRoot /srv/www/vanilladev

        <Directory /srv/www/vanilladev/>
                Options FollowSymLinks MultiViews
		# Options MultiViews
                AllowOverride All
	        # Temporarily passwd protecting these sites                                                                                                                                             
	        AuthUserFile /home/mark/.htpasswd                                                                                                                                                       
	        AuthGroupFile /dev/null                                                                                                                                                                 
	        AuthName "Private!"                                                                                                                                                         
	        AuthType Basic                                                                                                                                                                          
	        <Limit GET>                                                                                                                                                                             
		    require valid-user                                                                                                                                                                      
	        </Limit>   
        </Directory>
	<Directory /srv/www/vanilladev/subdomains/>
	    Satisfy any
	    Allow from nopasswd
	</Directory>

        ErrorLog /srv/log/vanilladev/error.log
        LogLevel warn
        CustomLog /srv/log/vanilladev/access.log combined
        
        ServerSignature Off
	# Rewrite subdomain requests to subdirectories except for www.vanilladev.com
	RewriteEngine On
        RewriteCond %{REQUEST_URI} !^/subdomains/
        RewriteCond %{HTTP_HOST} !^www\.vanilladev\.com [NC]
        RewriteCond %{HTTP_HOST} ^([^.]+)\.vanilladev\.com
        RewriteCond /srv/www/subdomains/%1 -d
	RewriteRule (.*) /subdomains/%1/$1 [L]
	
        # Redirect direct user-agent requests for www.vanilladev.com/subdomains/<subdomain>/<page> to http://<subdomain>.vanilladev.com/<page>
        RewriteCond %{THE_REQUEST} ^[A-Z]{3,9}\ /subdomains/(.+)\ HTTP/                                    
	RewriteRule ^subdomains/([^/]+)/(.*)$ http://$1.vanilladev.com/$2 [R=301,L]
</VirtualHost>
