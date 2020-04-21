/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { CollapseAll, ExpandAll } from "@knowledge/navigation/navigationManagerIcons";
import React from "react";
import classNames from "classnames";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { navigationManagerToolbarClasses } from "@knowledge/navigation/subcomponents/navigationManagerToolBarStyles";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { NewFolderIcon } from "@library/icons/common";
import { iconClasses } from "@library/icons/iconStyles";
import { ToolTip } from "@library/toolTip/ToolTip";
import Translate from "@library/content/Translate";
import { LocaleDisplayer } from "@vanilla/i18n";

interface IProps {
    expandAll: () => void;
    collapseAll: () => void;
    newCategory: () => void;
    newCategoryButtonRef: React.RefObject<HTMLButtonElement>;
    newCategoryButtonDisable?: boolean;
    sourceLocale?: string;
}

export default function NavigationManagerToolBar(props: IProps) {
    const classesNavigationManager = navigationManagerClasses();
    const classes = navigationManagerToolbarClasses();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    const classesIcon = iconClasses();

    const buttonContents = (
        <Button
            baseClass={ButtonTypes.CUSTOM}
            className={classNames(classes.newFolder, classesNavigationManager.button)}
            onClick={props.newCategory}
            ariaLabel={t("New Category")}
            buttonRef={props.newCategoryButtonRef}
            disabled={props.newCategoryButtonDisable}
        >
            <NewFolderIcon className={classes.icon} />
            {!isMobile && <span className={classes.buttonLabel}>{t("New Category")}</span>}
        </Button>
    );

    return (
        <div className={classes.root}>
            <div className={classes.bar}>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classesNavigationManager.button)}
                    onClick={props.expandAll}
                    ariaLabel={t("Expand All")}
                >
                    <ExpandAll className={classes.icon} />
                    {!isMobile && <span className={classes.buttonLabel}>{t("Expand All")}</span>}
                </Button>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classesNavigationManager.button)}
                    onClick={props.collapseAll}
                    ariaLabel={t("Collapse All")}
                >
                    <CollapseAll className={classes.icon} />
                    {!isMobile && <span className={classes.buttonLabel}>{t("Collapse All")}</span>}
                </Button>
                {props.newCategoryButtonDisable ? (
                    <ToolTip
                        label={
                            <Translate
                                source="You can only add categories in the source locale: <0/>."
                                c0={
                                    <LocaleDisplayer
                                        localeContent={props.sourceLocale || " "}
                                        displayLocale={props.sourceLocale || " "}
                                    />
                                }
                            />
                        }
                        ariaLabel={"You can only add categories in the source locale."}
                    >
                        <div className={classNames(classes.newFolder)}>{buttonContents}</div>
                    </ToolTip>
                ) : (
                    buttonContents
                )}
            </div>
            <hr role="separator" className={classes.separator} />
        </div>
    );
}
