<VirtualHost *:80>
        ServerAdmin support@spryplanner.com
        ServerName support.spryplanner.com
#	DocumentRoot /srv/www/vanilla_down
        DocumentRoot /srv/www/subdomains/spry

#	<Directory /srv/www/vanilla_down/>        
        <Directory /srv/www/subdomains/spry/>
                Options FollowSymLinks MultiViews
                AllowOverride All
        </Directory>

        ErrorLog /srv/log/vanillaforumscom/error.log
        LogLevel warn
        CustomLog /srv/log/vanillaforumscom/access.log combined
        
        ServerSignature Off
</VirtualHost>
