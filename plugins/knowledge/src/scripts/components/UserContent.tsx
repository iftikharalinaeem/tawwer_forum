import * as React from "react";
import className from "classnames";

interface IUserContent {
    className?: string;
    content: string;
}

export default class UserContent extends React.Component<IUserContent> {
    public render() {
        return <div className={ className('userContent', this.props.className) } dangerouslySetInnerHTML={{ __html: this.props.content }} />;
    }
}
