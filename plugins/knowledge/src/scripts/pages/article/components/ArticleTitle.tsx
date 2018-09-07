/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@knowledge/components/PageHeading";
import { IArticle } from "@knowledge/@types/api";

interface IProps {
    article: IArticle;
}

export default class ArticleTitle extends React.Component<IProps> {
    public render() {
        const { article } = this.props;
        return (
            <PanelWidget>
                <PageHeading title={article.articleRevision.name} />
            </PanelWidget>
        );
    }
}
