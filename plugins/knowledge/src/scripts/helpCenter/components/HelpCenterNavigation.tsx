/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IHelpData, IHelpGroup, NavArticle } from "@knowledge/modules/navigation/NavigationSelector";
import { t } from "@library/application";
import SmartLink from "@library/components/navigation/SmartLink";
import React from "react";

export default class HelpCenterNavigation extends React.Component<IProps> {
    public render() {
        const { data } = this.props;
        return (
            <div>
                <div>{data.groups.map(this.renderGroup)}</div>
                <div>
                    <h2>{t("Ungrouped articles")}</h2>
                    {data.ungroupedArticles.map(this.renderArticle)}
                </div>
            </div>
        );
    }

    private renderGroup = (group: IHelpGroup) => {
        const { category, articles } = group;
        return (
            <div key={category.recordID}>
                <h3>
                    <SmartLink to={category.url} />
                    {category.name}
                </h3>
                <ul>{articles.map(this.renderArticle)}</ul>
            </div>
        );
    };

    private renderArticle = (article: NavArticle) => {
        return (
            <li key={article.recordID}>
                <SmartLink to={article.url}>{article.name}</SmartLink>
            </li>
        );
    };
}

interface IProps {
    data: IHelpData;
}
