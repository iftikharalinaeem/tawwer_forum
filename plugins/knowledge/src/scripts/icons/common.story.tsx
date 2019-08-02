/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryUnorderedList } from "@library/storybook/StoryUnorderedList";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryLink } from "@library/storybook/StoryLink";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryTiles } from "@library/storybook/StoryTiles";
import * as icons from "@knowledge/icons/common";
import { StoryTile } from "@library/storybook/StoryTile";

const reactionsStory = storiesOf("KnowledgeBase", module);

reactionsStory.add("Icons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Knowledge Base Icons</StoryHeading>
            <StoryTiles>
                <StoryTile>{icons.knowldedgeBaseItem()}</StoryTile>
                <StoryTile>{icons.knowledgeBaseNoIcon()}</StoryTile>
            </StoryTiles>
        </StoryContent>
    );
});
