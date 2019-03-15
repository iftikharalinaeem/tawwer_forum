/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { ArticleReactions } from "@knowledge/modules/article/components/ArticleReactions";
import { ArticleReactionType } from "@knowledge/@types/api/article";

storiesOf("KnowledgeBase/Articles", module).add("Reactions", () => {
    const noop = () => {
        return;
    };

    return (
        <ArticleReactions
            reactions={[
                {
                    reactionType: ArticleReactionType.HELPFUL,
                    yes: 10,
                    no: 4,
                    total: 20,
                    userReaction: null,
                },
            ]}
            articleID={1}
            isSignedIn={true}
            onYesClick={noop}
            onNoClick={noop}
            isYesSubmitting={false}
            isNoSubmitting={true}
        />
    );
});
