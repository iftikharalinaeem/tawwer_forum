/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import DropDown from "@library/components/dropdown/DropDown";
import { t } from "@library/application";
import { IArticle, ArticleStatus } from "@knowledge/@types/api";
import {
    DropDownItemLink,
    DropDownItemButton,
    DropDownItemSeparator,
    DropDownItem,
} from "@library/components/dropdown";
import Permission from "@library/users/Permission";
import { EditorRoute, RevisionsRoute } from "@knowledge/routes/pageRoutes";
import InsertUpdateMetas from "@knowledge/modules/common/InsertUpdateMetas";

interface IProps {
    article: IArticle;
    buttonClassName?: string;
}
/**
 * Generates drop down menu for Article page
 */
export default class EditorMenu extends React.PureComponent<IProps> {
    public render() {
        const { article } = this.props;

        const { insertUser, updateUser, dateInserted, dateUpdated } = article;

        return (
            <Permission permission="articles.add">
                <DropDown
                    id={this.domID}
                    name={t("Article Options")}
                    buttonClassName={this.props.buttonClassName}
                    renderLeft={true}
                >
                    <InsertUpdateMetas
                        dateInserted={dateInserted}
                        dateUpdated={dateUpdated}
                        insertUser={insertUser!}
                        updateUser={updateUser!}
                    />
                    <DropDownItemSeparator />
                    <DropDownItem>
                        <RevisionsRoute.Link data={article} className={DropDownItemLink.CSS_CLASS}>
                            {t("Revision History")}
                        </RevisionsRoute.Link>
                    </DropDownItem>
                    {this.props.article.status === ArticleStatus.PUBLISHED && (
                        <DropDownItemLink name={t("View Article")} to={this.props.article.url} />
                    )}
                </DropDown>
            </Permission>
        );
    }

    /**
     * HTML ID for the component.
     */
    private get domID(): string {
        return "articleMenuDropDown-" + this.props.article.articleID;
    }

    /**
     * Fallback click handle until all functionaility has been implemented.
     */
    private dummyClick = () => {
        alert("Click works");
    };
}
