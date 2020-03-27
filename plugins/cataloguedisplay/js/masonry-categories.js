;(function($, window, document, undefined) {
    $(function() {
        $(".DataList.Discussions").imagesLoaded(function($images, $proper, $broken) {
            $(".placeholder-image", this).animate({ opacity: 1 });
            this.masonry({
                itemSelector: ".Item.ItemDiscussion",
                animate: true,
                glutter: 10
            });
        });
    });
})(window.jQuery, window, document);
