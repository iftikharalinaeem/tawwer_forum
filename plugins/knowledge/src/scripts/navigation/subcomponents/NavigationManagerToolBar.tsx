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
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import classNames from "classnames";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useDevice, Devices } from "@library/layout/DeviceContext";

interface IProps {
    expandAll: () => void;
    collapseAll: () => void;
    newCategory: () => void;
    newCategoryButtonRef: React.RefObject<HTMLButtonElement>;
}

export default function NavigationManagerToolBar(props: IProps) {
    const classesNavigationManager = navigationManagerClasses();
    const device = useDevice();
    const isMobile = device === Devices.MOBILE;
    return (
        <div className="navigationManagerToolbar">
            <div className="navigationManagerToolbar-bar">
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(
                        "navigationManagerToolbar-button",
                        "navigationManagerToolbar-expandAll",
                        classesNavigationManager.button,
                    )}
                    onClick={props.expandAll}
                    ariaLabel={t("Expand All")}
                >
                    {expandAll("navigationManagerToolbar-icon")}
                    {!isMobile && <span className="navigationManagerToolbar-buttonLabel">{t("Expand All")}</span>}
                </Button>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(
                        "navigationManagerToolbar-button",
                        "navigationManagerToolbar-collapseAll",
                        classesNavigationManager.button,
                    )}
                    onClick={props.collapseAll}
                    ariaLabel={t("Collapse All")}
                >
                    {collapseAll("navigationManagerToolbar-icon")}
                    {!isMobile && <span className="navigationManagerToolbar-buttonLabel">{t("Collapse All")}</span>}
                </Button>
                <Button
                    baseClass={ButtonTypes.CUSTOM}
                    className={classNames(
                        "navigationManagerToolbar-button",
                        "navigationManagerToolbar-newFolder",
                        classesNavigationManager.button,
                    )}
                    onClick={props.newCategory}
                    ariaLabel={t("New Category")}
                    buttonRef={props.newCategoryButtonRef}
                >
                    {newFolder("navigationManagerToolbar-icon navigationManagerToolbar-newFolder")}
                    {!isMobile && <span className="navigationManagerToolbar-buttonLabel">{t("New Category")}</span>}
                </Button>
            </div>
            <hr role="separator" className="navigationManagerToolbar-separator" />
        </div>
    );
}
