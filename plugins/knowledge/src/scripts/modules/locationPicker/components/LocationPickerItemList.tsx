/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import * as React from "react";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { folderContentsClasses } from "@knowledge/modules/locationPicker/folderContentsStyles";

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
        const classesFolderContents = folderContentsClasses();
        return (
            <fieldset id={this.props.id} className={classNames("folderContents", classesFolderContents.folderContents)}>
                <legend ref={this.props.legendRef} tabIndex={-1} className="sr-only">
                    {t("Contents of folder: ") + this.props.categoryName}
                </legend>
                <ul className={classNames(inheritHeightClass(), "folderContents-items", classesFolderContents.items)}>
                    {this.props.children}
                </ul>
            </fieldset>
        );
    }
}
