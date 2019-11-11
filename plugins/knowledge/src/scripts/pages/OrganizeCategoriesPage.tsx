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
import { useUniqueID } from "@library/utility/idUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import Loader from "@library/loaders/Loader";
import Heading from "@library/layout/Heading";
import React, { useEffect } from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import NavigationManagerErrors from "@knowledge/navigation/subcomponents/NavigationManagerErrors";
import classNames from "classnames";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import Permission from "@library/features/users/Permission";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import { DefaultError } from "@knowledge/modules/common/PageErrorMessage";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { OrganizeCategoriesTranslator } from "@knowledge/navigation/NavigationTranslator";

function OrganizeCategoriesPage(props: IProps) {
    const titleID = useUniqueID("organizeCategoriesTitle");
    const { knowledgeBase } = props;
    const pageTitle = t("Organize Categories");
    const classesNavigationManager = navigationManagerClasses();

    useEffect(() => {
        if (props.knowledgeBase.status === LoadStatus.PENDING) {
            props.requestData();
        }
    }, []);

    if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(knowledgeBase.status)) {
        return <Loader />;
    }

    if (knowledgeBase.status === LoadStatus.ERROR || !knowledgeBase.data) {
        return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
    }

    return (
        <Permission permission="articles.add" fallback={<ErrorPage defaultError={DefaultError.PERMISSION} />}>
            <AnalyticsData uniqueKey="organizeCategoriesPage" />
            <FullKnowledgeModal scrollable={true} titleID={titleID}>
                <NavigationManagerMenu />
                <div className={classNames(classesNavigationManager.container)}>
                    <NavigationManagerErrors knowledgeBaseID={knowledgeBase.data.knowledgeBaseID} />
                    <DocumentTitle title={pageTitle}>
                        <Heading
                            id={titleID}
                            depth={1}
                            renderAsDepth={2}
                            className={classNames(
                                "pageSubTitle",
                                "navigationManager-header",
                                classesNavigationManager.header,
                            )}
                            title={pageTitle}
                        >
                            {pageTitle}
                            {/* <OrganizeCategoriesTranslator kbID={props.kbID} /> */}
                        </Heading>
                    </DocumentTitle>
                    <NavigationManager knowledgeBase={knowledgeBase.data} />
                </div>
            </FullKnowledgeModal>
        </Permission>
    );
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
        kbID,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);

    return {
        requestData: () => kbActions.getAll(),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(OrganizeCategoriesPage);
