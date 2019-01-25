/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { IHelpData, IHelpGroup } from "@knowledge/modules/navigation/NavigationSelector";
import { t } from "@library/application";
import SmartLink from "@library/components/navigation/SmartLink";

export default class HelpCenterNavigation extends React.Component<IProps> {
    public render() {
        const { data } = this.props;
        return (
            <div>
                <h2>{t("groups")}</h2>
                <div>{data.groups.map(this.renderGroup)}</div>
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
                <ul>
                    {articles.map(article => (
                        <li key={article.recordID}>
                            <SmartLink to={article.url}>{article.name}</SmartLink>
                        </li>
                    ))}
                </ul>
            </div>
        );
    };
}

interface IProps {
    data: IHelpData;
}
