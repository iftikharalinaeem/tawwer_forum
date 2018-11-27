/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Link } from "react-router-dom";
import { t } from "@library/application";
import { ModalLink } from "@library/components/modal";
import DocumentTitle from "@library/components/DocumentTitle";
import VanillaHomeHeader from "@library/components/headers/VanillaHomeHeader";
import { IMobileDropDownProps } from "@library/components/headers/pieces/MobileDropDown";

export default class HomePage extends React.Component {
    public render() {
        return (
            <div className="container">
                <VanillaHomeHeader />
                <DocumentTitle title={t("Home")} />
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
                    <Link to="/kb/articles/add">{t("Add article (in 'modal')")}</Link>
                </p>
                <p>
                    <Link to="/kb/search">{t("Search")}</Link>
                </p>
                <p>
                    <Link to="/kb/drafts">{t("Drafts")}</Link>
                </p>
            </div>
        );
    }
}
