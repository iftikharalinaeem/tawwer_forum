/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { ArticleReactions } from "@knowledge/modules/article/components/ArticleReactions";
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api/article";
import { boolean, number } from "@storybook/addon-knobs";
import { t } from "@library/utility/appUtils";
import { optionsKnob as options } from "@storybook/addon-knobs";
import { StoryHeading } from "@library/storybook/StoryHeading";

const story = storiesOf("Knowledge Base", module);

// Add Knobs

interface IArticleReactionProps extends IArticleReaction {
    signedIn: boolean;
    isYesSubmitting: boolean;
    isNoSubmitting: boolean;
}

// interface IArticleReactionOption

story.add("Was this Helpful?", () => {
    const noop = () => {
        return;
    };
    const positiveIntOptions = { min: 0 };

    const yesVotes = number("Yes Votes", 10, positiveIntOptions);
    const noVotes = number("No Votes", 2, positiveIntOptions);

    const signedIn = boolean("User Signed In", true);

    enum possibleVote {
        NO_VOTE = "noVote",
        YES = "yes",
        NO = "no",
    }

    const userVote =
        signedIn &&
        options(
            "User's Vote (must be signed in)",
            {
                Yes: possibleVote.YES,
                No: possibleVote.NO,
                "Not Voted": possibleVote.NO_VOTE,
            },
            possibleVote.NO_VOTE,
            {
                display: "inline-radio",
            },
        );

    const loadingVote =
        signedIn && userVote !== possibleVote.NO_VOTE && boolean("Loading vote (must have voted)", false);

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
                        userReaction:
                            signedIn && userVote !== possibleVote.NO_VOTE && !loadingVote
                                ? userVote === possibleVote.YES
                                    ? "yes"
                                    : "no"
                                : null,
                    },
                ]}
                articleID={1}
                isSignedIn={signedIn}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={signedIn && userVote === possibleVote.YES && loadingVote}
                isNoSubmitting={signedIn && userVote === possibleVote.NO && loadingVote}
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

            <StoryHeading>{t("Signed in - Voted 'no'")}</StoryHeading>
            <ArticleReactions
                reactions={[
                    {
                        reactionType: ArticleReactionType.HELPFUL,
                        yes: 10,
                        no: 4,
                        total: 20,
                        userReaction: "no",
                    },
                ]}
                articleID={1}
                isSignedIn={true}
                onYesClick={noop}
                onNoClick={noop}
                isYesSubmitting={false}
                isNoSubmitting={false}
            />
        </>
    );
});
