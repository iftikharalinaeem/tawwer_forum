/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { RevisionsRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/utility/appUtils";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import Permission from "@library/features/users/Permission";
import * as React from "react";
import { IArticle } from "@knowledge/@types/api/article";
import InsertUpdateMetas from "@library/result/InsertUpdateMetas";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownItem from "@library/flyouts/items/DropDownItem";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { PublishStatus } from "@library/@types/api/core";

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
        const classesDropDown = dropDownClasses();

        return (
            <Permission permission="articles.add">
                <DropDown
                    id={this.domID}
                    name={t("Article Options")}
                    buttonClassName={this.props.buttonClassName}
                    mobileTitle={t("Article")}
                    flyoutType={FlyoutType.LIST}
                >
                    <InsertUpdateMetas
                        dateInserted={dateInserted}
                        dateUpdated={dateUpdated}
                        insertUser={insertUser!}
                        updateUser={updateUser!}
                    />
                    <DropDownItemSeparator />
                    <DropDownItem>
                        <RevisionsRoute.Link data={article} className={classesDropDown.action}>
                            {t("Revision History")}
                        </RevisionsRoute.Link>
                    </DropDownItem>
                    {this.props.article.status === PublishStatus.PUBLISHED && (
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
}
