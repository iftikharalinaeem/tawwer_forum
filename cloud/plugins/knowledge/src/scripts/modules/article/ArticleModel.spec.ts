/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ArticleModel, { IArticleState } from "@knowledge/modules/article/ArticleModel";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { dummyArticle } from "@knowledge/__tests__/kbMockTestData";
import { IArticle } from "@knowledge/@types/api/article";
import { LoadStatus } from "@library/@types/api/core";

describe("ArticleModel.reducer", () => {
    const model = new ArticleModel();

    describe("getArticleACs.done", () => {
        it("Saves articles by ID", () => {
            const article1 = dummyArticle();
            const result = model.reducer(
                undefined,
                ArticleActions.getArticleACs.done({
                    params: { articleID: article1.articleID, locale: "en" },
                    result: article1,
                }),
            );

            expect(result.articlesByID[1]).toBe(article1);
        });

        it("Sets the translation fallback on our off", () => {
            const article1 = dummyArticle({ locale: "en" });

            // We requested in french but got it in english.
            const result = model.reducer(
                undefined,
                ArticleActions.getArticleACs.done({
                    params: { articleID: article1.articleID, locale: "fr" },
                    result: article1,
                }),
            );

            expect(result.articleIDsWithTranslationFallback).toEqual([article1.articleID]);
        });

        it("Updates the article translation cache when a new article translation", () => {
            const article1 = dummyArticle({ locale: "en", translationStatus: "untranslated" });

            const initial: IArticleState = {
                ...ArticleModel.INITIAL_STATE,
                articlesByID: {
                    1: article1,
                },
                articleIDsWithTranslationFallback: [1],
                articleLocalesByID: {
                    1: {
                        status: LoadStatus.SUCCESS,
                        data: [
                            {
                                articleRevisionID: 1,
                                locale: "fr",
                                translationStatus: "untranslated",
                                url: "fake url",
                                name: "",
                                lang: "",
                            },
                        ],
                    },
                },
            };

            const updated: IArticle = {
                ...article1,
                locale: "fr",
                translationStatus: "up-to-date",
            };

            // The article was untranslated, but not it's translated.
            const result = model.reducer(
                initial,
                ArticleActions.getArticleACs.done({
                    params: { articleID: article1.articleID, locale: "fr" },
                    result: updated,
                }),
            );

            // Tranlsation fallback is cleared.
            expect(result.articleIDsWithTranslationFallback).toEqual([]);

            // Article translation information was updated.
            const articleLocale = result.articleLocalesByID[1].data?.[0];
            expect(articleLocale?.translationStatus).toEqual("up-to-date");
            expect(articleLocale?.url).toEqual(article1.url);
        });
    });
});
