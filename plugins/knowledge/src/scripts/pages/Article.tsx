import * as React from "react";
import { Devices } from "@knowledge/components/DeviceChecker";
import PanelWidget from "../components/PanelWidget";
import PanelArea from "../components/PanelArea";
import PageHeading from "../components/PageHeading";
import PanelLayout, {IPanelCellContent} from "../layouts/PanelLayout";
import UserContent from "../components/UserContent";
import { t } from "@dashboard/application";

export interface IProps {
    device: Devices;
}

export default class Article extends React.Component<IProps> {

    public render() {
        return <PanelLayout device={this.props.device}>
            {
                {
                    middleTopComponents: (
                        <PageHeading title={t("Knowledge Base")}/>
                    ),
                    middleBottomComponents: (
                        <UserContent content="<h2>Hello!</h1>" />
                    ),
                    leftTopComponents: (
                        <PageHeading title={t("Left Top")}/>
                    ),
                    leftBottomComponents: (
                        <React.Fragment>
                            <PageHeading title={t("Left Bottom")}/>
                        </React.Fragment>
                    ),
                    rightTopComponents: (
                        <PageHeading title={t("Right Top")}/>
                    ),
                    rightBottomComponents: (
                        <PageHeading title={t("Right Bottom")}/>
                    ),
                }
            }
        </PanelLayout>;
    }
}
