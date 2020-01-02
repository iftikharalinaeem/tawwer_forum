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

export default function ManageThemingPage(props) {
    const themePreviewDefault = {
        globalBg: "#fff",
        globalPrimary: "#985E6D",
        globalFg: "#555a62",
        titleBarBg: "#0291db",
        titleBarFg: "#fff",
        isActiveTheme: true
    };
    const {
                currentTheme = {
                    name: 'Theme',
                    author: 'Author',
                    description: 'Description',
                    preview: themePreviewDefault
                }
            } = props;
    const { templateThemes = [
            { preview: themePreviewDefault},
            { preview: themePreviewDefault},
            { preview: themePreviewDefault}
        ]
        } = props;
    const { customThemes = [] } = props;
    const globalVars = globalVariables();
    return (
        <BrowserRouter>
            <DashboardHeaderBlock
                title={t("Themes")}
            />
            <div>
            <ThemePreviewCard
                globalBg={currentTheme.preview.globalBg}
                globalFg={currentTheme.preview.globalFg}
                globalPrimary={currentTheme.preview.globalPrimary}
                titleBarBg={currentTheme.preview.titleBarBg}
                titleBarFg={currentTheme.preview.titleBarFg}
                isActiveTheme={currentTheme.preview.isActiveTheme}
            />
            <CurrentThemeInfo name={currentTheme.name} authors={currentTheme.author} description={currentTheme.description}

            />
            </div>

            <DashboardHeaderBlock
                title={t("Templates")}
            />
            <div style={{display: "flex"}}>
            { templateThemes.map((templateTheme, key) => (
                        <ThemePreviewCard
                        globalBg={templateTheme.preview.globalBg}
                        globalFg={templateTheme.preview.globalFg}
                        globalPrimary={templateTheme.preview.globalPrimary}
                        titleBarBg={templateTheme.preview.titleBarBg}
                        titleBarFg={templateTheme.preview.titleBarFg}
                        isActiveTheme={templateTheme.isActive}
                        />

                    )
                )
            }
            </div>
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
