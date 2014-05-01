<VirtualHost *:80>
        ServerAdmin support@vanillaforums.com
        ServerName vanillaforums.com
		  ServerAlias www.vanillaforums.com
		  DocumentRoot /srv/www/vanillaforumscom
		  ErrorDocument 404 http://vanillaforums.com/filenotfound
		  <Directory /srv/www/vanillaforumscom/>
					 Options FollowSymLinks MultiViews
					 AllowOverride All
		  </Directory>
		  <Directory /srv/www/vanillaforumscom/awstats/>
					 Options ExecCGI -MultiViews +SymLinksIfOwnerMatch
		  </Directory>
        ErrorLog /srv/log/vanillaforumscom/error.log
        LogLevel warn
        CustomLog /srv/log/vanillaforumscom/access.log combined
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
</VirtualHost>