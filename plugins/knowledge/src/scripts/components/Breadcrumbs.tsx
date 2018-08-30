/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import className from "classnames";
import { t } from "@dashboard/application";
import Breadcrumb from "@knowledge/components/Breadcrumb";

interface IBreadcrumbProps {
    name: string;
    url: string;
}

export interface IBreadcrumbsProps {
    children: IBreadcrumbProps[];
    className?: string;
}

export default class Breadcrumbs extends React.Component<IBreadcrumbsProps> {
    public render() {
        if (this.props.children.length > 1) {
            const crumbCount = this.props.children.length - 1;
            const crumbs = this.props.children.map((crumb, index) => {
                const lastElement = index === crumbCount;
                const crumbSeparator = `›`;
                let ariaCurrent;

                if (lastElement) {
                    ariaCurrent = `page`;
                }
                return (
                    <React.Fragment key={`breadcrumb-${index}`}>
                        <Breadcrumb lastElement={lastElement} name={crumb.name} url={crumb.url} />
                        {!lastElement && (
                            <li className="breadcrumb-item breadcrumbs-separator">
                                <span className="breadcrumbs-separatorIcon">{crumbSeparator}</span>
                            </li>
                        )}
                    </React.Fragment>
                );
            });
            return (
                <nav aria-label={t("Breadcrumb")} className={className("breadcrumbs", this.props.className)}>
                    <ol className="breadcrumbs-list">{crumbs}</ol>
                </nav>
            );
        } else {
            return null;
        }
    }
}
