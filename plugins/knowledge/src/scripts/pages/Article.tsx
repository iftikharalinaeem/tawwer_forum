import * as React from "react";
import { Devices } from "@knowledge/components/DeviceChecker";
import PanelWidget from "../components/PanelWidget";
import PageHeading from "../components/PageHeading";
import PanelLayout, {IPanelCellContent} from "../layouts/PanelLayout";
import UserContent from "../components/UserContent";
import { t } from "@dashboard/application";
import {IBreadcrumbsProps} from "../components/Breadcrumbs";
import {IDevice} from "../components/DeviceChecker";

export interface IArticleProps extends IDevice {};

export default class Article extends React.Component<IArticleProps> {
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
                        <PanelWidget>
                            <PageHeading title={t("Knowledge Base")}/>
                        </PanelWidget>
                    ),
                    middleBottomComponents: (
                        <PanelWidget>
                            <UserContent content="<h2>Hello!</h1>" />
                        </PanelWidget>
                    ),
                    leftTopComponents: (
                        <PanelWidget>
                            <PageHeading title={t("Left Top")}/>
                        </PanelWidget>
                    ),
                    leftBottomComponents: (
                        <React.Fragment>
                            <PanelWidget>
                                <PageHeading title={t("Left Bottom")}/>
                            </PanelWidget>
                        </React.Fragment>
                    ),
                    rightTopComponents: (
                        <PanelWidget>
                            <PageHeading title={t("Right Top")}/>
                        </PanelWidget>
                    ),
                    rightBottomComponents: (
                        <PanelWidget>
                            <PageHeading title={t("Right Bottom")}/>
                        </PanelWidget>
                    ),
                }
            }
        </PanelLayout>;
    }
}
