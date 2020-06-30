/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import IndependentSearch from "@library/features/search/IndependentSearch";
import SearchContext from "@library/contexts/SearchContext";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import { MemoryRouter } from "react-router";
import ResultList from "@library/result/ResultList";
import { ResultMeta } from "@library/result/ResultMeta";
import { PublishStatus } from "@library/@types/api/core";
import { AttachmentType } from "@library/content/attachments/AttatchmentType";
import { globalVariables } from "@library/styles/globalStyleVars";
import { bannerClasses } from "@library/banner/bannerStyles";
import {
    TypeAllIcon,
    TypeArticlesIcon,
    TypeCategoriesAndGroupsIcon,
    TypeCategoriesIcon,
    TypeDiscussionsIcon,
    TypeIdeasIcon,
    TypeMemberIcon,
    TypePollsIcon,
    TypeQuestionIcon,
} from "@library/icons/searchIcons";
import { t } from "@vanilla/i18n/src";
import { sampleImages } from "./storybook/attachments/sampleAttachmentImages";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { StoryListItem } from "@library/storybook/StoryListItem";
import { StoryBookImageTypeSearchResult } from "./storybook/attachments/StorybookImageTypeSearchResult";
import { StoryContent } from "@library/storybook/StoryContent";

const story = storiesOf("Search", module);

story.add("Search Box", () => {
    const dummyUserFragment = {
        userID: 1,
        name: "Joe",
        photoUrl: "",
        dateLastActive: "2016-07-25 17:51:15",
    };

    const classesSearch = bannerClasses();
    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Box</StoryHeading>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <MemoryRouter>
                    <div
                        style={{
                            backgroundColor: globalVariables()
                                .mixBgAndFg(0.5)
                                .toHexString(),
                            padding: `30px 10px`,
                        }}
                    >
                        <div className={classesSearch.searchContainer}>
                            <IndependentSearch
                                buttonClass={classesSearch.searchButton}
                                buttonBaseClass={ButtonTypes.CUSTOM}
                                isLarge={true}
                                placeholder={t("Search")}
                                inputClass={classesSearch.input}
                                iconClass={classesSearch.icon}
                                buttonLoaderClassName={classesSearch.buttonLoader}
                                contentClass={classesSearch.content}
                                valueContainerClasses={classesSearch.valueContainer(false)}
                            />
                        </div>
                    </div>
                </MemoryRouter>
            </SearchContext.Provider>
        </StoryContent>
    );
});

story.add("Search Results", () => {
    const dummyUserFragment = {
        userID: 1,
        name: "Joe",
        photoUrl: "",
        dateLastActive: "2016-07-25 17:51:15",
    };
    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Results</StoryHeading>
            <ResultList
                results={[
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        attachments: [{ name: "My File", type: AttachmentType.WORD }],
                        icon: <TypeDiscussionsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        image: "https://upload.wikimedia.org/wikipedia/en/7/70/Bob_at_Easel.jpg",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeQuestionIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",

                        icon: <TypePollsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeIdeasIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeCategoriesIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeMemberIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeCategoriesAndGroupsIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeArticlesIcon />,
                    },
                    {
                        name: "Example search result",
                        headingLevel: 3,
                        url: "#",
                        excerpt:
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake.",
                        meta: (
                            <ResultMeta
                                dateUpdated={"2016-07-25 17:51:15"}
                                updateUser={dummyUserFragment}
                                crumbs={[{ name: "This" }, { name: "is" }, { name: "the" }, { name: "breadcrumb" }]}
                                status={PublishStatus.PUBLISHED}
                                type={"Article"}
                            />
                        ),
                        icon: <TypeAllIcon />,
                    },
                ]}
            />
            <StoryHeading>Category result (used on categories page)</StoryHeading>
            <ResultList
                results={[
                    {
                        name: "Example category result",
                        headingLevel: 3,
                        url: "#",
                        meta: <ResultMeta dateUpdated={"2016-07-25 17:51:15"} updateUser={dummyUserFragment} />,
                    },
                ]}
            />
        </StoryContent>
    );
});

story.add("Search Results - images", () => {
    return (
        <StoryContent>
            <StoryHeading depth={1}>Search Results - images</StoryHeading>
            <StoryParagraph>
                <StoryHeading depth={2}>We have 4 possible types of images to handle:</StoryHeading>
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
});
