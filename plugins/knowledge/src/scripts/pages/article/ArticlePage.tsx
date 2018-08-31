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
import Heading from "@knowledge/components/Heading";
import { Devices } from "@knowledge/components/DeviceChecker";
import MobileMenu from "@knowledge/layouts/components/MobileMenu";

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
        const breadcrumbData: IBreadcrumbsProps = {
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

        // @ts-ignore
        const article = this.props.articlePageState.data.article;

        return (
            <>
                <MobileMenu render={this.props.device === Devices.MOBILE && this.state.menuOpen} />
                <Container>
                    <PanelLayout
                        device={this.props.device}
                        breadcrumbs={breadcrumbData}
                        toggleMobileMenu={toggleMobileMenu}
                    >
                        {{
                            leftTopComponents: (
                                <PanelWidget>
                                    <Heading title={t("Actions")} depth={2} />
                                </PanelWidget>
                            ),
                            leftBottomComponents: (
                                <React.Fragment>
                                    <PanelWidget>
                                        <Heading title={t("Navigation")} depth={2} />
                                    </PanelWidget>
                                </React.Fragment>
                            ),
                            middleTopComponents: (
                                <PanelWidget>
                                    <PageHeading title={article.name} backUrl={`#`} />
                                </PanelWidget>
                            ),
                            middleBottomComponents: (
                                <PanelWidget>
                                    <UserContent content={article.bodyRendered} />
                                </PanelWidget>
                            ),
                            rightTopComponents: (
                                <PanelWidget>
                                    <Heading title={t("Table of Contents")} depth={2} />
                                </PanelWidget>
                            ),
                            rightBottomComponents: (
                                <PanelWidget>
                                    <Heading title={t("Related Articles")} depth={2} />
                                </PanelWidget>
                            ),
                        }}
                    </PanelLayout>
                </Container>
            </>
        );
    }
}

function toggleMobileMenu(open: boolean = !this.state.open) {
    this.setState({
        menuOpen: open,
    });
}

function mapStateToProps(state: IStoreState) {
    return {
        articlePageState: state.knowledge.articlePage,
    };
}

const withRedux = connect(mapStateToProps);

export default withRedux(withDevice(ArticlePage));
