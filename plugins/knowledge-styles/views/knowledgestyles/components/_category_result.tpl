<article class="_searchResult">
    <a href="#" class="_searchResult-main">
        <div class="_searchResult-head">
            <h3 class="_searchResult-title">
                {$title|default:'Getting Help with your community'}
            </h3>
            {if $excel}
                <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <title>Excel</title>
                    <rect x="3" y="3" width="18" height="18" rx="1" ry="1" style="fill: #2f7d32"/>
                    <polygon points="12.334 13.361 10.459 16.543 9 16.543 11.613 12.166 9.164 8 10.629 8 12.334 10.965 14.039 8 15.498 8 13.055 12.166 15.668 16.543 14.203 16.543 12.334 13.361" style="fill: #fff"/>
                </svg>
            {/if}
            {if $word}
                <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <title>Word</title>
                    <rect x="3" y="3" width="18" height="18" rx="1" ry="1" style="fill: #2b5599; border: solid currentColor 1px;"/>
                    <polygon points="9.133 16.543 7 8 8.365 8 9.707 14.07 11.389 8 12.326 8 13.979 14.07 15.35 8 16.715 8 14.582 16.543 13.498 16.543 11.869 10.385 10.211 16.543 9.133 16.543" style="fill: #fff;"/>
                </svg>

            {/if}
            {if $pdf}
                <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <title>PDF</title>
                    <rect x="3" y="3" width="18" height="18" rx="1" ry="1" style="fill: #ff3934;border: solid currentColor 1px;"/>
                    <path d="M5,16.767V8H6.884a2.808,2.808,0,0,1,.911.135,1.75,1.75,0,0,1,.714.481,1.881,1.881,0,0,1,.444.806,5.054,5.054,0,0,1,.123,1.25,6.209,6.209,0,0,1-.068,1,2.1,2.1,0,0,1-.289.764,1.843,1.843,0,0,1-.69.671,2.321,2.321,0,0,1-1.133.24h-.64v3.423ZM6.256,9.182v2.98h.6a1.279,1.279,0,0,0,.591-.111.7.7,0,0,0,.308-.308,1.114,1.114,0,0,0,.117-.455c.012-.181.019-.382.019-.6,0-.205,0-.4-.013-.585a1.259,1.259,0,0,0-.111-.486.7.7,0,0,0-.295-.32,1.169,1.169,0,0,0-.566-.111Zm3.755,7.585V8h1.86a2.161,2.161,0,0,1,1.644.591,2.341,2.341,0,0,1,.56,1.675v4.1a2.445,2.445,0,0,1-.6,1.816,2.356,2.356,0,0,1-1.718.585Zm1.256-7.585v6.4h.579a.93.93,0,0,0,.751-.265,1.278,1.278,0,0,0,.222-.831V10.266a1.325,1.325,0,0,0-.21-.8.892.892,0,0,0-.763-.283Zm3.99,7.585V8H19V9.182H16.513v2.66H18.68v1.182H16.513v3.743Z" style="fill: #fff;"/>
                </svg>
            {/if}
        </div>

        <div class="_searchResult-main">
            <div class="_searchResult-text">
                <div class="_metas">
                    <span class="_meta">
                        Article by Todd Burry
                    </span>
                    <span class="_meta">
                        <time class="_meta-time" datetime="2016-06-01">1 August 2016</time>
                    </span>
                    <span aria-label="Article Location" class="_meta _metaBreadcrumb">
                        <ol class="_metaBreadcrumb">
                            <li class="_metaBreadcrumb-item">
                                <span class="_metaBreadcrumb-label" itemprop="name">This</span>
                            </li>
                            <li class="_metaBreadcrumb-item _metaBreadcrumb-separator" aria-hidden="true"><span class="breadcrumbs-separatorIcon">›</span></li>
                            <li class="_metaBreadcrumb-item">
                                <span class="_metaBreadcrumb-label" itemprop="name">Is</span>
                            </li>
                        </ol>
                    </span>
                </div>
                <p class="_searchResult-excerpt">
                    {$excerpt|default:'Currently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feedCurrently when I use my RSS feed, each discussion has no image attached to it. How can I assign (even manually to the same image for all discussions) an image to it, so that e.g. if I use the RSS feed'}
                </p>
            </div>
            {if $image}
            <div class="_searchResult-mediaPreview">
                <div class="_searchResult-mediaPreviewFrame">
                    <img src="{$image}" class="_searchResult-mediaPreviewImage" alt="Some Image or Video Preview">
                </div>
            </div>
            {/if}
        </div>
    </a>
</article>
