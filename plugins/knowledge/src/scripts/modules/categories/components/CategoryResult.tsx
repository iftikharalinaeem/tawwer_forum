/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { Devices } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IPageHeading } from "@knowledge/modules/article/components/ArticleTOC";
import { IInternalLink } from "@knowledge/modules/article/components/ArticleRelatedArticles";
import { InlineTypes } from "@library/components/Sentence";
import {t} from "@library/application";

interface IProps {}

interface IState {}

export class CategoryResult extends React.Component<IProps, IState> {
    public render() {
        return (
            <div>{t("Result")}</div>
        );
    }
}
