$(document).ready(function(){
    // When a category is selected on the discussion creation page refresh the entire page with the categoryID
    // so that the image upload link can be shown or not shown based on category settings.
    $('body').on('change', '#Form_CategoryID', function() {
        if ( gdn.meta['ImageRequiredCategory']) {
            var newURL = gdn.getMeta('WebRoot') + "/post/discussion/" + $(this).val();
            window.location = newURL;
        }
    });

    // When submitting the form for discussion creation, populate the form field "imageName"
    // for the purposes of validating because the image upload is in another in the FileUpload plugin.
    $('body').on("click", ".DiscussionButton", function() {
        $('#Form_imageName').val($('.AttachFileContainer img.ImageThumbnail').attr('src'));
    });
});