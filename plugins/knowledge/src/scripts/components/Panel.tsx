import * as React from "react";
import className from "classnames";
import PanelArea from "./PanelArea";

interface IPanelArea {
    children?: JSX.Element;
    className: string;
}

interface IPanelArea {
    top: IPanelArea;
    bottom: IPanelArea;
}

interface IPanel {
    className?: string;
    children: IPanelArea;
    rendered?: boolean;
}


export default class Panel extends React.Component<IPanel> {
    public static defaultProps = {
        rendered: true,
    };

    public render() {
        if (this.props.rendered) {
            return (
                <React.Fragment>
                    <PanelArea className={ className('panelLayout-panel', this.props.children.top.className) }>
                        { this.props.children.top.children }
                    </PanelArea>
                    <PanelArea className={ className('panelLayout-panel', this.props.children.bottom.className) }>
                        { this.props.children.bottom.children }
                    </PanelArea>
                </React.Fragment>
            );
        } else {
            return null;
        }
    }
}
