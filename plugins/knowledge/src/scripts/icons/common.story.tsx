/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryTiles } from "@library/storybook/StoryTiles";
import * as icons from "@knowledge/icons/common";
import { StoryTile } from "@library/storybook/StoryTile";
import { StoryTileAndTextCompact } from "@library/storybook/StoryTileAndTextCompact";

const story = storiesOf("Knowledge Base", module);

story.add("Icons", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Knowledge Base Icons</StoryHeading>
            <StoryTiles>
                <StoryTileAndTextCompact text={`knowledgeBaseItem`}>
                    {icons.knowledgeBaseItem()}
                </StoryTileAndTextCompact>
                <StoryTileAndTextCompact text={`knowledgeBaseNoIcon`}>
                    {icons.knowledgeBaseNoIcon()}
                </StoryTileAndTextCompact>
            </StoryTiles>
        </StoryContent>
    );
});
