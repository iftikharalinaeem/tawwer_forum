<article class="_searchResult">
    <a href="#" class="_searchResult-contents">
        <div class="_searchResult-head">
            <h3 class="_searchResult-title">
                {$title|default:'Getting Help with your community'}
            </h3>

            {if $excel or $word or $pdf}
                <div class="_searchResult-attachments _formatIcons">
                    {if $excel === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="18.5 18.5">
                            <title>Excel</title>
                            <rect x="0.25" y="0.25" width="18" height="18" rx="1" ry="1" style="fill: #2f7d32;stroke: #000;stroke-width: 0.5px"/>
                            <polygon points="9.584 10.611 7.709 13.793 6.25 13.793 8.863 9.416 6.414 5.25 7.879 5.25 9.584 8.215 11.289 5.25 12.748 5.25 10.305 9.416 12.918 13.793 11.453 13.793 9.584 10.611" style="fill: #fff"/>
                        </svg>
                    {/if}
                    {if $word === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="18.5 18.5">
                            <title>Word</title>
                            <rect x="0.25" y="0.25" width="18" height="18" rx="1" ry="1" style="fill: #2b5599;stroke: #000;stroke-width: 0.5px"/>
                            <polygon points="6.383 13.793 4.25 5.25 5.615 5.25 6.957 11.32 8.639 5.25 9.576 5.25 11.229 11.32 12.6 5.25 13.965 5.25 11.832 13.793 10.748 13.793 9.119 7.635 7.461 13.793 6.383 13.793" style="fill: #fff"/>
                        </svg>
                    {/if}
                    {if $pdf === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="18.5 18.5">
                            <title>PDF</title>
                            <rect x="0.25" y="0.25" width="18" height="18" rx="1" ry="1" style="fill: #ff3934;stroke: #000;stroke-width: 0.5px"/>
                            <path d="M5,16.767V8H6.884a2.815,2.815,0,0,1,.911.135,1.75,1.75,0,0,1,.714.481,1.889,1.889,0,0,1,.444.806,5.053,5.053,0,0,1,.123,1.25,6.2,6.2,0,0,1-.068,1,2.1,2.1,0,0,1-.289.764,1.851,1.851,0,0,1-.69.671,2.325,2.325,0,0,1-1.133.24h-.64V16.77ZM6.256,9.182v2.98h.6a1.29,1.29,0,0,0,.591-.111.7.7,0,0,0,.308-.308,1.112,1.112,0,0,0,.117-.455c.012-.181.019-.382.019-.6s0-.4-.013-.585a1.254,1.254,0,0,0-.111-.486.7.7,0,0,0-.295-.32,1.163,1.163,0,0,0-.566-.111Zm3.755,7.585V8h1.86a2.159,2.159,0,0,1,1.644.591,2.343,2.343,0,0,1,.56,1.675v4.1a2.446,2.446,0,0,1-.6,1.816,2.356,2.356,0,0,1-1.718.585Zm1.256-7.585v6.4h.579a.931.931,0,0,0,.751-.265,1.279,1.279,0,0,0,.222-.831v-4.22a1.323,1.323,0,0,0-.21-.8.891.891,0,0,0-.763-.283Zm3.99,7.585V8H19V9.182H16.513v2.66H18.68v1.182H16.513v3.743Z" transform="translate(-2.75 -2.75)" style="fill: #fff"/>
                        </svg>
                    {/if}
                </div>
            {/if}
        </div>

        <div class="_searchResult-main">
            {if isset($image)}
                <div class="_searchResult-mediaPreview">
                    <div class="_searchResult-mediaPreviewFrame">
                        <img src="{$image}" class="_searchResult-mediaPreviewImage" alt="Some Image or Video Preview">
                    </div>
                </div>
            {/if}
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

        </div>
    </a>

</article>
