/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { t } from "@library/utility/appUtils";
import Button from "@library/forms/Button";
import { newFolder } from "@library/icons/common";
import { collapseAll, expandAll } from "@knowledge/navigation/navigationManagerIcons";
import React from "react";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useDevice, Devices } from "@library/layout/DeviceContext";
import { navigationManagerToolbarClasses } from "@knowledge/navigation/subcomponents/navigationManagerToolBarStyles";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";

interface IProps {
    expandAll: () => void;
    collapseAll: () => void;
    newCategory: () => void;
    newCategoryButtonRef: React.RefObject<HTMLButtonElement>;
}

export default function NavigationManagerToolBar(props: IProps) {
    const classesNavigationManager = navigationManagerClasses();
    const classes = navigationManagerToolbarClasses();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE || device === Devices.XS;
    return (
        <div className={classes.root}>
            <div className={classes.bar}>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classesNavigationManager.button)}
                    onClick={props.expandAll}
                    ariaLabel={t("Expand All")}
                >
                    {expandAll(classes.icon)}
                    {!isMobile && <span className={classes.buttonLabel}>{t("Expand All")}</span>}
                </Button>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classesNavigationManager.button)}
                    onClick={props.collapseAll}
                    ariaLabel={t("Collapse All")}
                >
                    {collapseAll(classes.icon)}
                    {!isMobile && <span className={classes.buttonLabel}>{t("Collapse All")}</span>}
                </Button>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(classes.newFolder, classesNavigationManager.button)}
                    onClick={props.newCategory}
                    ariaLabel={t("New Category")}
                    buttonRef={props.newCategoryButtonRef}
                >
                    {newFolder(classNames(classes.icon, classes.newFolder))}
                    {!isMobile && <span className={classes.buttonLabel}>{t("New Category")}</span>}
                </Button>
            </div>
            <hr role="separator" className={classes.separator} />
        </div>
    );
}
