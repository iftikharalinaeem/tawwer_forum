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

// interface IArticleReactionOption

reactionsStory.add("Organize Categories", () => {
    return <></>;
});
