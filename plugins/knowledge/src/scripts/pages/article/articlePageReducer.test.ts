/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { expect } from "chai";
import {
    GET_ARTICLE_SUCCESS,
    GET_ARTICLE_ERROR,
    GET_ARTICLE_REQUEST,
    RESET_PAGE_STATE,
    componentActions,
    _rawApiActions,
} from "@knowledge/pages/article/articlePageActions";
import { LoadStatus } from "@library/@types/api";
import reducer, { initialState } from "@knowledge/pages/article/articlePageReducer";
import { IArticlePageState } from "@knowledge/@types/state";

const { getArticleActions } = _rawApiActions;

describe("articleReducer", () => {
    it("should return the initial state", () => {
        expect(reducer(undefined, {} as any)).deep.equals(initialState);
    });

    it(`handles ${RESET_PAGE_STATE}`, () => {
        const initial = { some: "garbage", data: "garbage data" } as any;
        const action = componentActions.clearArticlePageState();

        expect(reducer(initial, action)).deep.equals(initialState);
    });

    describe(`handling ${GET_ARTICLE_REQUEST}`, () => {
        it("sets the status to loading", () => {
            const action = getArticleActions.request({ id: 17 });
            const expected: IArticlePageState = {
                status: LoadStatus.LOADING,
            };
            expect(reducer(undefined, action)).deep.equals(expected);
        });

        it("clears the current error and data contents", () => {
            const action = getArticleActions.request({ id: 17 });
            const error = { message: "Test error" } as any;
            const data = { data: "Some data" } as any;
            const initial: IArticlePageState = {
                status: LoadStatus.ERROR,
                error,
                data,
            };
            const expected: IArticlePageState = {
                status: LoadStatus.LOADING,
            };
            expect(reducer(initial, action)).deep.equals(expected);
        });
    });

    it(`handles ${GET_ARTICLE_SUCCESS}`, () => {
        const article = { articleTitle: "Some Title" } as any;
        const action = getArticleActions.success({ data: article, status: 200, headers: {} });
        const error = { message: "Test error" } as any;
        const initial: IArticlePageState = {
            status: LoadStatus.ERROR,
            error,
        };
        const expected: IArticlePageState = {
            status: LoadStatus.SUCCESS,
            data: {
                article,
            },
        };
        expect(reducer(initial, action)).deep.equals(expected);
    });

    it(`handles ${GET_ARTICLE_ERROR}`, () => {
        const error = { message: "Test error" } as any;
        const action = getArticleActions.error(error);
        const initial: IArticlePageState = {
            status: LoadStatus.SUCCESS,
            data: {
                article: {} as any,
            },
        };
        const expected: IArticlePageState = {
            status: LoadStatus.ERROR,
            error,
        };
        expect(reducer(initial, action)).deep.equals(expected);
    });
});
