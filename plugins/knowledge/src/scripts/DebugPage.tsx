/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Link } from "react-router-dom";
import { t } from "@library/application";
import DocumentTitle from "@library/components/DocumentTitle";
import VanillaHomeHeader from "@library/components/headers/VanillaHomeHeader";
import { EditorRoute, SearchRoute, DraftsRoute, OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";

export default class DebugPage extends React.Component {
    public render() {
        return (
            <div className="container">
                <VanillaHomeHeader />
                <DocumentTitle title={t("Debug")} />
                <p>{t("Hello Home Page. Links are below:")}</p>
                <p>
                    <Link to="/kb/articles/1-article-one">{t("Article One")}</Link>
                </p>
                <p>
                    <Link to="/kb/articles/2-article-two">{t("Article Two")}</Link>
                </p>
                <p>
                    <Link to="/kb/articles/3-article-two">{t("Article Three")}</Link>
                </p>
                <p>
                    <Link to="/kb/categories/1-category-one">{t("Category One")}</Link>
                </p>
                <p>
                    <Link to="/kb/categories/2-category-two">{t("Category Two")}</Link>
                </p>
                <p>
                    <EditorRoute.Link data={undefined}>{t("Create new article")}</EditorRoute.Link>
                </p>
                <p>
                    <SearchRoute.Link data={undefined}>{t("Search Page")}</SearchRoute.Link>
                </p>
                <p>
                    <DraftsRoute.Link data={undefined}>{t("Drafts")}</DraftsRoute.Link>
                </p>
                <p>
                    <OrganizeCategoriesRoute.Link data={{ kbID: 1 }}>
                        {t("Organize Categories")}
                    </OrganizeCategoriesRoute.Link>
                </p>
            </div>
        );
    }
}
