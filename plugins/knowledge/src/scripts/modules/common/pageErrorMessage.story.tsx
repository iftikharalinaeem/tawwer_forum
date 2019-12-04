/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";

const formsStory = storiesOf("Knowledge", module).addDecorator(dashboardCssDecorator);

formsStory.add("Error Pages", () =>
    (() => {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Error Pages</StoryHeading>
            </StoryContent>
        );
    })(),
);
