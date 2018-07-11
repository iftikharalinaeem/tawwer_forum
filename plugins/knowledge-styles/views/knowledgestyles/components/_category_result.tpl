<article class="_searchResult">
    <a href="#" class="_searchResult-contents">
        <div class="_searchResult-head">
            <h3 class="_searchResult-title">
                {$title|default:'Getting Help with your community'}
            </h3>

            {if $excel or $word or $pdf}
                <div class="_searchResult-attachments _formatIcons">
                    {if $excel === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Excel Document</title>
                            <rect width="20" height="20" style="fill: #2f7d32;stroke: currentColor;stroke-width: 0.5px"/>
                            <polygon points="10 11.089 8.125 14.272 6.666 14.272 9.279 9.895 6.83 5.728 8.295 5.728 10 8.694 11.705 5.728 13.164 5.728 10.721 9.895 13.334 14.272 11.869 14.272 10 11.089" style="fill: #fff"/>
                        </svg>
                    {/if}
                    {if $word === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>Word Document</title>
                            <rect width="20" height="20" style="fill: #2b5599;stroke: currentColor;stroke-width: 0.5px"/>
                            <polygon points="7.3,14.3 5.1,5.7 6.5,5.7 7.8,11.8 9.5,5.7 10.5,5.7 12.1,11.8 13.5,5.7 14.9,5.7 12.7,14.3 11.6,14.3 10,8.1 8.4,14.3" style="fill: #fff"/>
                        </svg>
                    {/if}
                    {if $pdf === "true"}
                        <svg class="_formatIcon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <title>PDF Document</title>
                            <rect width="20" height="20" style="fill: #ff3934;stroke: currentColor;stroke-width: 0.5px"/>
                            <path d="M3.2,14.4V5.6h1.9c0.3,0,0.6,0,0.9,0.1C6.2,5.9,6.5,6,6.7,6.2C6.9,6.5,7.1,6.7,7.1,7c0.1,0.4,0.1,0.8,0.1,1.3c0,0.3,0,0.7-0.1,1c0,0.3-0.1,0.5-0.3,0.8c-0.2,0.3-0.4,0.5-0.7,0.7C5.9,10.9,5.5,11,5.1,11H4.4v3.4L3.2,14.4z M4.4,6.8v3H5c0.2,0,0.4,0,0.6-0.1c0.1-0.1,0.2-0.2,0.3-0.3C6,9.2,6,9.1,6,8.9c0-0.2,0-0.4,0-0.6s0-0.4,0-0.6c0-0.2,0-0.3-0.1-0.5C5.9,7.1,5.8,7,5.6,6.9C5.5,6.8,5.3,6.8,5.1,6.8L4.4,6.8z M8.2,14.4V5.6H10c0.6,0,1.2,0.2,1.6,0.6c0.4,0.5,0.6,1.1,0.6,1.7V12c0.1,0.7-0.2,1.3-0.6,1.8c-0.5,0.4-1.1,0.6-1.7,0.6L8.2,14.4z M9.4,6.8v6.4H10c0.3,0,0.6-0.1,0.8-0.3c0.2-0.2,0.2-0.5,0.2-0.8V7.9c0-0.3-0.1-0.6-0.2-0.8c-0.2-0.2-0.5-0.3-0.8-0.3L9.4,6.8z M13.4,14.4V5.6h3.7v1.2h-2.5v2.7h2.2v1.2h-2.2v3.7H13.4z" style="fill: #fff"/>
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
                            <li class="_metaBreadcrumb-item _metaBreadcrumb-separator" aria-hidden="true"><span class="breadcrumbs-separatorIcon">›</span></li>
                            <li class="_metaBreadcrumb-item">
                                <span class="_metaBreadcrumb-label" itemprop="name">The</span>
                            </li>
                            <li class="_metaBreadcrumb-item _metaBreadcrumb-separator" aria-hidden="true"><span class="breadcrumbs-separatorIcon">›</span></li>
                            <li class="_metaBreadcrumb-item">
                                <span class="_metaBreadcrumb-label" itemprop="name">Breadcrumb</span>
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
