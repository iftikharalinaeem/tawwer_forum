<?php if (!defined('APPLICATION')) exit; ?>
<style>
    .Complete {
        color: #aaa;
        text-decoration: line-through;
    }
</style>
<h1>Move to Cloud Files</h1>

<div class="Info">
    Move your files to cloud files.
</div>

<div class="Wrap">

    <?php
    foreach ($this->Data('Urls') as $Url => $Label) {
        echo <<<EOT
<div class="P">
   $Label <span class="MoveJob" rel="$Url"> </span>
</div>
EOT;
    }
    ?>

</div>

<script>
    jQuery(document).ready(function($) {
        var moveFiles = function() {
            var url = $(this).attr('rel');
            var curr = this;

            $(curr).addClass('TinyProgress');

            $.ajax({
                url: gdn.url(url),
                type: 'POST',
                data: {One: true},
                success: function(data) {
                    if (!data.Complete) {
                        moveFiles.call(curr);
                    } else {
                        $(curr).removeClass('TinyProgress');
                        $(curr).closest('.P').addClass('Complete');
                    }
                },
                error: function(xhr) {
                    gdn.informError(xhr);
                }
            });
        };


        $('.MoveJob').each(moveFiles);
    });
</script>