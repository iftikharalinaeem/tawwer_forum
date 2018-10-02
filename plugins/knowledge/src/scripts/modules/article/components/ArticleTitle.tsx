/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@library/components/PageHeading";
import { IArticle } from "@knowledge/@types/api";

interface IProps {
    article: IArticle;
    menu?: JSX.Element;
}

export default class ArticleTitle extends React.Component<IProps> {
    public render() {
        const { article } = this.props;
        return (
            <PanelWidget>
                <PageHeading title={article.articleRevision.name} menu={this.props.menu} />
            </PanelWidget>
        );
    }
}
