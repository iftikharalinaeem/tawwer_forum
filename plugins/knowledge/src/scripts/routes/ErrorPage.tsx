/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
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
import Permission from "@library/users/Permission";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { searchError } from "@library/components/icons";
import { style } from "typestyle";
import { globalVariables } from "library/src/scripts/styles/globalStyleVars";

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const error = this.getError();

        return (
            <DocumentTitle title={error.message}>
                <VanillaHeader />
                <Container className="inheritHeight">
                    <div className="errorPage inheritHeight">
                        {error.icon}
                        <Heading depth={1} title={error.message} />
                        {error.description && <Paragraph>{error.description}</Paragraph>}
                        {error.actionItem && <div className="errorPage-cta">{error.actionItem}</div>}
                    </div>
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
        const globalVars = globalVariables();
        const errorIconClass = style({});

        switch (this.props.defaultError) {
            case DefaultError.PERMISSION: {
                const message = t("No Permission");
                return {
                    message,
                    description: t("You don't have permission to view this resource."),
                    actionItem: this.renderSignin(),
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.NOT_FOUND: {
                const message = t("Page not found");
                return {
                    message,
                    description: t("The page you were looking for could not be found."),
                    actionItem: <LinkAsButton to={"/kb"}>{t("Home")}</LinkAsButton>,
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.NO_KNOWLEDGE_BASE: {
                const message = t("There are no knowledge bases");
                return {
                    message,
                    description: t("No knowledge bases could be found. Please create one to get started."),
                    actionItem: (
                        <Permission permission="articles.add">
                            <LinkAsButton to={"/knowledge-settings/knowledge-bases"}>
                                {t("New Knowledge Base")}
                            </LinkAsButton>
                        </Permission>
                    ),
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.NO_ARTICLES: {
                const { knowledgeBaseID, knowledgeCategoryID } = this.props;
                const message = t("This knowledge base does not have any articles.");
                return {
                    message,
                    description: "",
                    actionItem: knowledgeBaseID ? (
                        <Permission permission="articles.add">
                            <EditorRoute.Link
                                className={ButtonBaseClass.STANDARD}
                                data={{ knowledgeBaseID, knowledgeCategoryID }}
                            >
                                {t("New Article")}
                            </EditorRoute.Link>
                        </Permission>
                    ) : null,
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.GENERIC:
            default: {
                const message = t("There was an error");
                return {
                    message,
                    description: t("Please try again later."),
                    actionItem: <LinkAsButton to={"/kb"}>{t("Home")}</LinkAsButton>,
                    icon: searchError(message, errorIconClass),
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
    knowledgeCategoryID?: number;
}

interface IError {
    message: string;
    description: string;
    actionItem: React.ReactNode;
    icon?: ReactNode;
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
