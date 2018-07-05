<div class="_container _panelAndNav">
    <div class="_panelAndNav-middle">
        <div class="_panelAndNav-block _panelAndNav-top">
            {block name="top"}{/block}
        </div>

        {* Displayed in 1 or 2 column media queries *}
        <div class="_panelAndNav-block _panelAndNav-leftTop">{block name="topLeft"}{/block}</div>

        {* Displayed in 1 or 2 column media queries *}
        <div class="_panelAndNav-block _panelAndNav-rightTop">{block name="topRight"}{/block}</div>

        <div class="_panelAndNav-block _panelAndNav-main">{block name="main"}{/block}</div>
    </div>
    <div class="_panelAndNav-right">{* Hidden in 1 or 2 column media queries *}
        <div class="_panelAndNav-block _panelAndNav-rightTop">{block name="topRight"}{/block}</div>
        <div class="_panelAndNav-block _panelAndNav-rightBottom">{block name="bottomRight"}{/block}</div>
    </div>
</div>


{block name="demo"}{/block} {* Demo CSS/JS*}
