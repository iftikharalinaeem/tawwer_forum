/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";

interface IProps {
    categoryName: string;
    children: React.ReactNode;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class NavigationItemList extends React.Component<IProps> {
    public render() {
        return (
            <fieldset className={classNames("folderContents")}>
                <legend className="sr-only">{t("Contents of folder: " + this.props.categoryName)}</legend>
                <ul className="folderContents-items">{this.props.children}</ul>
            </fieldset>
        );
    }
}
