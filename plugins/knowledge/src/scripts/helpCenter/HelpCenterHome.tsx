/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import HelpCenterNavigation from "@knowledge/helpCenter/components/HelpCenterNavigation";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NavigationActions from "@knowledge/navigation/state/NavigationActions";
import NavigationSelector from "@knowledge/navigation/state/NavigationSelector";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";
import { EditorRoute, HomeRoute } from "@knowledge/routes/pageRoutes";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { ILinkListData, ILoadable, LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Permission from "@library/features/users/Permission";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import WidgetContainer from "@library/layout/components/WidgetContainer";
import ScreenReaderContent from "@library/layout/ScreenReaderContent";
import Loader from "@library/loaders/Loader";
import DocumentTitle from "@library/routing/DocumentTitle";
import LinkAsButton from "@library/routing/LinkAsButton";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import TitleBar from "@library/headers/TitleBar";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { ComposeIcon } from "@library/icons/common";
import { FallbackBackUrlSetter } from "@library/routing/links/BackRoutingProvider";
import { DefaultKbError } from "@knowledge/modules/common/KbErrorMessages";
import Banner from "@library/banner/Banner";

/**
 * Component representing the the full home page of a help center.
 */
export class HelpCenterHome extends React.Component<IProps> {
    public render() {
        const { knowledgeBase, status, data, rootCategoryUrl } = this.props;
        const { bannerImage, bannerContentImage, knowledgeBaseID, rootCategoryID, description } = knowledgeBase;

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(status)) {
            return <Loader />;
        }

        if (status === LoadStatus.ERROR) {
            return <KbErrorPage error={this.props.error} />;
        }

        if (knowledgeBase.countArticles === 0) {
            return (
                <KbErrorPage
                    defaultError={DefaultKbError.NO_ARTICLES}
                    knowledgeBaseID={knowledgeBaseID}
                    knowledgeCategoryID={knowledgeBase.rootCategoryID}
                />
            );
        }

        const bannerAction = (
            <Permission permission="articles.add">
                <LinkAsButton
                    to={EditorRoute.url({ knowledgeBaseID, knowledgeCategoryID: rootCategoryID })}
                    onMouseOver={EditorRoute.preload}
                    className={classNames("searchBar-actionButton")}
                    baseClass={ButtonTypes.ICON}
                    title={t("Compose")}
                >
                    <ComposeIcon />
                </LinkAsButton>
            </Permission>
        );

        return (
            <>
                <AnalyticsData data={knowledgeBase} uniqueKey={knowledgeBaseID} />
                <FallbackBackUrlSetter url={HomeRoute.url(undefined)} />
                <Banner
                    action={bannerAction}
                    backgroundImage={bannerImage}
                    contentImage={bannerContentImage}
                    title={knowledgeBase.name}
                    description={description}
                />
                <Container>
                    <DocumentTitle title={knowledgeBase.name}>
                        <TitleBar useMobileBackButton={!this.props.isOnlyKb} />
                    </DocumentTitle>

                    {/*For Screen Readers / SEO*/}
                    <ScreenReaderContent>
                        <h1>{knowledgeBase.name}</h1>
                    </ScreenReaderContent>
                    <WidgetContainer>
                        <HelpCenterNavigation data={data!} rootCategoryUrl={rootCategoryUrl} />
                    </WidgetContainer>
                </Container>
            </>
        );
    }

    /**
     * Request our data if it hasn't been provided yet.
     */
    public componentDidMount() {
        const { status, requestData } = this.props;
        if (status === LoadStatus.PENDING) {
            void requestData();
        }
    }
}

interface IOwnProps {
    knowledgeBase: IKnowledgeBase;
    isOnlyKb?: boolean;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IKnowledgeAppStoreState, ownProps: IOwnProps) {
    const { knowledgeBaseID } = ownProps.knowledgeBase;
    const knowledgeState = state.knowledge.navigation;
    const loadStatus = knowledgeState.fetchStatusesByKbID[knowledgeBaseID] || LoadStatus.PENDING;

    let data: ILinkListData | undefined;
    let rootCategoryUrl: string | undefined;
    if (loadStatus === LoadStatus.SUCCESS) {
        data = NavigationSelector.selectHelpCenterHome(knowledgeState.navigationItems, ownProps.knowledgeBase);
        const rootCategory = NavigationSelector.selectCategory(
            ownProps.knowledgeBase.rootCategoryID,
            knowledgeState.navigationItems,
        );
        if (rootCategory) {
            rootCategoryUrl = rootCategory.url;
        }
    }

    const loadable: ILoadable<ILinkListData> = {
        status: loadStatus,
        data,
    };

    return { ...loadable, rootCategoryUrl };
}

function mapDispatchToProps(dispatch: any, ownProps: IOwnProps) {
    const navActions = new NavigationActions(dispatch, apiv2);
    return {
        requestData: () => navActions.getNavigationFlat(ownProps.knowledgeBase.knowledgeBaseID),
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(HelpCenterHome);
