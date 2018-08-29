import * as React from "react";
import className from "classnames";



export interface IBreadcrumbProps {
    className?: string;
    lastElement: boolean;
    url: string;
    name: string;
    key?: number;
}


export default class Breadcrumbs extends React.Component<IBreadcrumbProps> {

    public render() {
        let ariaCurrent;
        if (this.props.lastElement) {
            ariaCurrent = `page`;
        }

        return (
            <a href={this.props.url} key={this.props.key} title={this.props.name} aria-current={ariaCurrent} className={className("breadcrumb-link", this.props.className)} itemScope itemType='http://schema.org/Thing' itemProp='item'>
                <span className='breadcrumb-label' itemProp='name'>{this.props.name}</span>
            </a>
        );
    }
}
