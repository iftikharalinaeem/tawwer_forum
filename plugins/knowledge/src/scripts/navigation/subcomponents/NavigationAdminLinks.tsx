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
import { siteNavAdminLinksClasses } from "@library/styles/siteNavAdminLinksStyles";

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
        const classes = siteNavAdminLinksClasses();
        return (
            <Permission permission="articles.add">
                <ul className={classNames("siteNavAdminLinks", this.props.className, classes.root)}>
                    {this.props.showDivider && (
                        <hr className={classNames("siteNavAdminLinks-divider", classes.divider)} />
                    )}
                    <h3 className="sr-only">{t("Admin Links")}</h3>
                    <li className={classNames("siteNavAdminLinks-item", classes.item)}>
                        {organize()}
                        <OrganizeCategoriesRoute.Link
                            className={classNames("siteNavAdminLinks-link", classes.link)}
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
