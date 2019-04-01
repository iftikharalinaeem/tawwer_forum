/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";

interface IProps {
    children: React.ReactNode;
    hideTitle?: boolean;
}

/**
 * Implements the article drafts list component
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        return (
            <div className="draftsList related">
                {!this.props.hideTitle && (
                    <Heading className="panelList-title draftsList-title" title={t("Drafts")} depth={2} />
                )}
                <ul className="draftsList-items panelList-items">{this.props.children}</ul>
            </div>
        );
    }
}
