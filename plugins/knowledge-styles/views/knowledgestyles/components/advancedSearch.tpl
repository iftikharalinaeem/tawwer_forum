{container}
    <div class="_fullWidthLayout">
        {layoutWidget}
            <h1 class="_pageTitle sr-only">
                Search
            </h1>
            {include file="searchBar.tpl" advanced="true"}
            <h2 class="pageSubTitle sr-only">
                Search Results for: "search term"
            </h2>

            <div class="_pageMetas">
                <div class="vanillaDropDown-content">
                    <div class="ToggleFlyout selectBox _selectBox">
                        <span class="selectBox-label">Sort by: </span>
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
    </div>
{/container}
<script>
    $('._searchBar').each(function(){
        $bar = $(this);
        $input = $bar.find('._searchBar-input');
        $wrap = $bar.find("._splash-advancedSearchWrap");
        $advanced = $bar.find("._splash-advancedSearch");
        $toggle = $bar.find('._searchBar-toggleAdvanced');
        $cancel = $bar.find('._searchBar-cancel');

        $toggle.on('click', function() {
            $bar.toggleClass('_showAdvancedSearch');
            $advanced.attr("aria-pressed", !($advanced.attr("aria-pressed") === "true"));
            $bar.find('._advancedSearchFields-title').focus();
        });

        $cancel.on('click', function() {
            $bar.toggleClass('_showAdvancedSearch');
            $advanced.attr("aria-pressed", !($advanced.attr("aria-pressed") === "true"));
        });

        $wrap.on('webkitAnimationEnd animationend', function(){
            var isOpen = ($advanced.attr("aria-pressed") === "true");
            $wrap.style.display = isOpen ? "block" : "none";
        });

        $input.on("focusin", function(){
            $bar.addClass("_showAutoComplete");
        });

        $input.on("focusout", function(){
            $bar.removeClass("_showAutoComplete");
        });

    });

    $('#advancedSearch-toggle').on('click', function(){
        $(this).closest('._searchBar').toggleClass("showingAdvancedSearch");
    });
</script>
<style>
    .Trace { display: none; }
</style>
