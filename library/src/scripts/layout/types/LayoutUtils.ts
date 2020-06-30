/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { LayoutTypes } from "@library/layout/types/interface.layout";
import { threeColumnLayout } from "@library/layout/types/threeColumns";

export const layoutVarsByLayoutType = (props: { type?: LayoutTypes; layoutVariables? } = {}) => {
    const { type, layoutVariables = { layouts: {} } } = props;
    const { types = {} } = layoutVariables.layouts;
    if (type && types && types[type]) {
        return types[type];
    } else {
        return threeColumnLayout();
    }
};

export const allLayoutVariables = (props: { offset?: number } = {}) => {
    const mediaQueriesByType = {};
    const { offset } = props;
    const types = {
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout(),
        [LayoutTypes.THREE_COLUMNS]: threeColumnLayout({ vars: { offset } }),
    };

    Object.keys(LayoutTypes).forEach(layoutName => {
        const enumKey = LayoutTypes[layoutName];
        const layoutData = types[enumKey];
        mediaQueriesByType[enumKey] = layoutData.mediaQueries();
    });

    return {
        mediaQueries: mediaQueriesByType,
        types,
    };
};
