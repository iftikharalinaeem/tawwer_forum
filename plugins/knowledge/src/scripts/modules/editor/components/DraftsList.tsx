/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import Heading from "@library/layout/Heading";
import classNames from "classnames";
import { mobileDropDownClasses } from "@library/headers/pieces/mobileDropDownStyles";

interface IProps {
    children: React.ReactNode[];
    hideTitle?: boolean;
    classes?: string;
}

/**
 * Implements the article drafts list component
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        const { children } = this.props;
        const classes = mobileDropDownClasses();
        return (
            children.length > 0 && (
                <div className="draftsList related">
                    {!this.props.hideTitle && (
                        <Heading
                            className={classNames("panelList-title", "itemList-title", this.props.classes)}
                            title={t("Drafts")}
                            depth={2}
                        />
                    )}
                    <ul className={classNames("itemList-items", "panelList-items", classes.listContainer)}>
                        {children}
                    </ul>
                </div>
            )
        );
    }
}
