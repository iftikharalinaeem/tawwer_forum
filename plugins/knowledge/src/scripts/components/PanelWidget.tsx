import * as React from "react";
import className from "classnames";

interface IPanelWidget {
    className?: string;
    children: JSX.Element;
}

export default class PanelWidget extends React.Component<IPanelWidget> {
    public render() {
        return (
            <div className={className('panelWidget', this.props.className)}>
                { this.props.children }
            </div>
        );
    }
}
