/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardTable } from "@dashboard/tables/DashboardTable";
import React, { useState } from "react";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import { BrowserRouter } from "react-router-dom";
const { HeadItem } = DashboardTable;

interface IProps {
}

interface IState {}

export default class ThemeEditorPage extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
    }
    public render() {
        return (
            <BrowserRouter>
                <DashboardHeaderBlock
                    title={t("Theme Editor")}
                />
            </BrowserRouter>
        );
    }
}
