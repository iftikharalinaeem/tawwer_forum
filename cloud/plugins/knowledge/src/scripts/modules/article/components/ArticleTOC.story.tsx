/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { t } from "@library/utility/appUtils";
import { storiesOf } from "@storybook/react";
import React from "react";

const story = storiesOf("Knowledge Base", module);

// interface IArticleReactionOption

story.add("Table of Contents", () => {
    return (
        <>
            <StoryHeading depth={1}>{t("Table of Contents")}</StoryHeading>
            <ArticleTOC
                items={[
                    {
                        ref: "things-to-do",
                        level: 2,
                        text: "Things to do\n",
                    },
                    {
                        ref: "things-to-avoid",
                        level: 2,
                        text: "Things to avoid\n",
                    },
                    {
                        ref: "things-you-absolutely-cannot-do",
                        level: 2,
                        text: "Things you absolutely cannot do\n",
                    },
                ]}
            />
        </>
    );
});
