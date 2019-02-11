/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IKbNavigationItem, KbRecordType } from "@knowledge/navigation/state/NavigationModel";
import { t } from "@library/application";
import Button, { ButtonBaseClass } from "@library/components/forms/Button";
import { article } from "@library/components/icons";
import { categoryIcon, checkCompact, rightChevron } from "@library/components/icons/common";
import classNames from "classnames";
import React from "react";
import { knowldedgeBaseItem } from "@knowledge/icons/common";

interface IProps {
    onClick: (position: string) => void;
}

/**
 * Component representing a single navigation item in a list of navigation items.
 */
export default class LocationPickerInsertArticle extends React.Component<IProps> {
    public render() {
        const { onClick } = this.props;
        return (
            <li className={classNames("folderContents-item")}>
                <Button onClick={onClick} baseClass={ButtonBaseClass.CUSTOM} className="">
                    <span className="sr-only">{t("Sub folder")}</span>
                    {t("INSERT ARTICLE")}
                </Button>
            </li>
        );
    }
}
