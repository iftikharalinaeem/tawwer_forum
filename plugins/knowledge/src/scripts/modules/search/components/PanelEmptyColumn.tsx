/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";

/**
 * Implements "empty column" to render a column in PanelLayout, with no content
 */
export default class PanelEmptyColumn extends React.PureComponent {
    public render() {
        return <React.Fragment />;
    }
}
