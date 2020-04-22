/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import TitleBar from "@library/headers/TitleBar";
import Container from "@library/layout/components/Container";
import { ActiveVariablePanel, PanelActivator, useActivePanelContext } from "@themingapi/theme/ActivePanelContext";
import { t } from "@vanilla/i18n";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { bannerVariables } from "@vanilla/library/src/scripts/banner/bannerStyles";
import { userContentClasses } from "@vanilla/library/src/scripts/content/userContentStyles";
import { titleBarVariables } from "@vanilla/library/src/scripts/headers/titleBarStyles";
import React from "react";
import themeEditorPreviewClasses from "./ThemeEditorPreviewContents.styles";
import { contentBannerVariables } from "@vanilla/library/src/scripts/banner/contentBannerStyles";
import { globalVariables } from "@vanilla/library/src/scripts/styles/globalStyleVars";
import { colorOut, flexHelper } from "@vanilla/library/src/scripts/styles/styleHelpers";
import { ToolTip, ToolTipIcon } from "@vanilla/library/src/scripts/toolTip/ToolTip";
import { InformationIcon } from "@vanilla/library/src/scripts/icons/common";

export function ThemeEditorPreviewContentsContent() {
    const classes = themeEditorPreviewClasses();
    const { setActivePanel } = useActivePanelContext();

    return (
        <>
            <PanelActivator panel={ActiveVariablePanel.TITLE_BAR} color={titleBarVariables().colors.fg}>
                <TitleBar container={null} />
            </PanelActivator>

            <PreviewContentBanner />

            <Container narrow fullGutter>
                <div className={classes.content}>
                    <div className={userContentClasses().root}>
                        <p>
                            {t(
                                "This page represents the various content pages from the application.",
                                "This page represents the various content pages from the application. These styles apply to discussions, articles, search pages, and some editor pages.",
                            )}
                        </p>
                    </div>
                </div>
            </Container>
        </>
    );
}

function PreviewContentBanner() {
    const vars = contentBannerVariables();
    const classes = themeEditorPreviewClasses();

    if (vars.options.enabled) {
        return (
            <PanelActivator panel={ActiveVariablePanel.CONTENT_BANNER} color={bannerVariables().colors.primaryContrast}>
                <Banner isContentBanner title={t(bannerVariables().title.text)} />
            </PanelActivator>
        );
    } else {
        return (
            <PanelActivator
                panel={ActiveVariablePanel.CONTENT_BANNER}
                color={globalVariables().elementaryColors.almostBlack}
            >
                <div className={classes.disabledContentBanner}>
                    <p>{t("Enable the content banner in the side panel.")}</p>
                </div>
            </PanelActivator>
        );
    }
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
