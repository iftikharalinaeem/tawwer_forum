/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ButtonTypes } from "@library/forms/buttonTypes";
import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { PanelActivator, useActivePanelContext, ActiveVariablePanel } from "@themingapi/theme/ActivePanelContext";
import { t } from "@vanilla/i18n";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { bannerVariables } from "@vanilla/library/src/scripts/banner/bannerStyles";
import Translate from "@vanilla/library/src/scripts/content/Translate";
import { userContentClasses } from "@vanilla/library/src/scripts/content/userContentStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { titleBarVariables } from "@vanilla/library/src/scripts/headers/titleBarStyles";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import React, { useState } from "react";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import UserContent from "@vanilla/library/src/scripts/content/UserContent";

export function ThemeEditorPreviewContentsContent() {
    const classes = themeEditorPreviewClasses();
    const { setActivePanel } = useActivePanelContext();

    return (
        <>
            <PanelActivator panel={ActiveVariablePanel.TITLE_BAR} color={titleBarVariables().colors.fg}>
                <TitleBar container={null} />
            </PanelActivator>

            <PanelActivator panel={ActiveVariablePanel.BANNER} color={bannerVariables().colors.primaryContrast}>
                <Banner isContentBanner title={t("Welcome To Your Theme")} />
            </PanelActivator>

            <Container narrow fullGutter>
                <div className={classes.content}>
                    <div className={userContentClasses().root}>
                        <p>
                            {t(
                                "This is a style guide of your theme.",
                                "This is a style guide of your theme. It has examples of the visual elements used throughout the application.",
                            )}
                        </p>
                        <p>
                            <Translate
                                shortSource={"You can click on the various widgets such as the <0>Content Banner</0>"}
                                source="You can click on the various widgets such as the <0>content banner</0> or <1>table</1> to edit their properties in the side panel."
                                c0={text => (
                                    <Button
                                        baseClass={ButtonTypes.TEXT_PRIMARY}
                                        onClick={e => {
                                            e.preventDefault();
                                            e.stopPropagation();
                                            setActivePanel(ActiveVariablePanel.CONTENT_BANNER);
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
                                            setActivePanel(ActiveVariablePanel.USER_CONTENT);
                                        }}
                                    >
                                        {text}
                                    </Button>
                                )}
                            />
                        </p>
                    </div>

                    <div className={classes.buttonStyles}>
                        <h2 className={classes.title}>{t("Tables")}</h2>
                        <p>{t("Tables may appear in articles.")}</p>
                        <UserContent content={tableSample} />
                    </div>
                </div>
            </Container>
        </>
    );
}

const tableSample = `
<table>
<thead>
    <tr>
        <th>#</th>
        <th>Name 1</th>
        <th>Name 2</th>
        <th>Name 3</th>
    </tr>
</thead>
<tbody>
    <tr>
        <th>1</th>
        <td>Mark</td>
        <td>Gloria</td>
        <td>Otto</td>
    </tr>
    <tr>
        <th>2</th>
        <td>Arthur</td>
        <td>Elise</td>
        <td>Thomto</td>
    </tr>
    <tr>
        <th>3</th>
        <td>John</td>
        <td>Larry</td>
        <td>Louise</td>
    </tr>
</tbody>
</table>
`;
