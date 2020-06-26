/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import DropDown, { FlyoutType, DropDownOpenDirection } from "@vanilla/library/src/scripts/flyouts/DropDown";
import { ThemeBuilderTitle } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilderTitle";
import { t } from "@vanilla/i18n";
import DropDownItemButton from "@vanilla/library/src/scripts/flyouts/items/DropDownItemButton";
import { useActivePanelContext, ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import { themeBuilderClasses } from "@vanilla/library/src/scripts/forms/themeEditor/ThemeBuilder.styles";
import { BottomChevronIcon, DownTriangleIcon } from "@vanilla/library/src/scripts/icons/common";
import { themeEditorClasses } from "@themingapi/theme/ThemeEditor.styles";
import classNames from "classnames";
import DropDownSection from "@vanilla/library/src/scripts/flyouts/items/DropDownSection";

export function ActivePanelChooser(props: { titlePanel: ActiveVariablePanel }) {
    const classes = themeEditorClasses();
    return (
        <div className={classNames(themeBuilderClasses().title, classes.activeIndicator)}>
            <DropDown
                openDirection={DropDownOpenDirection.BELOW_LEFT}
                contentsClassName={classes.activeDropdown}
                buttonClassName={classes.activeButton}
                buttonBaseClass={ButtonTypes.RESET}
                flyoutType={FlyoutType.LIST}
                buttonContents={
                    <span className={classes.activeIndicatorContents}>
                        <span>{sectionToName(props.titlePanel)}</span>
                        <DownTriangleIcon className={classes.activeIndicatorIcon} />
                    </span>
                }
            >
                <DropDownSection title={t("Global & Homepage")} noSeparator>
                    <SectionItem panel={ActiveVariablePanel.GLOBAL} />
                    <SectionItem panel={ActiveVariablePanel.TITLE_BAR} />
                    <SectionItem panel={ActiveVariablePanel.BANNER} />
                </DropDownSection>
                <DropDownSection title={t("Content")}>
                    <SectionItem panel={ActiveVariablePanel.CONTENT_BANNER} />
                </DropDownSection>
            </DropDown>
        </div>
    );
}

function sectionToName(panel: ActiveVariablePanel) {
    switch (panel) {
        case ActiveVariablePanel.TITLE_BAR:
            return t("Title Bar");
        case ActiveVariablePanel.BANNER:
            return t("Banner");
        case ActiveVariablePanel.CONTENT_BANNER:
            return t("Content Banner");
        case ActiveVariablePanel.GLOBAL:
        default:
            return t("Global Styles");
    }
}

function SectionItem(props: { panel: ActiveVariablePanel }) {
    const { activePanel, setActivePanel } = useActivePanelContext();
    return (
        <DropDownItemButton
            isActive={activePanel === props.panel}
            onClick={() => {
                setActivePanel(props.panel);
            }}
        >
            {sectionToName(props.panel)}
        </DropDownItemButton>
    );
}
