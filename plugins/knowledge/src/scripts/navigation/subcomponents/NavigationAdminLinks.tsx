/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/application";
import { organize } from "@library/components/icons/navigationManager";
import Permission from "@library/users/Permission";
import classNames from "classnames";
import * as React from "react";

interface IProps {
    className?: string;
    kbID: number;
    showDivider: boolean;
}

/**
 * Implementation of SiteNav component
 */
export default class NavigationAdminLinks extends React.Component<IProps> {
    public render() {
        return (
            <Permission permission="articles.add">
                <ul className={classNames("siteNavAdminLinks", this.props.className)}>
                    {this.props.showDivider && <hr className="siteNavAdminLinks-divider" />}
                    <h3 className="sr-only">{t("Admin Links")}</h3>
                    <li className="siteNavAdminLinks-item">
                        {organize()}
                        <OrganizeCategoriesRoute.Link
                            className="siteNavAdminLinks-link"
                            data={{ kbID: this.props.kbID }}
                        >
                            {t("Organize Categories")}
                        </OrganizeCategoriesRoute.Link>
                    </li>
                </ul>
            </Permission>
        );
    }
}
