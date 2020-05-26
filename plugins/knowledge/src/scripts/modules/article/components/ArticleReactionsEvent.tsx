import React from "react";

export const ArticleReactionsEvent = React.memo(
    function ArticleReactionsEvent() {
        return <div id={"article-reactions-event"} />;
    },

    () => true,
);
