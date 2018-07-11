<main class="_main">
    {include file="splash.tpl" title="Welcome! How can we help?" paragraph="Find answers, ask questions, and connect with our community of Vanilla users from around the world."}
    {*{include file="_example_standardWidget.tpl"}*}
    {include file="overview.tpl"}
    {*{include file="featured.tpl"}*}
    {include file="featured-cssColumns.tpl"}
</main>

{block name="demo"}
    <style>
        ._container-breadcrumb,
        .Trace {
            display: none;
        }
    </style>
{/block}
