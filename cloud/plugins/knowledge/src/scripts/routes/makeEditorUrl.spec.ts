/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { getMockKbStore } from "@knowledge/__tests__/kbMockStore";
import { makeEditorUrl, IEditorURLData } from "@knowledge/routes/makeEditorUrl";
import { DeepPartial } from "redux";
import { LoadStatus } from "@library/@types/api/core";
import { dummyKnowledgeBase, dummySiteSection } from "@knowledge/__tests__/kbMockTestData";

const stateWithCorrectSiteSection = {
    knowledge: {
        knowledgeBases: {
            knowledgeBasesByID: {
                status: LoadStatus.SUCCESS,
                data: {
                    1: dummyKnowledgeBase({
                        knowledgeBaseID: 1,
                        sourceLocale: "en",
                    }),
                },
            },
        },
    },
};

const stateWithDifferentLocaleSiteSection = {
    knowledge: {
        knowledgeBases: {
            knowledgeBasesByID: {
                status: LoadStatus.SUCCESS,
                data: {
                    1: dummyKnowledgeBase({
                        knowledgeBaseID: 1,
                        sourceLocale: "fr",
                        siteSections: [
                            dummySiteSection({
                                contentLocale: "fr",
                                sectionID: "fr",
                                basePath: "/fr",
                            }),
                        ],
                    }),
                },
            },
        },
    },
};

describe("makeEditorUrl", () => {
    const cases: Array<[string, IEditorURLData, DeepPartial<IKnowledgeAppStoreState>]> = [
        ["/kb/articles/add", {}, {}],
        ["/kb/articles/add?discussionID=50", { discussionID: 50 }, {}],
        ["/kb/articles/add?draftID=100", { draftID: 100 }, {}],
        ["/kb/articles/add?knowledgeBaseID=1", { knowledgeBaseID: 1 }, stateWithCorrectSiteSection],
        [
            "/kb/articles/add?knowledgeCategoryID=10&knowledgeBaseID=1",
            { knowledgeCategoryID: 10, knowledgeBaseID: 1 },
            stateWithCorrectSiteSection,
        ],
        ["/kb/articles/5/editor", { articleID: 5 }, {}],
        ["/kb/articles/5/editor?articleRevisionID=100", { articleID: 5, articleRevisionID: 100 }, {}],
        ["/kb/articles/5/editor?draftID=100", { articleID: 5, draftID: 100 }, {}],

        ///
        /// Edge cases
        ///

        // We strip off the categoryID when we aren't provided with the KB ID to fetch it from.
        ["/kb/articles/add", { knowledgeCategoryID: 10 }, {}],

        // knowledgeID has a different source locale, so we redirect to that locale.
        [
            "http://localhost/fr/kb/articles/add?knowledgeBaseID=1&articleRedirection=true",
            { knowledgeBaseID: 1 },
            stateWithDifferentLocaleSiteSection,
        ],
    ];

    cases.forEach(([expected, data, storeState]) => {
        it(`Can make: ${expected}`, () => {
            const store = getMockKbStore(storeState);
            expect(makeEditorUrl(data, store)).toEqual(expected);
        });
    });
});
