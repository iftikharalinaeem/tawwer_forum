{container id="forDemo" class="_panelAndNav _noRightColumn"}
    <div class="_section _panelAndNav-left">
        {* Visible in 1 column media query *}
        <button class="_button _button-close _button-icon _panelAndNav-close" title="{t c="Close"}">
            <svg class="_icon _button-closeIcon" viewBox="0 0 24 24">
                <title>{t c="Close"}</title>
                <path fill="currentColor" d="M12,10.6293581 L5.49002397,4.11938207 C5.30046135,3.92981944 4.95620859,3.96673045 4.69799105,4.22494799 L4.22494799,4.69799105 C3.97708292,4.94585613 3.92537154,5.29601344 4.11938207,5.49002397 L10.6293581,12 L4.11938207,18.509976 C3.92981944,18.6995387 3.96673045,19.0437914 4.22494799,19.3020089 L4.69799105,19.775052 C4.94585613,20.0229171 5.29601344,20.0746285 5.49002397,19.8806179 L12,13.3706419 L18.509976,19.8806179 C18.6995387,20.0701806 19.0437914,20.0332695 19.3020089,19.775052 L19.775052,19.3020089 C20.0229171,19.0541439 20.0746285,18.7039866 19.8806179,18.509976 L13.3706419,12 L19.8806179,5.49002397 C20.0701806,5.30046135 20.0332695,4.95620859 19.775052,4.69799105 L19.3020089,4.22494799 C19.0541439,3.97708292 18.7039866,3.92537154 18.509976,4.11938207 L12,10.6293581 Z"/>
            </svg>
        </button>

        {* Hidden in 1 or 2 column media queries *}
        {*<div class="_panelAndNav-block _panelAndNav-leftTop">{block name="topLeft"}{/block}</div>*}
        {layoutWidget class="_panelAndNav-leftBottom"}
            {include "article_nav.tpl"}
        {/layoutWidget}
    </div>
    <div class="_panelAndNav-content">
        <main class="_section _panelAndNav-middle">
            {layoutWidget class="_panelAndNav-top"}
                <div class="_pageHeading">
                    <div class="_pageHeading-main">
                        <div class="_pageHeading-hamburgerWrapper">
                            <button class="_button _button-icon _button-menu _panelAndNav-menu" title="{t c="Menu"}" aria-label="{t c="Menu"}">
                                <svg class="_icon _button-menuIcon" viewBox="0 0 24 24">
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
                    </div>
                    <div class="pageHeading-actions">
                        <div class="_dropDown">
                            <button class="_button _buttonNoBorder">
                                <svg class="_icon" viewBox="0 0 20 20">
                                    <title>▼</title>
                                    <path fill="currentColor" stroke-linecap="square" fill-rule="evenodd" d="M6.79521339,4.1285572 L6.13258979,4.7726082 C6.04408814,4.85847112 6,4.95730046 6,5.0690962 C6,5.18057569 6.04408814,5.27940502 6.13258979,5.36526795 L11.3416605,10.4284924 L6.13275248,15.4915587 C6.04425083,15.5774216 6.00016269,15.6762509 6.00016269,15.7878885 C6.00016269,15.8995261 6.04425083,15.9983555 6.13275248,16.0842184 L6.79537608,16.7282694 C6.88371504,16.8142905 6.98539433,16.8571429 7.10025126,16.8571429 C7.21510819,16.8571429 7.31678748,16.8141323 7.40512644,16.7282694 L13.5818586,10.7248222 C13.6701976,10.6389593 13.7142857,10.54013 13.7142857,10.4284924 C13.7142857,10.3168547 13.6701976,10.2181835 13.5818586,10.1323206 L7.40512644,4.1285572 C7.31678748,4.04269427 7.21510819,4 7.10025126,4 C6.98539433,4 6.88371504,4.04269427 6.79521339,4.1285572 L6.79521339,4.1285572 Z" transform="rotate(90 9.857 10.429)"></path>
                                </svg>
                            </button>
                            <div class="vanillaDropDown-content">

                            </div>
                        </div>
                    </div>
                </div>

                <div class="_pageMetas">
                    <div class="vanillaDropDown-content">
                        <div class="ToggleFlyout selectBox _selectBox">
                            <div class="selectBox-label">
                                View:
                            </div>
                            <div class="selectBox-main">
                                <a href="#" role="button" rel="nofollow" class="FlyoutButton selectBox-toggle" tabindex="0" aria-haspopup="true" aria-expanded="false">
                                    <span class="selectBox-selected">Date Updated</span>
                                    <span class="vanillaDropDown-arrow">▾</span>
                                </a>
                                <ul class="Flyout MenuItems selectBox-content" role="presentation" aria-hidden="true">
                                    <li class="selectBox-item isActive" role="presentation">
                                        <a href="#" role="menuitem" class="dropdown-menu-link selectBox-link" tabindex="0" aria-current="location">
                                            <svg class="vanillaIcon selectBox-selectedIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18">
                                                <title>✓</title>
                                                <polygon fill="currentColor" points="1.938,8.7 0.538,10.1 5.938,15.5 17.337,3.9 15.938,2.5 5.938,12.8"></polygon>
                                            </svg>
                                            <span class="selectBox-selectedText">Date Updated</span>
                                        </a>
                                    </li>
                                    <li class="selectBox-item" role="presentation">
                                        <a href="/categories?followed=1&amp;save=1&amp;TransientKey=qEMHue10d9qFruXW" role="menuitem" class="dropdown-menu-link selectBox-link" tabindex="0">
                                            Date Created
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            {/layoutWidget}

            {layoutWidget class="_panelAndNav-main userContent"}
                <div class="_searchResults">
                    {include "_category_result.tpl" excel="true" word="true" pdf="true" image="https://vanillaforums.com/images/metaIcons/vanillaForums.png" title="Getting Help with your community" excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="false" word="false" pdf="false" image="https://vanillaforums.com/images/metaIcons/vanillaForums.png" title="Vanilla support needed: Poll feature not workingVanilla support needed: Poll feature not workingVanilla support needed: Poll feature not workingVanilla support needed: Poll feature not workingVanilla support needed: Poll feature not working" excerpt="Currently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feed"}
                    {include "_category_result.tpl" excel="false" word="true" pdf="true" title="Short" excerpt="Short"}
                    {include "_category_result.tpl" excel="true" word="false" pdf="true" title="How To Help If Everything Else Fails" excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="true" word="true" pdf="false" image="https://vignette.wikia.nocookie.net/mrmen/images/d/d2/Mrtallimage.png/revision/latest/scale-to-width-down/250?cb=20130222100629" title="Popular Help Documents and Videos" excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="false" word="false" pdf="true" title="Installation Help" excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="false" word="true" pdf="false" title="wmj710 and the spectacle earned the Seventh Anniversary badge." excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="true" word="false" pdf="false" title="LongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongword" excerpt="Standard with your order of the Plantronics CT12 wireless headset phone is a two in one headset that is convertible so you can use it over the head for stability or over the ear for convenience. It has a microphone that is especially designed to cancel out background noises as well as top notch clarity of sound."}
                    {include "_category_result.tpl" excel="false" word="false" pdf="true" title="Unreasonable" excerpt="LongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongwordLongword"}
                </div>
            {/layoutWidget}

            {include "pager-with-result-count.tpl"}
        </main>
    </div>
{/container}

<style>
    .Trace {
        display: none;
    }
</style>
<script>
    $("#forDemo ._panelAndNav-menu").on('click', function(){
        $("#forDemo ._panelAndNav-left").toggleClass('isOpen');
    });

    $("#forDemo ._panelAndNav-close").on('click', function(){
        $("#forDemo ._panelAndNav-left").removeClass('isOpen');
    });
</script>


{block name="demo"}
    <style>
        .Trace {
            display: none;
        }
    </style>
{/block}
