/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";

interface IProps {
    children: React.ReactNode[];
    hideTitle?: boolean;
}

/**
 * Implements the article drafts list component
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        const { children } = this.props;

        return (
            children.length > 0 && (
                <div className="draftsList related">
                    {!this.props.hideTitle && (
                        <Heading className="panelList-title itemList-title" title={t("Drafts")} depth={2} />
                    )}
                    <ul className="itemList-items panelList-items">{children}</ul>
                </div>
            )
        );
    }
}
