/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import NavigationManager from "@knowledge/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/navigation/NavigationManagerMenu";
import ErrorPage from "@knowledge/pages/ErrorPage";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import Loader from "@library/loaders/Loader";
import Heading from "@library/layout/Heading";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import NavigationManagerErrors from "@knowledge/navigation/subcomponents/NavigationManagerErrors";
import classNames from "classnames";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import Permission from "@library/features/users/Permission";
import { hot } from "react-hot-loader";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { getCurrentLocale } from "@vanilla/i18n";
import Message from "@library/messages/Message";
import { AttachmentErrorIcon } from "@library/icons/fileTypes";
import { messagesClasses } from "@library/messages/messageStyles";
import { LocaleDisplayer } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import { string } from "prop-types";
import Container from "@library/layout/components/Container";

interface IState {
    warningFlag: boolean;
}

class OrganizeCategoriesPage extends React.Component<IProps, IState> {
    private titleID = uniqueIDFromPrefix("organizeCategoriesTitle");
    public state: IState = {
        warningFlag: true,
    };

    public setWarning = () => {
        this.setState({
            warningFlag: false,
        });
    };

    public render() {
        const { knowledgeBase } = this.props;
        const sourceLocale = knowledgeBase.data ? knowledgeBase.data.sourceLocale : null;
        const showWarning = sourceLocale !== getCurrentLocale() ? true : false;
        const pageTitle = t("Organize Categories");
        const classesNavigationManager = navigationManagerClasses();
        const classesMessages = messagesClasses();

        const categoriesWarning = showWarning && this.state.warningFlag && (
            <Message
                isContained={true}
                // className={classNames(classesNavigationManager.containerWidth)}
                contents={
                    <div className={classesMessages.iconWrap}>
                        <AttachmentErrorIcon className={classesMessages.errorIcon} />
                        <div>
                            <Translate
                                source="You are viewing categories in the source locale: <0/>. Make sure you name new categories using the source locale."
                                c0={
                                    <>
                                        <LocaleDisplayer localeContent={sourceLocale || " "} />
                                    </>
                                }
                            />
                        </div>
                    </div>
                }
                onConfirm={this.setWarning}
                stringContents={t(
                    "You are viewing categories in the source locale. Make sure you name new categories using the source locale.",
                )}
            />
        );

        if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(knowledgeBase.status)) {
            return <Loader />;
        }

        if (knowledgeBase.status === LoadStatus.ERROR || !knowledgeBase.data) {
            return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
        }

        return (
            <Permission permission="articles.add" fallback={<ErrorPage defaultError={DefaultError.PERMISSION} />}>
                <AnalyticsData uniqueKey="organizeCategoriesPage" />
                <FullKnowledgeModal scrollable={true} titleID={this.titleID}>
                    <NavigationManagerMenu />
                    <div className={classNames(classesNavigationManager.containerWidth)}>{categoriesWarning}</div>

                    <div className={classNames(classesNavigationManager.container)}>
                        <NavigationManagerErrors knowledgeBaseID={knowledgeBase.data.knowledgeBaseID} />

                        <DocumentTitle title={pageTitle}>
                            <Heading
                                id={this.titleID}
                                depth={1}
                                renderAsDepth={2}
                                className={classNames(
                                    "pageSubTitle",
                                    "navigationManager-header",
                                    classesNavigationManager.header,
                                )}
                                title={pageTitle}
                            />
                        </DocumentTitle>
                        <NavigationManager knowledgeBase={knowledgeBase.data} />
                    </div>
                </FullKnowledgeModal>
            </Permission>
        );
    }

    public componentDidMount() {
        if (this.props.knowledgeBase.status === LoadStatus.PENDING) {
            this.props.requestData();
        }
    }
}

interface IOwnProps {
    match: match<{
        id: string;
        page?: string;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const kbID = parseInt(ownProps.match.params.id, 10);

    const knowledgeBase = {
        ...knowledgeBasesByID,
        data: knowledgeBasesByID.data ? knowledgeBasesByID.data[kbID] : undefined,
    };

    const hasError = !!state.knowledge.navigation.currentError;

    return {
        knowledgeBase,
        hasError,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);

    return {
        requestData: () => kbActions.getAll(),
    };
}

export default hot(module)(
    connect(
        mapStateToProps,
        mapDispatchToProps,
    )(OrganizeCategoriesPage),
);
