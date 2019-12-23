/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { KnowledgeBaseStatus, useKnowledgeBases } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { KnowledgeBaseTableRow } from "@knowledge/knowledge-settings/KnowledgeBaseTableRow";
import Loader from "@library/loaders/Loader";
import qs from "qs";
import React, { useState } from "react";
import { EmptyKnowledgeBasesResults } from "@knowledge/knowledge-settings/EmptyKnowledgeBasesResults";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { KnowledgeBaseAddEdit } from "@knowledge/knowledge-settings/KnowledgeBaseAddEdit";
import { BrowserRouter } from "react-router-dom";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { useKnowledgeBaseActions } from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import { KnowledgeBasePatchStatusModal } from "@knowledge/knowledge-settings/KnowledgeBasePatchStatusModal";
import { KnowledgeBasePurgeModal } from "@knowledge/knowledge-settings/KnowledgeBasePurgeModal";
import ThemePreviewCard from "@library/theming/ThemePreviewCard";
import CurrentThemeInfo from "@library/theming/CurrentThemeInfo";
const { HeadItem } = DashboardTable;


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
                    />)
                )
                }

            </BrowserRouter>
        );
    }
}
