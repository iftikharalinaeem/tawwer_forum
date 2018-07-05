{extends file="columns.tpl"}
{block name="main"}
    <div class="_column">
        Column 1
    </div>
    <div class="_column">
        Column 2
    </div>
{/block}
{block name="demo"}
    <style>
        ._column {
            padding: 20px;
            background: orange;
            height: 300px;
        }

        .Trace {
            display: none;
        }
    </style>
{/block}
