{* I'm keeping this file as an example, but for the featured articles, I think what we really want is css columns, not hard coded ones. See "featured-cssColumns.tpl *}

{extends file="columns-2.tpl"}

{block name="header"}
    {include "widget-titleBar-viewMore.tpl" title="Featured Articles"}
{/block}

{block name="column1"}
    <div class="_widgetColumn">
        {include "featuredArticle.tpl" title="Change the profile information on your forum account" description="Visit vanillaforums.com/account to change your username, email, team name, and other preferences."}
        {include "featuredArticle.tpl" title="How do I create a Vanilla account?" description="Visit vanillaforums.com/register, or download Vanilla on your computer or mobile device to get started."}
        {include "featuredArticle.tpl" title="How do I delete my account?" description="Learn what happens when you downgrade a paid account, or delete a Basic account at vanillaforums.com/account/delete."}
    </div>
{/block}
{block name="column2"}
    <div class="_widgetColumn">
        {include "featuredArticle.tpl" title="Change the profile information on your forum account" description="Visit vanillaforums.com/account to change your username, email, team name, and other preferences."}
        {include "featuredArticle.tpl" title="How do I create a Vanilla account?" description="Visit vanillaforums.com/register, or download Vanilla on your computer or mobile device to get started."}
        {include "featuredArticle.tpl" title="How do I delete my account?" description="Learn what happens when you downgrade a paid account, or delete a Basic account at vanillaforums.com/account/delete."}
        {include "featuredArticle.tpl" title="Loooooooong Title Too Long To Be A Titl eReally Loooooooong Title Too Long To Be A Title Really Loooooooong Title Too Long To Be A Title Really" description="Learn what happens when you downgrade a paid account,  Loooooooong Title Too Long To Be A Titl eReally Loooooooong Title Too Long To Be A Title Really Loooooooong Title Too Long To Be A Title Really or delete a Basic account at vanillaforums.com/account/delete.Learn what happens when you downgrade a paid account,  Loooooooong Title Too Long To Be A Titl eReally Loooooooong Title Too Long To Be A Title Really Loooooooong Title Too Long To Be A Title Really or delete a Basic account at vanillaforums.com/account/delete.Learn what happens when you downgrade a paid account,  Loooooooong Title Too Long To Be A Titl eReally Loooooooong Title Too Long To Be A Title Really Loooooooong Title Too Long To Be A Title Really or delete a Basic account at vanillaforums.com/account/delete.Learn what happens when you downgrade a paid account,  Loooooooong Title Too Long To Be A Titl eReally Loooooooong Title Too Long To Be A Title Really Loooooooong Title Too Long To Be A Title Really or delete a Basic account at vanillaforums.com/account/delete."}
    </div>
{/block}
{block name="demo"}
    <style>
        .Trace {
            display: none;
        }
    </style>
{/block}
