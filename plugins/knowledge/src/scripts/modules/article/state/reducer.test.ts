/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import { expect } from "chai";
import { actions, model, constants, reducer, initialState } from "@knowledge/modules/article/state";
import { LoadStatus } from "@library/@types/api";

describe("articleReducer", () => {
    it("should return the initial state", () => {
        expect(reducer(undefined, {} as any)).deep.equals(initialState);
    });

    it(`handles ${constants.RESET_PAGE_STATE}`, () => {
        const initial = { some: "garbage", data: "garbage data" } as any;
        const action = actions.clearPageState();

        expect(reducer(initial, action)).deep.equals(initialState);
    });

    describe(`handling ${constants.GET_ARTICLE_REQUEST}`, () => {
        it("sets the status to loading", () => {
            const action = actions.getArticleActions.request({ id: 17 });
            const expected: model.IState = {
                status: LoadStatus.LOADING,
            };
            expect(reducer(undefined, action)).deep.equals(expected);
        });

        it("clears the current error and data contents", () => {
            const action = actions.getArticleActions.request({ id: 17 });
            const error = { message: "Test error" } as any;
            const data = { data: "Some data" } as any;
            const initial: model.IState = {
                status: LoadStatus.ERROR,
                error,
                data,
            };
            const expected: model.IState = {
                status: LoadStatus.LOADING,
            };
            expect(reducer(initial, action)).deep.equals(expected);
        });
    });

    it(`handles ${constants.GET_ARTICLE_RESPONSE}`, () => {
        const article = { articleTitle: "Some Title" } as any;
        const action = actions.getArticleActions.success({ data: article, status: 200, headers: {} });
        const error = { message: "Test error" } as any;
        const initial: model.IState = {
            status: LoadStatus.ERROR,
            error,
        };
        const expected: model.IState = {
            status: LoadStatus.SUCCESS,
            data: {
                article,
            },
        };
        expect(reducer(initial, action)).deep.equals(expected);
    });

    it(`handles ${constants.GET_ARTICLE_ERROR}`, () => {
        const error = { message: "Test error" } as any;
        const action = actions.getArticleActions.error(error);
        const initial: model.IState = {
            status: LoadStatus.SUCCESS,
            data: {
                article: {} as any,
            },
        };
        const expected: model.IState = {
            status: LoadStatus.ERROR,
            error,
        };
        expect(reducer(initial, action)).deep.equals(expected);
    });
});
