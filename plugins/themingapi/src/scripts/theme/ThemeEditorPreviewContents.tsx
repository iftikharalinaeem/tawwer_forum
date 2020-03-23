/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { userContentClasses } from "@vanilla/library/src/scripts/content/userContentStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import React, { useEffect, useState, useContext } from "react";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import { useIFrameCommunication } from "@themingapi/theme/IframeCommunicationContext";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import { t } from "@vanilla/i18n";
import { setActivePanelAC, ActiveVariablePanel } from "@themingapi/theme/ThemeBuilderPanel";
import classNames from "classnames";
import { ColorHelper } from "csx";
import { colorOut } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { bannerVariables } from "@vanilla/library/src/scripts/banner/bannerStyles";
import { titleBarVariables } from "@vanilla/library/src/scripts/headers/titleBarStyles";

const activePanelContext = React.createContext({
    activePanel: ActiveVariablePanel.GLOBAL,
    setActivePanel: (activePanel: ActiveVariablePanel) => {},
});

export function useActivePanelContext() {
    return useContext(activePanelContext);
}

window.lastActivePanel = ActiveVariablePanel.GLOBAL;

export function ThemeEditorPreviewContents() {
    const [intialInputValue, newInputValue] = useState("Text Input");
    const classes = themeEditorPreviewClasses();
    const { sendMessageOut } = useIFrameCommunication();
    const [activePanel, _setActivePanel] = useState(window.lastActivePanel);

    const setActivePanel = (activePanel: ActiveVariablePanel) => {
        // Stash on window to prevent losing data when component is reinitialized.
        window.lastActivePanel = activePanel;
        _setActivePanel(activePanel);
        sendMessageOut?.(setActivePanelAC({ panel: activePanel }));
    };

    return (
        <activePanelContext.Provider
            value={{
                activePanel,
                setActivePanel,
            }}
        >
            <PanelActivator panel={ActiveVariablePanel.TITLE_BAR} color={titleBarVariables().colors.fg}>
                <TitleBar container={null} />
            </PanelActivator>

            <PanelActivator panel={ActiveVariablePanel.BANNER} color={bannerVariables().colors.primaryContrast}>
                <Banner title={t("Welcome To Your Theme")} />
            </PanelActivator>

            <PanelActivator panel={ActiveVariablePanel.GLOBAL} color={globalVariables().mainColors.fg}>
                <Container narrow fullGutter>
                    <div className={classes.content}>
                        <div className={userContentClasses().root}>
                            <p>
                                <Translate
                                    shortSource={"This is a style guide of your theme."}
                                    source="This is a style guide of your theme. It has examples of the visual elements used throughout the application. You can click on the various widgets such as the <0>Title Bar</0> or <1>Banner</1> to edit their properties in the side panel."
                                    c0={text => (
                                        <Button
                                            baseClass={ButtonTypes.TEXT_PRIMARY}
                                            onClick={e => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setActivePanel(ActiveVariablePanel.TITLE_BAR);
                                            }}
                                        >
                                            {text}
                                        </Button>
                                    )}
                                    c1={text => (
                                        <Button
                                            baseClass={ButtonTypes.TEXT_PRIMARY}
                                            onClick={e => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setActivePanel(ActiveVariablePanel.BANNER);
                                            }}
                                        >
                                            {text}
                                        </Button>
                                    )}
                                />
                            </p>
                            <p>
                                <Translate
                                    shortSource="In additon to the widgets there are also"
                                    source="In additon to the widgets there are also <0>Global Styles</0>. To edit global styles click anywhere else on the page, such as this text."
                                    c0={text => (
                                        <Button
                                            baseClass={ButtonTypes.TEXT_PRIMARY}
                                            onClick={e => {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                setActivePanel(ActiveVariablePanel.GLOBAL);
                                            }}
                                        >
                                            {text}
                                        </Button>
                                    )}
                                />
                            </p>
                        </div>

                        <div className={classes.buttonStyles}>
                            <h2 className={classes.title}>{t("Buttons")}</h2>
                            <p>{t("There are two types of buttons in the application: primary and secondary.")}</p>
                            <div className={classes.styleContent}>
                                <Button baseClass={ButtonTypes.PRIMARY}>{t("Primary Button")}</Button>
                                <Button>{t("Secondary Button")}</Button>{" "}
                            </div>
                        </div>
                        <div className={classes.inputStyles}>
                            <h2 className={classes.title}>Inputs</h2>
                            <p>{t("User inputs are based on the global background and text colors.")}</p>
                            <div className={classes.styleContent}>
                                <InputTextBlock
                                    inputProps={{
                                        value: intialInputValue,
                                        placeholder: t("Text Input"),
                                        onChange: event => {
                                            newInputValue(event.target.value);
                                        },
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </Container>
            </PanelActivator>
        </activePanelContext.Provider>
    );
}

function PanelActivator(props: { panel: ActiveVariablePanel; children: React.ReactNode; color?: ColorHelper }) {
    const { activePanel, setActivePanel } = useActivePanelContext();
    const classes = themeEditorPreviewClasses();
    const { color } = props;
    return (
        <div
            className={classes.panelActivator}
            onClick={e => {
                e.stopPropagation();
                setActivePanel(props.panel);
            }}
        >
            {props.children}
            {props.panel === activePanel && (
                <div
                    className={classNames(classes.panelActivatorIndicator)}
                    style={{ borderColor: color ? colorOut(color) : undefined }}
                ></div>
            )}
        </div>
    );
}
