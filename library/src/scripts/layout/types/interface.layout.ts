/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { NestedCSSProperties } from "typestyle/lib/types";
import {
    ThreeColumnLayoutDevices,
    IThreeColumnLayoutMediaQueryStyles,
} from "@library/layout/types/interface.layoutThreeColumn";
import {
    ITwoColumnLayoutMediaQueryStyles,
    TwoColumnLayoutDevices,
} from "@library/layout/types/interface.layoutTwoColumn";

export enum LayoutTypes {
    THREE_COLUMNS = "three columns", // Dynamic layout with up to 3 columns that adjusts to its contents. This is the default for KB
    TWO_COLUMNS = "two column", // Single column, but full width of page
    ONE_COLUMN = "one column", // Single column, but full width of page
    NARROW = "one column narrow", // Single column, but narrower than default
    LEGACY = "legacy", // Legacy layout used on the Forum pages. The media queries are also used for older components. Newer ones should use the context
}

export interface IAllLayoutMediaQueries {
    [LayoutTypes.TWO_COLUMNS]?: ITwoColumnLayoutMediaQueryStyles;
    [LayoutTypes.THREE_COLUMNS]?: IThreeColumnLayoutMediaQueryStyles;
}

export type ILayoutMediaQueryFunction = (styles: IAllLayoutMediaQueries) => NestedCSSProperties;

export type IAllLayoutDevices = TwoColumnLayoutDevices | ThreeColumnLayoutDevices;
