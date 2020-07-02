/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import {
    ITwoColumnLayoutMediaQueryStyles,
    twoColumnLayoutDevices,
    twoColumnLayoutVariables,
} from "./layout.twoColumns";
import {
    IThreeColumnLayoutMediaQueryStyles,
    threeColumnLayoutDevices,
    threeColumnLayoutVariables,
} from "./layout.threeColumns";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    TWO_COLUMNS = "two column", // Single column, but full width of page
    // ONE_COLUMN = "one column", // Single column, but full width of page
    // NARROW = "one column narrow", // Single column, but narrower than default
    // LEGACY = "legacy", // Legacy layout used on the Forum pages. The media queries are also used for older components. Newer ones should use the context
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.TWO_COLUMNS]?: ITwoColumnLayoutMediaQueryStyles;
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices = twoColumnLayoutDevices | threeColumnLayoutDevices;

/* Allows to declare styles for any layout without causing errors
Declare media query styles like this:

    mediaQueries({
        [LayoutTypes.TWO_COLUMNS]: {
            oneColumnDown: {
                ...srOnly(),
            },
        },
        [LayoutTypes.THREE_COLUMNS]: {
            twoColumns: {
                // Styles go here
            }
        }
    }),


Note that "twoColumns" does not exist in the two column layout media queries, but it does not crash!
*/

export const filterQueriesByType = (mediaQueriesByType, type) => {
    return (mediaQueriesByLayout: IAllLayoutMediaQueries) => {
        Object.keys(mediaQueriesByLayout).forEach(layoutName => {
            if (layoutName === type) {
                // Check if we're in the correct layout before applying
                const mediaQueriesForLayout = mediaQueriesByLayout[layoutName];
                const stylesForLayout = mediaQueriesByLayout[layoutName];
                if (mediaQueriesForLayout) {
                    Object.keys(mediaQueriesForLayout).forEach(queryName => {
                        mediaQueriesForLayout[queryName] = stylesForLayout;
                        const result = mediaQueriesForLayout[queryName];
                        return result;
                    });
                }
            }
        });
        return {};
    };
};

export const allLayouts = (props: { offset?: number } = {}) => {
    const mediaQueriesByType = {};

    const variablesByType = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayoutVariables(),
        [LayoutTypes.TWO_COLUMNS]: twoColumnLayoutVariables(),
    };

    const classesByType = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayoutVariables(),
        [LayoutTypes.TWO_COLUMNS]: twoColumnLayoutVariables(),
    };

    Object.keys(LayoutTypes).forEach(layoutName => {
        const enumKey = LayoutTypes[layoutName];
        const layoutData = variablesByType[enumKey];
        mediaQueriesByType[enumKey] = layoutData.mediaQueries();
    });

    return {
        mediaQueriesByType,
        classesByType,
        variablesByType,
    };
};

export const layoutData = (type: LayoutTypes = LayoutTypes.THREE_COLUMNS) => {
    const layouts = allLayouts();
    return {
        mediaQueries: layouts.mediaQueriesByType[type],
        classes: layouts.classesByType[type],
        variables: layouts.variablesByType[type],
    };
};
