<div class="_container">
    <div class="_panelAndNav">
        <div class="_panelAndNav-left">

            {* Visible in 1 column media query *}
            <button class="button button-close button-icon _panelAndNav-close" title="{t c="Close"}">
                <svg class="icon button-closeIcon" viewBox="0 0 24 24">
                    <title>{t c="Close"}</title>
                    <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
                </svg>
            </button>

            {* Hidden in 1 or 2 column media queries *}
            <div class="_panelAndNav-block _panelAndNav-leftTop">
                {block name="topLeft"}{/block}
            </div>
            <div class="_panelAndNav-block _panelAndNav-leftBottom">
                {block name="bottomLeft"}{/block}
            </div>
        </div>
        <div class="_panelAndNav-content">
            <div class="_panelAndNav-middle">
                <div class="_panelAndNav-block _panelAndNav-top">

                    {* Visible in 1 column media query *}
                    <button class="button button-icon button-menu _panelAndNav-menu" title="{t c="Menu"}" aria-label="{t c="Menu"}">
                        <svg class="icon button-menuIcon" viewBox="0 0 24 24">
                            <title>{t c="Menu"}</title>
                            <rect fill="currentColor" x="3.9" y="11" width="16.1" height="1.9"/>
                            <rect fill="currentColor" x="3.9" y="5.3" width="16.1" height="1.9"/>
                            <rect fill="currentColor" x="3.9" y="16.8" width="16.1" height="1.9"/>
                        </svg>
                    </button>
                    {block name="top"}{/block}
                </div>

                {* Displayed in 1 or 2 column media queries *}
                <div class="_panelAndNav-block _panelAndNav-leftTop">
                    {block name="topLeft"}{/block}
                </div>

                {* Displayed in 1 or 2 column media queries *}
                <div class="_panelAndNav-block _panelAndNav-rightTop">
                    {block name="topRight"}{/block}
                </div>

                <div class="_panelAndNav-block _panelAndNav-main">
                    {block name="main"}{/block}
                </div>
            </div>
            <div class="_panelAndNav-right">
                {* Hidden in 1 or 2 column media queries *}
                <div class="_panelAndNav-block _panelAndNav-rightTop">
                    {block name="topRight"}{/block}
                </div>
                <div class="_panelAndNav-block _panelAndNav-rightBottom">
                    {block name="bottomRight"}{/block}
                </div>
            </div>
        </div>
    </div>
</div>


{block name="demo"}{/block} {* Demo Styles Block*}
