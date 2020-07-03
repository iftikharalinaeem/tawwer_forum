import { NestedCSSProperties } from "typestyle/lib/types";
import { allLayouts } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";
import {
    ITwoColumnLayoutMediaQueries,
    ITwoColumnLayoutMediaQueryStyles,
    twoColumnLayoutDevices,
} from "@library/layout/types/interface.twoColumns";
import {
    IThreeColumnLayoutMediaQueries,
    IThreeColumnLayoutMediaQueryStyles,
} from "@library/layout/types/interface.threeColumns";
import { fallbackLayoutVariables } from "@library/layout/types/interface.panelLayout";

export interface IAllLayoutMediaQueries {
    [LayoutTypes.TWO_COLUMNS]?: ITwoColumnLayoutMediaQueryStyles;
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices = twoColumnLayoutDevices | fallbackLayoutVariables;

export type IAllMediaQueriesForLayouts = ITwoColumnLayoutMediaQueries | IThreeColumnLayoutMediaQueries;

export type IMediaQueryFunction = (mediaQueriesForAllLayouts: IAllLayoutMediaQueries) => NestedCSSProperties;

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
    Note that "twoColumns" does not exist in two column layout media queries, but it does not crash!
*/

export const filterQueriesByType = (mediaQueriesByType, type): IMediaQueryFunction => {
    // The following function is the one called in component styles.
    return (mediaQueriesForAllLayouts: IAllLayoutMediaQueries): NestedCSSProperties => {
        let output = { $nest: {} };
        Object.keys(mediaQueriesForAllLayouts).forEach(layoutName => {
            // Check if we're in the correct layout before applying
            if (layoutName === type) {
                // Fetch the available styles and the media queries for the current layout
                const stylesByMediaQuery = mediaQueriesForAllLayouts[layoutName];
                const mediaQueries = allLayouts().mediaQueriesByType[type];

                // Match the two together
                if (stylesByMediaQuery) {
                    Object.keys(stylesByMediaQuery).forEach(queryName => {
                        const query: ILayoutMediaQueryFunction = mediaQueries[queryName];
                        const styles: NestedCSSProperties = stylesByMediaQuery[queryName];
                        output = {
                            $nest: {
                                ...output.$nest,
                                ...query(styles as any).$nest,
                            },
                        };
                    });
                }
            }
        });
        return output;
    };
};
