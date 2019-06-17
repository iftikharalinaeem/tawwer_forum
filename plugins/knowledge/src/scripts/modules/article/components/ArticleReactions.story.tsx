/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { ArticleReactions } from "@knowledge/modules/article/components/ArticleReactions";
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api/article";
import { boolean, withKnobs, text, OptionsKnobOptions } from "@storybook/addon-knobs";
import { t } from "@library/utility/appUtils";
import { array } from "@storybook/addon-knobs";
import { IDeviceProps } from "@library/layout/DeviceContext";
import { optionsKnob as options } from "@storybook/addon-knobs";
import { StoryHeading } from "@library/storybook/StoryHeading";

const reactionsStory = storiesOf("KnowledgeBase/Articles", module);

// Add Knobs

interface IArticleReactionProps extends IArticleReaction {
    signedIn: boolean;
    isYesSubmitting: boolean;
    isNoSubmitting: boolean;
}

// interface IArticleReactionOption

reactionsStory.add("Was this Helpful?", () => {
    const noop = () => {
        return;
    };

    const valuesObj = {
        Kiwi: "kiwi",
        Guava: "guava",
        Watermelon: "watermelon",
    };
    const defaultValue = "kiwi";
    const optionsObj: OptionsKnobOptions = {
        display: "radio",
    };

    const value = options("Fruits", valuesObj, defaultValue, optionsObj);
    return (
        <>
            <StoryHeading depth={1}>{t("COMPONENT: ArticleReactions")}</StoryHeading>

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

            <StoryHeading>{t("Signed In")}</StoryHeading>

            <h2 style={{ textAlign: "center" }}>{t("Signed In")}</h2>
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

            <StoryHeading>{t("No Votes")}</StoryHeading>

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
            <StoryHeading>{t("Some Votes")}</StoryHeading>
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
            <StoryHeading>{t("Interactive")}</StoryHeading>
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

            <pre>{value}</pre>
        </>
    );
});
