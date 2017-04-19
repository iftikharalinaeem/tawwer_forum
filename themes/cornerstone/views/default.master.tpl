<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {asset name="Head"}
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body id="{$BodyID}" class="{$BodyClass}">
    <!--[if lt IE 8]>
      <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
    <![endif]-->

    <header class="site-header" role="banner">
      <nav class="topbar" role="navigation">
        <div class="container">
          {module name="MeModule"}
        </div>
      </nav>
      <nav class="navbar" role="navigation">
        <div class="container">
          <a class="navbar-brand" href="{link path="home"}">{logo}</a>

          <div class="navbar-right">

            <ul class="nav navbar-nav pull-left">
              {categories_link}
              {discussions_link}
              {activity_link}
              {custom_menu}
            </ul>

            <div class="navbar-form navbar-search pull-left">
              {searchbox}
            </div>

          </div>

        </div>
      </nav>
    </header>

    <main class="container site-container" role="main">

      <nav class="trail">
        {breadcrumbs}
      </nav>

      <div class="row">

        <section class="site-content">
          {asset name="Content"}
        </section>

        <aside class="site-sidebar">
          {asset name="Panel"}
        </aside>

      </div>

    </main>

    <footer class="site-footer" role="contentinfo">
      <div class="container">
        <p class="pull-left">{t c="Copyright"} &copy; {$smarty.now|date:"%Y"} <a href="{link path="home"}">{logo}</a>. {t c="All rights reserved"}.</p>

        <p class="pull-right PoweredByVanilla-Wrap"><a href="//vanillaforums.com">Powered by Vanilla</a></p>
      </div>
    </footer>

    {asset name="Foot"}
    {event name="AfterBody"}
  </body>
</html>
