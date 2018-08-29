import * as React from "react";
import className from "classnames";

interface IPanelArea {
    className: string;
    children: JSX.Element;
    render?: boolean;
}

export default class PanelArea extends React.Component<IPanelArea> {
    public static defaultProps = {
        render: true,
    };
    public render() {
        if (this.props.render && this.props.children) {
            return (
                <div className={className('panelArea', this.props.className)}>
                    { this.props.children }
                </div>
            );
        } else {
            return null;
        }
    }
}
