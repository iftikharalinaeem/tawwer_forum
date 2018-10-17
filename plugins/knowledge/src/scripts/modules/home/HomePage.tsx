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

export default class HomePage extends React.Component {
    public render() {
        return (
            <div className="container">
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
                    <ModalLink to="/kb/articles/add">{t("Add article (in 'modal')")}</ModalLink>
                </p>
            </div>
        );
    }
}
