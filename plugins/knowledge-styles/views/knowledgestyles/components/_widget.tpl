<div class="_widget"> {* Used for spacing, don't add background here - has the "outer" padding and the "gap" margin *}
    <div class="_widget-contents"> {* Used for full background *}
        <div class="_widget-head">
            {block name="head"}
                Widget Head
            {/block}
        </div>
        <div class="_widget-main"> {* Helps if header is different than rest *}
            <div class="_widget-body">
                {block name="body"}
                    Widget Body
                {/block}
            </div>
            <div class="_widget-footer">
                {block name="footer"}
                    Widget Footer
                {/block}
            </div>
        </div>
    </div>
</div>
