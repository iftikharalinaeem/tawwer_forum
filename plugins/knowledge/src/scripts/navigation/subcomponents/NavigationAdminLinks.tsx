/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/utility/appUtils";
import { organize } from "@library/icons/navigationManager";
import Permission from "@library/features/users/Permission";
import classNames from "classnames";
import * as React from "react";
import { siteNavAdminLinksClasses } from "@library/navigation/siteNavAdminLinksStyles";

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
                        <hr role="separator" className={classNames("siteNavAdminLinks-divider", classes.divider)} />
                    )}
                    <h3 className="sr-only">{t("Admin Links")}</h3>
                    <li className={classNames("siteNavAdminLinks-item", classes.item)}>
                        <OrganizeCategoriesRoute.Link
                            className={classNames("siteNavAdminLinks-link", classes.link)}
                            data={{ kbID: this.props.kbID }}
                        >
                            {organize(classes.linkIcon)}
                            {t("Organize Categories")}
                        </OrganizeCategoriesRoute.Link>
                    </li>
                </ul>
            </Permission>
        );
    }
}
