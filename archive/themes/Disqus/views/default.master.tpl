<!DOCTYPE html>
<html lang="en">
<head>
    {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">

    <header id="Head" class="Head">
        <div class="Row">

            <h1 class="Logo">{logo}</h1>

            <div class="SiteSearch">
                {searchbox placeholder="Enter your search terms"}
            </div>

        </div><!-- /Row -->
    </header><!-- /Head -->

    <section id="Body" class="Body">
        <div class="Row">

            <nav class="Nav">
                {module name="MeModule" CssClass="Inline FlyoutRight"}
                <ul class="SiteMenu">
                    {discussions_link}
                    {activity_link}
                </ul>
                <div class="BreadcrumbsWrapper">
                    {breadcrumbs}
                </div>
            </nav><!-- /Nav -->

            <aside id="Panel" class="Panel">
                {asset name="Panel"}
            </aside><!-- /Panel -->

            <section id="Content" class="Content">
                {asset name="Content"}
            </section><!-- /Panel -->

        </div><!-- /Row -->
    </section><!-- /Body -->

    <footer id="Foot" class="Foot">
        <div class="Row">
            <p><a href="{vanillaurl}" title="Community Software by Vanilla Forums">Powered by Vanilla</a></p>
        </div><!-- /Row -->
    </footer><!-- /Foot -->

    {asset name="Foot"}
    {event name="AfterBody"}
</body>
</html>