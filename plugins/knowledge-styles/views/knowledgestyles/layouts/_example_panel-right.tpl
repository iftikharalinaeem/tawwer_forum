{extends file="panel-right.tpl"}

{block name="demo"}
    <style>

        ._panelAndNav-block {
            padding: 20px;
        }

        ._panelAndNav-top {
            background: yellow;
            min-height: 235px;
        }

        ._panelAndNav-main {
            background: green;
            min-height: 500px;
        }

        ._panelAndNav-rightTop {
            background: blue;
            min-height: 365px;
        }

        ._panelAndNav-rightBottom {
            background: red;
            min-height: 800px;
        }

        .Trace {
            display: none;
        }
    </style>
{/block}
{block name="top"}
    <div class="panelCol">
        Top
    </div>
{/block}
{block name="main"}
    <div class="panelCol">
        Main
    </div>
{/block}
{block name="topRight"}
    <div class="panelCol">
        Top Right
    </div>
{/block}
{block name="bottomRight"}
    <div class="panelCol">
        Bottom Right
    </div>
{/block}



