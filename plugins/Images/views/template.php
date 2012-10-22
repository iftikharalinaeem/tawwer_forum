<?php if (!defined('APPLICATION')) exit(); ?>

<!-- The template to display files available for upload -->
<script id="template-upload" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}<div class="ImageWrap template-upload">
      <div class="Image preview"><span class="fade"></span></div>
      <div class="Filename">{%=file.name%}</div>
      {% if (file.error) { %}
         <div class="Warning error">{%=locale.fileupload.error%}: {%=locale.fileupload.errors[file.error] || file.error%}</div>
      {% } else if (o.files.valid && !i) { %}
         <div class="Progress">
             <div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="bar" style="width:0%;"></div></div>
         </div>
      {% } %}
      {% if (!i) { %}
      <div class="Cancel cancel">
         <button class="btn btn-warning Button">
            <i class="icon-ban-circle icon-white"></i>
            <span>{%=locale.fileupload.cancel%}</span>
         </button>
      </div>
      {% } %}
    </div>{% } %}
</script>
<!-- The template to display files available for download -->
<script id="template-download" type="text/x-tmpl">
{% for (var i=0, file; file=o.files[i]; i++) { %}<div class="ImageWrap template-download fade">
   {% if (file.error) { %}
      <div class="Filename filename">{%=file.name%}</span></div>
      <div class="Warning error">{%=locale.fileupload.error%}: {%=locale.fileupload.errors[file.error] || file.error%}</div>
   {% } else { %}
      {% if (file.thumbnail_url) { %}
      <div class="Image preview">
         <!-- <a href="{%=file.url%}" title="{%=file.name%}" rel="gallery" download="{%=file.name%}"><img src="{%=file.thumbnail_url%}"></a> -->
         <img src="{%=file.thumbnail_url%}">
      </div>
      {% } %}
      <div class="Caption">
         <input type="hidden" name="Image[]" value="{%=file.url%}" />
         <input type="hidden" name="Thumbnail[]" value="{%=file.thumbnail_url%}" />
         <input type="hidden" name="Size[]" value="{%=file.size%}" />
         <input type="text" name="Caption[]" class="InputBox" placeholder="Enter a caption..." value="{%=file.caption%}" />
      </div>
    {% } %}
    <div class="Delete delete">
      <button tabindex="-1" class="Button DeleteButton btn btn-danger" data-type="{%=file.delete_type%}" data-url="{%=file.delete_url%}">
          <i class="icon-trash icon-white"></i>
          <span><?php echo T('Remove'); ?></span>
      </button>
    </div>
   </div>{% } %}
</script>