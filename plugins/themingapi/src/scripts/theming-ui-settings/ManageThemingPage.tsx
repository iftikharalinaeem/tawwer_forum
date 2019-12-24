/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardTable } from "@dashboard/tables/DashboardTable";
import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { BrowserRouter } from "react-router-dom";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import CurrentThemeInfo from "@library/theming/CurrentThemeInfo";
import {globalVariables} from "@library/styles/globalStyleVars";

interface IPreviewThemeVars {
    globalBg: string;
    globalFg: string;
    globalPrimary: string;
    titleBarBg: string;
    titleBarFg: string;
}

interface ITheme {
    name: string;
    author: string;
    description: string;
    isActive: boolean;
    preview: IPreviewThemeVars;
}
interface IProps {
    currentTheme: ITheme;
    templateThemes: ITheme[];
    customThemes: ITheme[];
}

interface IState {}

export default class ManageThemingPage extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }
    public render() {
        const { currentTheme, templateThemes, customThemes } = this.props;
        const globalVars = globalVariables();
        return (
            <BrowserRouter>
                <DashboardHeaderBlock
                    title={t("Themes")}
                />
                <CurrentThemeInfo name={currentTheme.name} authors={currentTheme.author} description={currentTheme.description}

                />

                <DashboardHeaderBlock
                    title={t("Templates")}
                />
                { templateThemes.map((templateTheme, key) => (
                        <ThemePreviewCard
                        globalBg={templateTheme.preview.globalBg}
                        globalFg={templateTheme.preview.globalFg}
                        globalPrimary={templateTheme.preview.globalPrimary}
                        titleBarBg={templateTheme.preview.titleBarBg}
                        titleBarFg={templateTheme.preview.titleBarFg}
                        isActiveTheme={templateTheme.isActive}
                        />)
                    )
                }

                <DashboardHeaderBlock
                    title={t("Custom themes")}
                />
                { customThemes.map((templateTheme, key) => (
                    <ThemePreviewCard
                        globalBg={templateTheme.preview.globalBg}
                        globalFg={templateTheme.preview.globalFg}
                        globalPrimary={templateTheme.preview.globalPrimary}
                        titleBarBg={templateTheme.preview.titleBarBg}
                        titleBarFg={templateTheme.preview.titleBarFg}
                        isActiveTheme={templateTheme.isActive}
                    />)
                )
                }

            </BrowserRouter>
        );
    }
}
