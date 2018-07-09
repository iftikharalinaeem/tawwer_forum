<main class="_container _panelAndNav">
    <div class="_panelAndNav-left">
        {* Visible in 1 column media query *}
        <button class="button button-close button-icon _panelAndNav-close" title="{t c="Close"}">
            <svg class="icon button-closeIcon" viewBox="0 0 24 24">
                <title>{t c="Close"}</title>
                <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
            </svg>
        </button>

        {* Hidden in 1 or 2 column media queries *}
        {*<div class="_panelAndNav-block _panelAndNav-leftTop">{block name="topLeft"}{/block}</div>*}
        <div class="_panelAndNav-block _panelAndNav-leftBottom">
            {include "article_nav.tpl"}
        </div>
    </div>
    <div class="_panelAndNav-content">
        <div class="_panelAndNav-middle">
            <div class="_panelAndNav-block _panelAndNav-top">

                <div class="breadcrumbsWrapper">{breadcrumbs}</div>

                <div class="_pageHeading">
                    <div class="_pageHeading-main">
                        <button class="button button-icon button-menu _panelAndNav-menu" title="{t c="Menu"}" aria-label="{t c="Menu"}">
                            <svg class="icon button-menuIcon" viewBox="0 0 24 24">
                                <title>{t c="Menu"}</title>
                                <rect fill="currentColor" x="3.9" y="11" width="16.1" height="1.9"/>
                                <rect fill="currentColor" x="3.9" y="5.3" width="16.1" height="1.9"/>
                                <rect fill="currentColor" x="3.9" y="16.8" width="16.1" height="1.9"/>
                            </svg>
                        </button>
                        <h1 class="_pageTitle">
                            Integrations
                        </h1>
                    </div>
                    <div class="pageHeading-actions">
                        <div class="_dropDown">
                            <button class="button buttonNoBorder vanillaDropDown-handle">
                                <svg class="icon" viewBox="0 0 20 20">
                                    <title>▼</title>
                                    <path fill="currentColor" stroke-linecap="square" stroke-linejoin="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"></path>
                                </svg>
                            </button>
                            <div class="vanillaDropDown-content">

                            </div>
                        </div>
                    </div>
                </div>

                <div class="_metas">
                    <span class="_meta _meta-author">
                        By Todd Burry
                    </span>
                    <span class="_meta _meta-author">
                        Last Updated <time class="_meta-time" datetime="2018-03-03">3 March 2018</time>
                    </span>
                    <span class="_meta _meta-id">
                        ID #1029384756
                    </span>
                </div>



            </div>

            {* Displayed in 1 or 2 column media queries *}
            <div class="_panelAndNav-block _panelAndNav-leftTop">{block name="topLeft"}{/block}</div>

            {* Displayed in 1 or 2 column media queries *}
            <div class="_panelAndNav-block _panelAndNav-rightTop">{block name="topRight"}{/block}</div>

            <div class="_panelAndNav-block _panelAndNav-main userContent">
                {include "article_text.tpl"}
                {include "article_attachments.tpl"}
                {include "article_helpful.tpl"}
            </div>
        </div>
        <div class="_panelAndNav-right">{* Hidden in 1 or 2 column media queries *}
            <div class="_panelAndNav-block _panelAndNav-rightTop">
                {include "article_toc.tpl"}
                {include "article_realted.tpl"}
            </div>
            <div class="_panelAndNav-block _panelAndNav-rightBottom">{block name="bottomRight"}{/block}</div>
        </div>
    </div>
</main>


<main class="_main">
    {include file="splash.tpl" title="Welcome! How can we help?" paragraph="Find answers, ask questions, and connect with our community of Vanilla users from around the world."}
    {include file="_example_standardWidget.tpl"}
    {include file="overview.tpl"}
    {include file="featured-cssColumns.tpl"}
</main>

<style>
    ._container-breadcrumb,
    .Trace {
        display: none;
    }
</style>
