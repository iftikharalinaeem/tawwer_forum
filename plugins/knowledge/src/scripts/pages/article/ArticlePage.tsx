/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { match } from "react-router";
import { t } from "@dashboard/application";
import {connect} from "react-redux";
import { IStoreState, IArticlePageState } from "@knowledge/@types/state";
import {IBreadcrumbsProps} from "../../components/Breadcrumbs";
import PanelLayout from "../../layouts/PanelLayout";
import PanelWidget from "../../components/PanelWidget";
import PageHeading from "../../components/PageHeading";
import UserContent from "../../components/UserContent";
import {IDeviceProps} from "../../components/DeviceChecker";
import {withDevice} from "../../contexts/DeviceContext";

interface IProps extends IDeviceProps {
    match: match<{
        id: string;
    }>;
    articlePageState: IArticlePageState;
}

export class ArticlePage extends React.Component<IProps> {
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

function mapStateToProps(state: IStoreState) {
    return {
        articlePageState: state.knowledge.articlePage,
    };
}

const withRedux = connect(mapStateToProps);

export default withRedux(withDevice(ArticlePage));
