/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Link } from "react-router-dom";
import { t } from "@library/application";

export default class HomePage extends React.Component {
    public render() {
        return (
            <div>
                <p>{t("Hello Home Page. Links are below:")}</p>
                <p>
                    <Link to="/kb/articles/test-articles--text-post-1">{t("Test Article - Text Post")}</Link>
                </p>
                <p>
                    <Link to="/kb/articles/test-articles--another-test-post-2">
                        {t("Test Article - Another Text Post")}
                    </Link>
                </p>
                <p>
                    <Link to="/kb/articles/test-articles--all-formatting--embeds-3">
                        {t("Test Article - All Formatting & Embeds")}
                    </Link>
                </p>
            </div>
        );
    }
}
