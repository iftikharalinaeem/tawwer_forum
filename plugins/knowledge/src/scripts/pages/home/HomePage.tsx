/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Link } from "react-router-dom";
import { t } from "@dashboard/application";

export default class HomePage extends React.Component {
    public render() {
        return (
            <div>
                <p>{t("Hello Home Page. Links are below:")}</p>
                <p>
                    <Link to="/kb/articles/test-post-1">{t("/articles/test-post-1")}</Link>
                </p>
                <p>
                    <Link to="/kb/articles/test-post-2">{t("/articles/test-post-2")}</Link>
                </p>
                <p>
                    <Link to="/kb/articles/test-post-all">{t("/articles/test-post-all")}</Link>
                </p>
            </div>
        );
    }
}
