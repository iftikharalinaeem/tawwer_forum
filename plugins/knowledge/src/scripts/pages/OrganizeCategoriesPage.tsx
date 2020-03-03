/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import NavigationManager from "@knowledge/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/navigation/NavigationManagerMenu";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import { useUniqueID } from "@library/utility/idUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import Loader from "@library/loaders/Loader";
import Heading from "@library/layout/Heading";
import React, { useEffect, useState } from "react";
import { connect } from "react-redux";
import { match } from "react-router";
import NavigationManagerErrors from "@knowledge/navigation/subcomponents/NavigationManagerErrors";
import classNames from "classnames";
import { navigationManagerClasses } from "@knowledge/navigation/navigationManagerStyles";
import Permission from "@library/features/users/Permission";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import OrganizeCategoriesTranslator from "@knowledge/navigation/NavigationTranslator";
import Message from "@library/messages/Message";
import { messagesClasses } from "@library/messages/messageStyles";
import { LocaleDisplayer, useLocaleInfo } from "@vanilla/i18n";
import Translate from "@library/content/Translate";
import { ErrorIcon } from "@library/icons/common";
import { ActionBar } from "@vanilla/library/src/scripts/headers/ActionBar";
import { useFallbackBackUrl } from "@vanilla/library/src/scripts/routing/links/BackRoutingProvider";
import { KnowledgeBaseRoute } from "@knowledge/routes/pageRoutes";

function OrganizeCategoriesPage(props: IProps) {
    const titleID = useUniqueID("organizeCategoriesTitle");
    const { knowledgeBase } = props;
    const pageTitle = t("Organize Categories");

    const { currentLocale } = useLocaleInfo();
    const classesNavigationManager = navigationManagerClasses();
    const classesMessages = messagesClasses();
    const sourceLocale = knowledgeBase.data ? knowledgeBase.data.sourceLocale : null;
    const isNonSourceLocale = knowledgeBase.data && knowledgeBase.data.sourceLocale !== currentLocale;
    const [warningFlag, setWarning] = useState(isNonSourceLocale);

    const categoriesWarning = warningFlag && (
        <Message
            isFixed={true}
            icon={<ErrorIcon />}
            contents={
                <Translate
                    source="Certain actions are disabled because they are only avalaible in the source locale: <0/>"
                    c0={<LocaleDisplayer localeContent={sourceLocale || " "} />}
                />
            }
            onConfirm={() => {
                setWarning(false);
            }}
            stringContents={t("Certain actions are disabled because they are only avalaible in the source locale.")}
        />
    );
    useEffect(() => {
        if (props.knowledgeBase.status === LoadStatus.PENDING) {
            props.requestData();
        }
    }, []);

    useFallbackBackUrl(knowledgeBase.data ? KnowledgeBaseRoute.url(knowledgeBase.data) : "/kb");

    if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(knowledgeBase.status)) {
        return <Loader />;
    }

    if (knowledgeBase.status === LoadStatus.ERROR || !knowledgeBase.data) {
        return <KbErrorPage defaultError={DefaultKbError.NOT_FOUND} />;
    }
    return (
        <Permission permission="articles.add" fallback={<KbErrorPage defaultError={DefaultKbError.PERMISSION} />}>
            <AnalyticsData uniqueKey="organizeCategoriesPage" />
            <FullKnowledgeModal scrollable={true} titleID={titleID}>
                <ActionBar backTitle={t("Back")} />
                {categoriesWarning}
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
                            <OrganizeCategoriesTranslator
                                kbID={props.kbID}
                                sourceLocale={sourceLocale || ""}
                                activeLocale={currentLocale || ""}
                            />
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

export default connect(mapStateToProps, mapDispatchToProps)(OrganizeCategoriesPage);
