import * as React from "react";

interface IPageHeading {
    title: string;
}

export default class PageHeading extends React.Component<IPageHeading> {
    public render() {
        return <h1 className="pageTitle">{ this.props.title }</h1>;
    }
}
