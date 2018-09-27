/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { IArticle } from "@knowledge/@types/api";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import UserContent from "@knowledge/components/UserContent";
import { t } from "@library/application";

interface IProps {
    article: IArticle;
}

export default class ArticleContent extends React.Component<IProps> {
    public render() {
        const { article } = this.props;
        return (
            <PanelWidget>
                <UserContent content={article.articleRevision.bodyRendered} />
            </PanelWidget>
        );
    }
}
