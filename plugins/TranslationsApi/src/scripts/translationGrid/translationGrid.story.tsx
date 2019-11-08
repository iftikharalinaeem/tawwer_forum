/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { loadLocales, LocaleProvider } from "@vanilla/i18n";
import { TranslationGrid } from "./TranslationGrid";
import { localeData, makeTestTranslationProperty } from "./translationGrid.storyData";

const story = storiesOf("Components", module);
const dateUpdated = "2019-10-09T20:05:51+00:00";
loadLocales(localeData);
const ipsum =
    "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vestibulum sagittis porta nibh, a egestas tortor lobortis ac. \nPraesent interdum congue nunc, congue volutpat dui maximus commodo. Nunc sagittis libero id ex commodo aliquet. Vivamus venenatis pellentesque lorem, sed molestie justo vehicula eu.";

story.add("Translation Grid", () => {
    return (
        <>
            <LocaleProvider>
                <StoryContent>
                    <StoryHeading depth={1}>Translation Grid</StoryHeading>
                    <StoryParagraph>Border is just to show it works in a scroll container, like a Modal</StoryParagraph>
                </StoryContent>
                <div
                    style={{
                        height: "600px",
                        overflow: "auto",
                        border: "solid #1EA7FD 4px",
                        width: "1028px",
                        maxWidth: "100%",
                        margin: "auto",
                        position: "relative", // Scrolling container must have "position"
                    }}
                >
                    <TranslationGrid
                        sourceLocale="en"
                        properties={[
                            makeTestTranslationProperty("test.1", "Hello world!", false),
                            makeTestTranslationProperty("test.2", ipsum, true),
                            makeTestTranslationProperty("test.3", "Hello world 2!", false),
                            makeTestTranslationProperty("test.4", ipsum, true),
                            makeTestTranslationProperty("test.5", ipsum, true),
                            makeTestTranslationProperty("test.6", "Hello world 6!", false),
                        ]}
                        existingTranslations={{
                            "test.1": "Bonjour World!",
                            "test.4":
                                "Cum saepe multa, tum memini domi in hemicyclio sedentem, ut solebat, cum et ego essem una et pauci admodum familiares, in eum sermonem illum incidere qui tum forte multis erat in ore. Meministi enim profecto, Attice, et eo magis, quod P. Sulpicio utebare multum, cum is tribunus plebis capitali odio a Q. Pompeio, qui tum erat consul, dissideret, quocum coniunctissime et amantissime vixerat, quanta esset hominum vel admiratio vel querella.",
                        }}
                        inScrollingContainer={true}
                    />
                </div>
            </LocaleProvider>
        </>
    );
});
