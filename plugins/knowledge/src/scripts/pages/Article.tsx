import * as React from "react";
import { Devices } from "@knowledge/components/DeviceChecker";
import PanelWidget from "../components/PanelWidget";
import PanelArea from "../components/PanelArea";
import PageHeading from "../components/PageHeading";
import PanelLayout, {IPanelCellContent} from "../layouts/PanelLayout";
import UserContent from "../components/UserContent";
import { t } from "@dashboard/application";
import {IBreadcrumbsProps} from "../components/Breadcrumbs";

export interface IProps {
    device: Devices;
}

export default class Article extends React.Component<IProps> {
    public render() {
        const breadcrumbDummyData:IBreadcrumbsProps = {
            className: "breadcrumbs-test",
            children: [
                {
                    name: "one",
                    url: "#",
                },{
                    name: "two",
                    url: "#",
                },{
                    name: "three",
                    url: "#",
                },{
                    name: "four",
                    url: "#",
                },{
                    name: "five",
                    url: "#",
                },{
                    name: "six",
                    url: "#",
                },
            ],
        };

        return <PanelLayout device={this.props.device} breadcrumbs={breadcrumbDummyData}>
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
