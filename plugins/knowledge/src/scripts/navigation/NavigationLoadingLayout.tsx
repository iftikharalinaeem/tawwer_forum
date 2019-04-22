/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Navigation from "@knowledge/navigation/Navigation";
import { t } from "@library/utility/appUtils";
import Loader from "@library/loaders/Loader";
import VanillaHeader from "@library/headers/VanillaHeader";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import React from "react";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { useNavHistory } from "@knowledge/navigation/NavHistoryContext";
import { KbViewType } from "@knowledge/knowledge-bases/KnowledgeBaseModel";

interface IProps {
    activeRecord: IActiveRecord;
}

/**
 * A loading layout that includes navigation items.
 *
 * This is useful when your content loads separately from the navigation.
 * - Provides navigation & breadcrumbs.
 * - Note that hard coded kbID is temporary
 */
export default function NavigationLoadingLayout(props: IProps) {
    const { lastKB } = useNavHistory();
    const navigation = lastKB && (
        <Navigation
            activeRecord={props.activeRecord}
            collapsible={lastKB.viewType === KbViewType.GUIDE}
            kbID={lastKB.knowledgeBaseID}
        />
    );

    return (
        <Container>
            <VanillaHeader title={t("Loading")} mobileDropDownContent={navigation} />
            <PanelLayout
                leftBottom={<PanelWidget>{navigation}</PanelWidget>}
                breadcrumbs={
                    <PanelWidget>
                        <Breadcrumbs forceDisplay={true}>{[]}</Breadcrumbs>
                    </PanelWidget>
                }
                middleBottom={
                    <PanelWidget>
                        <Loader />
                    </PanelWidget>
                }
                rightBottom={<></>}
            />
        </Container>
    );
}
