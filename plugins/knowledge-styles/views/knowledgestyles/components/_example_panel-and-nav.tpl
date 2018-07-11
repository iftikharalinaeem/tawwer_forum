{extends file="panel-and-nav.tpl"}

{block name="demo"}
    <style>

        ._panelAndNav-block {
            padding: 20px;
        }

        ._panelAndNav-leftTop {
            background: orange;
            min-height: 300px;
        }

        ._panelAndNav-leftBottom {
            background: pink;
            min-height: 743px;
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
    <script>
        document.querySelector("._panelAndNav-menu").addEventListener('click', function(){
            document.querySelector("._panelAndNav-left").classList.toggle('isOpen');
        });

        document.querySelector("._panelAndNav-close").addEventListener('click', function(){
            document.querySelector("._panelAndNav-left").classList.remove('isOpen');
        });
    </script>
{/block}

{block name="topLeft"}
    <div class="panelCol">
        Top Left
    </div>
{/block}
{block name="bottomLeft"}
    <div class="panelCol">
        Bottom Left
    </div>
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



