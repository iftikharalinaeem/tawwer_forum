$(document).ready(function(){

    // If the user chooses a category to post a discussion
    // show or hide the image upload depending on if it is
    // in the array of ImageRequiredCategory discusssions.
    $('body').on('change', '#Form_CategoryID', function() {
        if( gdn.meta['ImageRequiredCategory']) {
            var newURL = gdn.getMeta('WebRoot') +    "/post/discussion/" + $(this).val();
            window.location = newURL;
        }
    });

    // When submitting the form for discussion creation, populate the form field "imageName"
    // for the purposes of validating because the image upload is in another in the FileUpload plugin.
    $('body').on("click", ".DiscussionButton", function() {
        if($("#Form_imageRequiredCategoryChosen").val() === 'true') {
            $('#Form_imageName').val($('.AttachFileContainer img.ImageThumbnail').attr('src'));
        } else {
            console.log("No need for an image.");
        }
    });
});