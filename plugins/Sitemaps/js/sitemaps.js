jQuery(document).ready(function(){
   var SitemapsBuildURL = gdn.definition('WebRoot')+'/plugin/sitemaps/build';
   jQuery.ajax({
      url: SitemapsBuildURL,
      type: 'POST'
   });
});