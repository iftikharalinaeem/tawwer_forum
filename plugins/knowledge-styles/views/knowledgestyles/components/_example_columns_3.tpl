{extends file="columns.tpl"}
{block name="extraClasses"}_columns-3{/block}
{block name="main"}
    <div class="_column">
        {block name="column1"}
            Column 1
        {/block}
    </div>
    <div class="_column">
        {block name="column2"}
            Column 2
        {/block}
    </div>
    <div class="_column">
        {block name="column3"}
            Column 3
        {/block}
    </div>
{/block}
{block name="demo"}
    <style>
        .Trace {
            display: none;
        }
    </style>
{/block}
