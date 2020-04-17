/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import EditorForm from "@knowledge/modules/editor/EditorForm";
import EditorPageActions from "@knowledge/modules/editor/EditorPageActions";
import EditorPageModel from "@knowledge/modules/editor/EditorPageModel";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import Message from "@library/messages/Message";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import QueryString from "@library/routing/QueryString";
import { t } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React, { Dispatch } from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router-dom";
import Container from "@library/layout/components/Container";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { HomeRoute } from "@knowledge/routes/pageRoutes";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import qs from "querystring";

/**
 * Page for editing an article.
 */
export class EditorPage extends React.Component<IProps> {
    private id = uniqueIDFromPrefix("editorPage");

    public render() {
        const { article } = this.props;

        const queryKbID = qs.parse(window.location.search).knowledgeBaseID ?? undefined;
        const kbID = typeof queryKbID === "string" ? parseInt(queryKbID) : undefined;

        const permissionProps =
            kbID != null
                ? {
                      resourceID: kbID,
                      resourceType: "knowledgeBase",
                  }
                : {};

        return (
            <Modal
                isVisible={true}
                titleID={this.titleID}
                size={ModalSizes.FULL_SCREEN}
                exitHandler={this.navigateToBacklink}
                elementToFocusOnExit={document.activeElement as HTMLElement}
            >
                <AnalyticsData uniqueKey={this.id} />
                <FallbackBackUrlSetter url={article?.data?.url ?? HomeRoute.url(undefined)} />
                {this.renderErrorMessage()}
                <Permission
                    {...permissionProps}
                    permission="articles.add"
                    fallback={<KbErrorPage defaultError={DefaultKbError.PERMISSION} />}
                >
                    {this.renderQueryString()}
                    <EditorForm titleID={this.titleID} />
                </Permission>
            </Modal>
        );
    }

    /**
     * Initial setup for the page.
     *
     * Either creates an article and changes to the edit page, or gets an existing article.
     */
    public componentDidMount() {
        const { article, requestData } = this.props;

        if (article.status === LoadStatus.PENDING) {
            requestData();
        }
    }

    /**
     * Cleanup the page contents.
     */
    public componentWillUnmount() {
        this.props.reset();
    }

    /**
     * Render the current error message, if there is one.
     */
    private renderErrorMessage(): React.ReactNode {
        if (this.props.currentError === null) {
            return null;
        }

        return (
            <Container>
                <Message
                    confirmText={t("Dismiss")}
                    onConfirm={this.props.resetError}
                    contents={this.props.currentError.message}
                    stringContents={this.props.currentError.message}
                    isFixed={true}
                />
            </Container>
        );
    }
    /**
     * Render a query string component from the form value.
     */
    private renderQueryString(): React.ReactNode {
        const { draft, saveDraft, submit } = this.props;

        // Only push a new draft query string if publish is not loading, because submitting/saving a draft can cause a race condition where we redirect to the article URL & where we "redirect" to the draft URL.
        if (submit.status !== LoadStatus.LOADING && saveDraft.status === LoadStatus.SUCCESS && draft.data) {
            return <QueryString value={{ draftID: draft.data.draftID }} syncOnFirstMount={true} />;
        } else {
            return null;
        }
    }

    private get titleID() {
        return this.id + "-title";
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.props.history.length > 1) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };
}

interface IOwnProps
    extends RouteComponentProps<{
        id?: string;
    }> {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState) {
    const { article, saveDraft, submit, currentError } = EditorPageModel.getStateSlice(state);

    return {
        article,
        saveDraft,
        submit,
        draft: EditorPageModel.selectDraft(state),
        currentError,
    };
}

function mapDispatchToProps(dispatch: Dispatch<any>, ownProps: IOwnProps) {
    const actions = new EditorPageActions(dispatch, apiv2);
    const requestData = () => {
        if (ownProps.match.params.id === undefined) {
            void actions.initializeAddPage(ownProps.history);
        } else {
            void actions.initializeEditPage(ownProps.history, parseInt(ownProps.match.params.id, 10));
        }
    };
    return {
        requestData,
        reset: actions.reset,
        resetError: actions.resetError,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(EditorPage);
