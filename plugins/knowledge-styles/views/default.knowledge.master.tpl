<!DOCTYPE html>
<html lang="{$CurrentLocale.Lang}">
<head>
    {asset name="Head"}
</head>
<body id="{$BodyID}" class="{$BodyClass}">
    <div id="Frame">
        {*<div class="Head" id="Head" role="banner">*}
            {*<div class="Row">*}
                {*<strong class="SiteTitle"><a href="{link path="/"}">{logo}</a></strong>*}
                {*<div class="SiteSearch" role="search">{searchbox}</div>*}
                {*<ul class="SiteMenu">*}
                    {*{discussions_link}*}
                    {*{activity_link}*}
                    {*{custom_menu}*}
                {*</ul>*}
            {*</div>*}
        {*</div>*}
        <div id="Body">
            <div class="_container _container-breadcrumb">
                <div class="breadcrumbsWrapper">{breadcrumbs}</div>
            </div>
            <div class="_pageContents">
                <div class="_fullBackgroundContainer"></div>
                {asset name="Header"}
                <div class="_messages"></div>
                {asset name="Content"}
                <div class="_stickyBottom"></div>
                <div class="_overlays"></div>
            </div>
        </div>
        {*<div id="Foot" role="contentinfo">*}
            {*<div class="Row">*}
                {*<a href="{vanillaurl}" class="PoweredByVanilla" title="Community Software by Vanilla Forums">Forum Software Powered by Vanilla</a>*}
                {*{asset name="Foot"}*}
            {*</div>*}
        {*</div>*}
    </div>
    {event name="AfterBody"}
    </body>
</html>
