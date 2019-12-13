/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { ArticleReactions } from "@knowledge/modules/article/components/ArticleReactions";
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api/article";
import { t } from "@library/utility/appUtils";
import { StoryHeading } from "@library/storybook/StoryHeading";

const story = storiesOf("Knowledge Base", module);

story.add("Was this Helpful?", () => {
    const noop = () => {
        return;
    };

    const yesVotes = 10;
    const noVotes = 2;

    const signedIn = true;

    return (
        <>
            <StoryHeading depth={1}>{t("COMPONENT: ArticleReactions")}</StoryHeading>

            <StoryHeading>{t("Interactive")}</StoryHeading>
            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: yesVotes,
                        no: noVotes,
                        total: (noVotes >= 0 ? noVotes : 0) + (yesVotes >= 0 ? yesVotes : 0),
                        userReaction: "yes",
                    },
                ]}
                articleID={1}
                isSignedIn={signedIn}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />

            <StoryHeading>{t("Signed Out - no score")}</StoryHeading>

            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 0,
                        no: 0,
                        total: 0,
                        userReaction: null,
                    },
                ]}
                articleID={1}
                isSignedIn={false}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />
            <StoryHeading>{t("Signed Out - with score")}</StoryHeading>

            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 10,
                        no: 0,
                        total: 10,
                        userReaction: null,
                    },
                ]}
                articleID={1}
                isSignedIn={false}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />

            <StoryHeading>{t("Signed In - no votes")}</StoryHeading>
            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 0,
                        no: 0,
                        total: 0,
                        userReaction: null,
                    },
                ]}
                articleID={1}
                isSignedIn={true}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />

            <StoryHeading>{t("Signed in - Votes")}</StoryHeading>

            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 10,
                        no: 4,
                        total: 14,
                        userReaction: null,
                    },
                ]}
                articleID={1}
                isSignedIn={true}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />
            <StoryHeading>{t("Signed in - Loading Vote 'yes'")}</StoryHeading>
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
                isYesSubmitting={true}
                isNoSubmitting={false}
            />

            <StoryHeading>{t("Signed in - Voted 'yes'")}</StoryHeading>
            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 10,
                        no: 4,
                        total: 20,
                        userReaction: "yes",
                    },
                ]}
                articleID={1}
                isSignedIn={true}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />

            <StoryHeading>{t("Signed in - Loading Vote 'no'")}</StoryHeading>
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
        </>
    );
});
