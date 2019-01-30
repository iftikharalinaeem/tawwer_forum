/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/application";
import classNames from "classnames";
import * as React from "react";

interface IProps {
    categoryName: string;
    children: React.ReactNode;
    id?: string;
    legendRef?: React.RefObject<HTMLLegendElement>;
}

/**
 * Represents a list of NavigationItems
 */
export default class LocationPickerItemList extends React.Component<IProps> {
    public render() {
        return (
            <fieldset id={this.props.id} className={classNames("folderContents")}>
                <legend ref={this.props.legendRef} tabIndex={-1} className="sr-only">
                    {t("Contents of folder: " + this.props.categoryName)}
                </legend>
                <ul className="folderContents-items">{this.props.children}</ul>
            </fieldset>
        );
    }
}
