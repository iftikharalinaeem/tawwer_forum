<VirtualHost *:80>
        ServerAdmin support@365hangers.com
        ServerName forums.365hangers.com
        DocumentRoot /srv/www/subdomains/365hangers

        <Directory /srv/www/subdomains/365hangers/>
                Options FollowSymLinks MultiViews
                AllowOverride All
        </Directory>

        ErrorLog /srv/log/vanillaforumscom/error.log
        LogLevel warn
        CustomLog /srv/log/vanillaforumscom/access.log combined
        
        ServerSignature Off
</VirtualHost>
