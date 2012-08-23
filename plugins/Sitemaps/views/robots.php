<?php if (!defined('APPLICATION')) exit;
echo 'Sitemap: '.Url('/sitemapindex.xml', TRUE)."\n";
?>

User-agent: *
Disallow: /entry/
Disallow: /messages/
Disallow: /profile/comments/
Disallow: /profile/discussions/
Disallow: /search/