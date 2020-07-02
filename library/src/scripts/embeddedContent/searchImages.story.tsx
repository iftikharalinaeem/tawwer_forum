/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { layoutVariables } from "@library/layout/panelLayoutStyles";
import { searchResultsVariables } from "@library/features/search/searchResultsStyles";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryBookImageTypeSearchResult } from "@library/embeddedContent/storybook/attachments/StorybookImageTypeSearchResult";
import { sampleImages } from "@library/embeddedContent/storybook/attachments/sampleAttachmentImages";

export default {
    title: "Search",
    parameters: {
        chromatic: {
            viewports: [
                1450,
                searchResultsVariables().breakPoints.compact,
                layoutVariables().panelLayoutBreakPoints.xs,
            ],
        },
    },
};

export function SearchResultImages(props: { title?: string }) {
    const { title = "Search Result Images" } = props;
    return (
        <StoryContent>
            <StoryHeading depth={1}>{title}</StoryHeading>
            <StoryParagraph>
                <StoryHeading depth={2}>We have possible ratios to handle:</StoryHeading>
                <ul>
                    <StoryListItem>Square (1x1)</StoryListItem>
                    <StoryListItem>Flush (same as desired ratio: 16/9)</StoryListItem>
                    <StoryListItem>Tall (taller than desired aspect ratio)</StoryListItem>
                    <StoryListItem>Wide (Wider than desired aspect ratio)</StoryListItem>
                </ul>
            </StoryParagraph>
            <StoryParagraph>
                <StoryHeading depth={2}>On top of that, for each type, we have 3 sizes</StoryHeading>
                <ul>
                    <StoryListItem>Big (wider than desired width)</StoryListItem>
                    <StoryListItem>Small (smaller than desired width)</StoryListItem>
                    <StoryListItem>Flush (exactly desired width)</StoryListItem>
                </ul>
            </StoryParagraph>

            <StoryBookImageTypeSearchResult type={"square"} imageSet={sampleImages.square} />
            <StoryBookImageTypeSearchResult type={"flush"} imageSet={sampleImages.flush} />
            <StoryBookImageTypeSearchResult type={"tall"} imageSet={sampleImages.tall} />
            <StoryBookImageTypeSearchResult type={"wide"} imageSet={sampleImages.wide} />
        </StoryContent>
    );
}
