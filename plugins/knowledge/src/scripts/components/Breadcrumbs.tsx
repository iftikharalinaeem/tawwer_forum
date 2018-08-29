import * as React from "react";
import className from "classnames";
import { t } from "@dashboard/application";
import Breadcrumb from "./Breadcrumb";

interface IBreadcrumbProps {
    name: string;
    url: string;
}

export interface IBreadcrumbsProps {
    className?: string;
    children: IBreadcrumbProps[];
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

                const breadcrumb = <Breadcrumb lastElement={lastElement} name={crumb.name} key={index} url={crumb.url}/>;

                if (lastElement) {
                    return breadcrumb;
                } else {
                    return (
                        <React.Fragment key={index}>
                            {breadcrumb}
                            <li className='breadcrumb-item breadcrumbs-separator'><span className='breadcrumbs-separatorIcon'>{crumbSeparator}</span></li>
                        </React.Fragment>
                    );
                }
            });
            return (
                <nav aria-label={t('Breadcrumb')} className={className("breadcrumbs", this.props.className)}>
                    <ol className="breadcrumbs-list">
                        {crumbs}
                    </ol>
                </nav>
            );
        } else {
            return null;
        }
    }
}
