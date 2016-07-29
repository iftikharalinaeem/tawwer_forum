<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {asset name="Head"}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Lato:400,700,400italic,700italic">
  </head>
  <body id="{$BodyID}" class="{$BodyClass}">
    <!--[if lt IE 8]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <nav class="navbar" role="navigation">
      <div class="container">
        <a class="navbar-brand" href="{link path="home"}">{logo}</a>
        <div class="navbar-right">
          {if !$User.SignedIn}
          <a class="button" href="{link path="entry/signin"}">{t c="Sign In"}</a>
          <a class="button" href="{link path="entry/register"}">{t c="Register"}</a>
          {else}
          <div class="mebox">{module name="MeModule" CssClass="Inline FlyoutRight"}</div>
          {/if}
        </div>
      </div>
    </nav>

    {if InSection(array("CategoryList", "DiscussionList"))}
      <div class="masthead" data-geopattern="{$Title}">
        <div class="container">

          <h1 class="text-center">{t c="How can we help you?"}</h1>

          {searchbox}

        </div>
      </div>
    {/if}

    <nav class="trail">
      <div class="container">
        {breadcrumbs}
      </div>
    </nav>

    <main class="container" role="main">

      <section class="site-content column column-content">
        {asset name="Content"}
      </section>

      <aside class="site-sidebar column column-sidebar" role="complementary">
        {asset name="Panel"}
      </aside>

    </main>

    <footer class="site-footer" role="contentinfo">
      <div class="container">
        <p class="pull-left">{t c="Copyright"} &copy; {$smarty.now|date:"%Y"}. {t c="All rights reserved"}.</p>
        <p class="pull-right">{t c="Powered by"} <a href="http://vanillaforums.com">Vanilla Forums</a></p>
      </div>
    </footer>

    {asset name="Foot"}
    {event name="AfterBody"}
  </body>
</html>
