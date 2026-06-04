Options -Indexes

##############################################
# SECURITY HEADERS
##############################################
<IfModule mod_headers.c>
	Header always set Content-Security-Policy "upgrade-insecure-requests;"
	Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
	Header always set X-Frame-Options "SAMEORIGIN"
	Header always set X-Content-Type-Options "nosniff"
	Header always set Referrer-Policy "strict-origin-when-cross-origin"
	Header always set Permissions-Policy "camera=(), microphone=(), geolocation=()"

	# Prevent CSS/JS from being indexed
	<FilesMatch "\.(css|js)$">
		Header set X-Robots-Tag "noindex, nofollow"
	</FilesMatch>

	# Serve correct Vary header for content-negotiated resources
	Header append Vary Accept
</IfModule>

##############################################
# PROTECT SENSITIVE FILES
##############################################
<FilesMatch "^(\.env|\.env\..*|composer\.(json|lock)|package(-lock)?\.json|pnpm-lock\.yaml|phpunit\.xml|wp-cli\.yml|wp-config\.php|wp-config-sample\.php|README\.md|LICENSE|CHANGELOG(\.md)?)$">
	Require all denied
</FilesMatch>

##############################################
# REWRITE ENGINE
##############################################
RewriteEngine On

# Block VCS / IDE folders
RewriteRule (^|/)\.(git|svn|hg|idea|vscode) - [F,L]

# Block access to parent directory traversal
RewriteRule ^\.\./ - [F,L]

# Disable PHP execution in uploads
RewriteRule ^wp-content/uploads/.*\.(?:php[0-9]?|phar|phtml|phps)$ - [F,L]

# Enforce HTTPS
RewriteCond %{HTTPS} off
RewriteCond %{HTTP:X-Forwarded-Proto} !https
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

##############################################
# COMPRESSION (GZIP)
##############################################
<IfModule mod_deflate.c>
	AddOutputFilterByType DEFLATE application/javascript
	AddOutputFilterByType DEFLATE application/json
	AddOutputFilterByType DEFLATE application/rss+xml
	AddOutputFilterByType DEFLATE application/vnd.ms-fontobject
	AddOutputFilterByType DEFLATE application/xhtml+xml
	AddOutputFilterByType DEFLATE application/xml
	AddOutputFilterByType DEFLATE font/opentype
	AddOutputFilterByType DEFLATE font/otf
	AddOutputFilterByType DEFLATE font/ttf
	AddOutputFilterByType DEFLATE image/svg+xml
	AddOutputFilterByType DEFLATE image/x-icon
	AddOutputFilterByType DEFLATE text/css
	AddOutputFilterByType DEFLATE text/html
	AddOutputFilterByType DEFLATE text/javascript
	AddOutputFilterByType DEFLATE text/plain
	AddOutputFilterByType DEFLATE text/xml
</IfModule>

##############################################
# STATIC CACHE
##############################################
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 6 months"
    ExpiresByType image/png "access plus 6 months"
    ExpiresByType image/gif "access plus 6 months"
    ExpiresByType image/webp "access plus 6 months"
    ExpiresByType image/avif "access plus 6 months"
    ExpiresByType image/svg+xml "access plus 6 months"
    ExpiresByType image/x-icon "access plus 6 months"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType font/woff2 "access plus 6 months"
    ExpiresByType font/woff "access plus 6 months"
    ExpiresByType font/ttf "access plus 6 months"
    ExpiresByType font/otf "access plus 6 months"
</IfModule>
