/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { MemoryRouter } from "react-router";
import DraftsList from "@knowledge/modules/editor/components/DraftsList";
import { DraftPreview } from "@knowledge/modules/drafts/components/DraftPreview";
import { StoryExampleDropDownDraft } from "@knowledge/modules/drafts/components/StoryExampleDropDownDraft";

const story = storiesOf("Drafts", module);

story.add("Results", () => {
    const dummyUserFragment = {
        userID: 1,
        name: "Joe",
        photoUrl: "",
        dateLastActive: "2016-07-25 17:51:15",
    };

    return (
        <StoryContent>
            <StoryHeading>Draft result</StoryHeading>
            <MemoryRouter>
                <DraftsList hideTitle={true}>
                    <DraftPreview
                        format={"text"}
                        dateUpdated={"2016-07-25 17:51:15"}
                        dateInserted={"2016-07-25 17:51:15"}
                        updateUserID={1}
                        insertUserID={1}
                        body={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        headingLevel={3}
                        draftID={1}
                        recordType={"article"}
                        excerpt={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        attributes={{
                            name: "Draft example",
                        }}
                        menuOverwrite={<StoryExampleDropDownDraft />}
                    />
                    <DraftPreview
                        format={"text"}
                        dateUpdated={"2016-07-25 17:51:15"}
                        dateInserted={"2016-07-25 17:51:15"}
                        updateUserID={1}
                        insertUserID={1}
                        body={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        headingLevel={3}
                        draftID={1}
                        recordType={"article"}
                        excerpt={""}
                        attributes={{
                            name: "",
                        }}
                        menuOverwrite={<StoryExampleDropDownDraft />}
                    />
                    <DraftPreview
                        format={"text"}
                        dateUpdated={"2016-07-25 17:51:15"}
                        dateInserted={"2016-07-25 17:51:15"}
                        updateUserID={1}
                        insertUserID={1}
                        body={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        headingLevel={3}
                        draftID={1}
                        recordType={"article"}
                        excerpt={""}
                        attributes={{
                            name: "Draft example",
                        }}
                        menuOverwrite={<StoryExampleDropDownDraft />}
                    />
                    <DraftPreview
                        format={"text"}
                        dateUpdated={"2016-07-25 17:51:15"}
                        dateInserted={"2016-07-25 17:51:15"}
                        updateUserID={1}
                        insertUserID={1}
                        body={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        headingLevel={3}
                        draftID={1}
                        recordType={"article"}
                        excerpt={
                            "Donut danish halvah macaroon chocolate topping. Sugar plum cookie chupa chups tootsie roll tiramisu cupcake carrot cake. Ice cream biscuit sesame snaps fruitcake."
                        }
                        attributes={{
                            name: "",
                        }}
                        menuOverwrite={<StoryExampleDropDownDraft />}
                    />
                </DraftsList>
            </MemoryRouter>
        </StoryContent>
    );
});
