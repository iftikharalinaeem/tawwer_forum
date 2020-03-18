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

const activePanelContext = React.createContext({
    activePanel: ActiveVariablePanel.GLOBAL,
    setActivePanel: (activePanel: ActiveVariablePanel) => {},
});

export function useActivePanelContext() {
    return useContext(activePanelContext);
}

export function ThemeEditorPreviewContents() {
    const [intialInputValue, newInputValue] = useState("Text Input");
    const classes = themeEditorPreviewClasses();
    const { sendMessageOut } = useIFrameCommunication();
    const [activePanel, _setActivePanel] = useState(ActiveVariablePanel.GLOBAL);

    const setActivePanel = (activePanel: ActiveVariablePanel) => {
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
            <PanelActivator panel={ActiveVariablePanel.TITLE_BAR}>
                <TitleBar container={null} />
            </PanelActivator>

            <PanelActivator panel={ActiveVariablePanel.BANNER}>
                <Banner title="Welcome To Your Theme" />
            </PanelActivator>

            <PanelActivator panel={ActiveVariablePanel.GLOBAL}>
                <Container narrow fullGutter>
                    <div className={classes.content}>
                        <div className={userContentClasses().root}>
                            <p>
                                <Translate
                                    source={t(
                                        "This is a style guide of your theme.",
                                        "This is a style guide of your theme. It has examples of the visual elements used throughout the application. You can click on the various widgets such as the <0>Title Bar</0> or <1>Banner</1> to edit their properties in the side panel.",
                                    )}
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
                                    source={t(
                                        "In additon to the widgets there are also",
                                        "In additon to the widgets there are also <0>Global Styles</0>. To edit global styles click anywhere else on the page, such as this text.",
                                    )}
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
                            <h2 className={classes.title}>Buttons</h2>
                            <p>There are two types of buttons in the application: primary and secondary.</p>
                            <div className={classes.styleContent}>
                                <Button baseClass={ButtonTypes.PRIMARY}>Primary Button</Button>
                                <Button>Secondary Button</Button>{" "}
                            </div>
                        </div>
                        <div className={classes.inputStyles}>
                            <h2 className={classes.title}>Inputs</h2>
                            <p>User inputs are based on the global background and text colors.</p>
                            <div className={classes.styleContent}>
                                <InputTextBlock
                                    inputProps={{
                                        value: intialInputValue,
                                        placeholder: "Text Input",
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

function PanelActivator(props: { panel: ActiveVariablePanel; children: React.ReactNode }) {
    const { activePanel, setActivePanel } = useActivePanelContext();
    const classes = themeEditorPreviewClasses();
    return (
        <div
            className={classes.panelActivator}
            onClick={e => {
                e.stopPropagation();
                setActivePanel(props.panel);
            }}
        >
            {props.children}
            {props.panel === activePanel && <div className={classes.panelActivatorIndicator}></div>}
        </div>
    );
}
