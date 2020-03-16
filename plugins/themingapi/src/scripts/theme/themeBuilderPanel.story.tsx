/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { percent } from "csx";
import { storiesOf } from "@storybook/react";
import ThemeBuilderForm from "@themingapi/theme/ThemeBuilderForm";
import { themeBuilderClasses } from "@library/forms/themeEditor/themeBuilderStyles";

const story = storiesOf("Theme", module);

story.add("Theme Builder Form", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Theme Editor</StoryHeading>
            <aside
                style={{
                    margin: "auto",
                }}
                className={themeBuilderClasses().root}
            >
                <ThemeBuilderForm />
            </aside>
        </StoryContent>
    );
});
