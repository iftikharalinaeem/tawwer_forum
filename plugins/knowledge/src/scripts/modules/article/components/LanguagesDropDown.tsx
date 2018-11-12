/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import SelectBox, { ISelectBoxItem } from "@library/components/SelectBox";
import classNames from "classnames";

interface IState {
    id: string;
}

export interface ILanguageProps extends ISelectBoxItem {
    lang: string;
    outdated?: boolean;
}

export interface ILanguageDropDownProps {
    id?: string;
    children: ILanguageProps[];
    titleID?: string; // set when it comes with a heading
    widthOfParent?: boolean;
    selected: any;
    className?: string;
    renderLeft?: boolean;
}

/**
 * Implements "other languages" DropDown for articles.
 */
export default class LanguagesDropDown extends React.Component<ILanguageDropDownProps, IState> {
    public render() {
        const showPicker = this.props.children && this.props.children.length > 1;
        if (showPicker) {
            let foundIndex = false;
            const processedChildren = this.props.children.map(language => {
                const selected = language.lang === this.props.selected;
                language.selected = selected;
                if (selected) {
                    foundIndex = selected;
                }
                return language;
            });
            if (!foundIndex) {
                processedChildren[0].selected = true;
            }
            return (
                <SelectBox
                    describedBy={this.props.titleID!}
                    label={!this.props.titleID ? t("Locale") : null}
                    widthOfParent={true}
                    className={classNames("otherLanguages-select", this.props.className)}
                    renderLeft={this.props.renderLeft}
                >
                    {processedChildren}
                </SelectBox>
            );
        } else {
            return null;
        }
    }
}

/*
name: string;
className?: string;
onClick?: () => {};
selected?: boolean;
outdated?: boolean;
*/
