/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { IArticle } from "@knowledge/@types/api";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import UserContent from "@knowledge/components/UserContent";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";

interface IProps {
    article: IArticle;
}

export default class ArticleContent extends React.Component<IProps> {
    public render() {
        const test1 = `test 1`;
        const test2 = `test 2`;
        const test3 = `test 3`;

        const { article } = this.props;
        return (
            <PanelWidget>
                <DropDown name={t("Test DropDown")} className="testDropDown">
                    <p>{test1}</p>
                    <p>{test2}</p>
                    <p>{test3}</p>
                </DropDown>

                <UserContent content={article.articleRevision.bodyRendered} />
            </PanelWidget>
        );
    }
}
