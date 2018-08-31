/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { match } from "react-router";
import { t } from "@dashboard/application";
import { connect } from "react-redux";
import { IStoreState, IArticlePageState } from "@knowledge/@types/state";
import { IBreadcrumbsProps } from "@knowledge/components/Breadcrumbs";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import PanelWidget from "@knowledge/components/PanelWidget";
import PageHeading from "@knowledge/components/PageHeading";
import UserContent from "@knowledge/components/UserContent";
import { IDeviceProps } from "@knowledge/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Container from "@knowledge/layouts/components/Container";
import { Devices } from "@knowledge/components/DeviceChecker";
import MobileMenu from "@knowledge/layouts/components/MobileMenu";
import { dummyTestPost1, dummyTestPost2, dummyPostEverything } from "@knowledge/dummyPostHTML";
import { LoadStatus } from "@dashboard/@types/api";

interface IProps extends IDeviceProps {
    match: match<{
        id: string;
    }>;
    articlePageState: IArticlePageState;
}

interface IState {
    menuOpen: boolean;
}

export class ArticlePage extends React.Component<IProps, IState> {
    constructor(props) {
        super(props);
        this.state = {
            menuOpen: false,
        };
    }

    public render() {
        if (this.props.articlePageState.status !== LoadStatus.SUCCESS) {
            return null;
        }

        const article = this.props.articlePageState.data.article;

        return (
            <React.Fragment>
                <MobileMenu render={this.props.device === Devices.MOBILE && this.state.menuOpen}>
                    <PanelWidget>
                        <PageHeading title={t("Navigation")} />
                    </PanelWidget>
                </MobileMenu>
                <Container>
                    <PanelLayout
                        device={this.props.device}
                        breadcrumbs={this.dummyBreadcrumbData}
                        toggleMobileMenu={toggleMobileMenu}
                    >
                        {{
                            leftTopComponents: (
                                <PanelWidget>
                                    <PageHeading title={t("Actions")} />
                                </PanelWidget>
                            ),
                            leftBottomComponents: (
                                <React.Fragment>
                                    <PanelWidget>
                                        <PageHeading title={t("Navigation")} />
                                    </PanelWidget>
                                </React.Fragment>
                            ),
                            middleTopComponents: (
                                <PanelWidget>
                                    <PageHeading title={article.name} />
                                </PanelWidget>
                            ),
                            middleBottomComponents: (
                                <PanelWidget>
                                    <UserContent content={this.dummyPostData} />
                                </PanelWidget>
                            ),
                            rightTopComponents: (
                                <PanelWidget>
                                    <PageHeading title={t("Table of Contents")} />
                                </PanelWidget>
                            ),
                            rightBottomComponents: (
                                <PanelWidget>
                                    <PageHeading title={t("Related Articles")} />
                                </PanelWidget>
                            ),
                        }}
                    </PanelLayout>
                </Container>
            </React.Fragment>
        );
    }

    private get dummyPostData(): string {
        switch (this.props.match.params.id) {
            case "test-post-1":
                return dummyTestPost1;
            case "test-post-2":
                return dummyTestPost2;
            case "test-post-all":
                return dummyPostEverything;
            default:
                return "No dummy post data exists for this URL";
        }
    }

    private get dummyBreadcrumbData(): IBreadcrumbsProps {
        return {
            children: [
                {
                    name: "one",
                    url: "#",
                },
                {
                    name: "two",
                    url: "#",
                },
                {
                    name: "three",
                    url: "#",
                },
                {
                    name: "four",
                    url: "#",
                },
                {
                    name: "five",
                    url: "#",
                },
                {
                    name: "six",
                    url: "#",
                },
            ],
        };
    }
}

function mapStateToProps(state: IStoreState) {
    return {
        articlePageState: state.knowledge.articlePage,
    };
}

function toggleMobileMenu(open: boolean = !this.state.open) {
    this.setState({
        menuOpen: open,
    });
}

const withRedux = connect(mapStateToProps);

export default withRedux(withDevice(ArticlePage));
