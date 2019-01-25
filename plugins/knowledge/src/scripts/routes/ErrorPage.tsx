/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import PanelLayout from "@library/components/layouts/PanelLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import Paragraph from "@library/components/Paragraph";
import Container from "@library/components/layouts/components/Container";
import { formatUrl, t } from "@library/application";
import { LoadStatus } from "@library/@types/api";
import DocumentTitle from "@library/components/DocumentTitle";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import { connect } from "react-redux";
import Heading from "@library/components/Heading";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import LinkAsButton from "@library/components/LinkAsButton";
import { EditorRoute } from "@knowledge/routes/pageRoutes";

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const error = this.getError();

        return (
            <DocumentTitle title={error.message}>
                <VanillaHeader />
                <Container>
                    <PanelLayout device={this.props.device}>
                        <PanelLayout.MiddleTop>
                            <div className="errorPage">
                                <Heading depth={1} title={error.message} />
                                {error.description && <Paragraph>{error.description}</Paragraph>}
                                <div className="errorPage-cta">{error.actionItem}</div>
                            </div>
                        </PanelLayout.MiddleTop>
                    </PanelLayout>
                </Container>
            </DocumentTitle>
        );
    }

    private getError() {
        return {
            ...this.parseDefaultError(),
            ...(this.props.error || {}),
        };
    }

    private parseDefaultError(): IError {
        switch (this.props.defaultError) {
            case DefaultError.PERMISSION: {
                return {
                    message: t("No Permission"),
                    description: t("You don't have permission to view this resource."),
                    actionItem: this.renderSignin(),
                };
            }
            case DefaultError.NOT_FOUND: {
                return {
                    message: "Page not found",
                    description: t("The page you were looking for could not be found."),
                    actionItem: <LinkAsButton to={"/kb"}>{t("Home")}</LinkAsButton>,
                };
            }
            case DefaultError.NO_KNOWLEDGE_BASE: {
                return {
                    message: "There are no knowledge bases",
                    description: t("No knowledge bases could be found. Please create one to get started."),
                    actionItem: <LinkAsButton to={"/knowledge/settings/knowledge-bases"}>{t("Home")}</LinkAsButton>,
                };
            }
            case DefaultError.NO_ARTICLES: {
                const { knowledgeBaseID } = this.props;
                return {
                    message: "This knowledge base does not have any articles.",
                    description: "",
                    actionItem: knowledgeBaseID ? (
                        <EditorRoute.Link data={{ knowledgeBaseID }}>{t("New Article")}</EditorRoute.Link>
                    ) : null,
                };
            }
            case DefaultError.NOT_FOUND: {
                return {
                    message: "There are no knowledge bases",
                    description: t("No knowledge bases could be found. Please create one to get started."),
                    actionItem: <LinkAsButton to={"/knowledge/settings/knowledge-bases"}>{t("Home")}</LinkAsButton>,
                };
            }
            case DefaultError.GENERIC:
            default: {
                return {
                    message: t("There was an error"),
                    description: t("Please try again later."),
                    actionItem: <LinkAsButton to={"/kb"}>{t("Home")}</LinkAsButton>,
                };
            }
        }
    }

    private renderSignin() {
        const { currentUser } = this.props;
        if (
            currentUser.status === LoadStatus.SUCCESS &&
            currentUser.data &&
            currentUser.data.userID === UsersModel.GUEST_ID
        ) {
            return (
                <LinkAsButton to={formatUrl(`/entry/signin?Target=${encodeURIComponent(window.location.href)}`)}>
                    {t("Sign In")}
                </LinkAsButton>
            );
        } else {
            return null;
        }
    }
}

interface IProps extends IDeviceProps, IInjectableUserState {
    defaultError?: DefaultError;
    error?: Partial<IError>;
    knowledgeBaseID?: number;
}

interface IError {
    message: string;
    description: string;
    actionItem: React.ReactNode;
}

export enum DefaultError {
    GENERIC = "generic",
    PERMISSION = "permission",
    NOT_FOUND = "notfound",
    NO_KNOWLEDGE_BASE = "noknowledgebase",
    NO_ARTICLES = "noarticles",
}

const withCurrentUser = connect(UsersModel.mapStateToProps);

export default withCurrentUser(withDevice(ErrorPage));
