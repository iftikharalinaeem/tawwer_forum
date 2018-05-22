<!DOCTYPE html>
<html lang="en" class="sticky-footer-html">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {asset name="Head"}
  </head>
  <body id="{$BodyID}" class="{$BodyClass} sticky-footer-body">
    <nav class="navbar navbar-default navbar-static-top" role="navigation">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">{t c="Toggle navigation"}</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="{link path="home"}">{logo}</a>
        </div>

        <div class="navbar-collapse collapse">
          {if $User.SignedIn}
            <ul class="nav navbar-nav navbar-right hidden-xs">
              {module name="MeModule"}
            </ul>
            <ul class="nav navbar-nav navbar-right visible-xs">
              {profile_link}
              {inbox_link}
              {bookmarks_link}
              {dashboard_link}
              {signinout_link}
            </ul>
          {else}
            <ul class="nav navbar-nav navbar-right">
              {signin_link}
            </ul>
          {/if}
        </div>
      </div>
    </nav>

    <section class="container">
      <nav class="navbar navbar-default" role="navigation">

        <div class="navbar-header">
          <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
            <span class="sr-only">{t c="Toggle navigation"}</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
        </div>

        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            {home_link}
            {categories_link}
            {discussions_link}
            {activity_link}
            {custom_menu}
          </ul>
          <div class="navbar-form navbar-right js-sphinxAutoComplete" role="search">
            {searchbox}
          </div>
        </div>
      </nav>

      {if InSection(array("CategoryList", "CategoryDiscussionList"))}
        <div class="promoted-content">
          <div class="swiper-pagination"></div>

          <h4>{t c="Latest Administrator Posts"}</h4>

          {* Change the `Selector` and `Selection` to suit your needs! *}
          {module name="PromotedContentModule" Selector="Role" Selection="Administrator" BodyLimit=300}
        </div>
      {/if}

      {breadcrumbs}

      <div class="page-wrap clearfix">
        <div class="row">
          <main class="page-content" role="main">
            {*
            {if InSection(array("CategoryList", "CategoryDiscussionList", "DiscussionList"))}
              <div class="well search-form">{searchbox}</div>
            {/if}
            *}
            {asset name="Content"}
          </main>

          <aside class="page-sidebar" role="complementary">
            {asset name="Panel"}
          </aside>
        </div>
      </div>
    </section>

    <footer class="page-footer sticky-footer">
      <div class="container">
        <div class="clearfix">
          <p class="pull-left">{t c="Copyright"} &copy; {$smarty.now|date_format:"%Y"} <a href="{link path="home"}">{logo}</a></p>
          <p class="pull-right hidden-xs">Powered by <a href="{vanillaurl}">Vanilla Forums</a></p>
        </div>
        {asset name="Foot"}
      </div>
    </footer>

    {event name="AfterBody"}

  </body>
</html>
