/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { storiesOf } from "@storybook/react";
import { ArticleReactions } from "@knowledge/modules/article/components/ArticleReactions";
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api/article";
import { boolean, withKnobs, text, OptionsKnobOptions, number, EmptyNumberOptions } from "@storybook/addon-knobs";
import { t } from "@library/utility/appUtils";
import { array } from "@storybook/addon-knobs";
import { IDeviceProps } from "@library/layout/DeviceContext";
import { optionsKnob as options } from "@storybook/addon-knobs";
import { StoryHeading } from "@library/storybook/StoryHeading";

const reactionsStory = storiesOf("KnowledgeBase/pages", module);

// Add Knobs

interface IArticleReactionProps extends IArticleReaction {
    signedIn: boolean;
    isYesSubmitting: boolean;
    isNoSubmitting: boolean;
}

// interface IArticleReactionOption

reactionsStory.add("Organize Categories", () => {
    const noop = () => {
        return;
    };
    const positiveIntOptions: EmptyNumberOptions = ({ min: 0 } as unknown) as EmptyNumberOptions;

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

    return <></>;
});
