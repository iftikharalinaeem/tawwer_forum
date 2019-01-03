/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import NavigationManager from "@knowledge/modules/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/modules/navigation/NavigationManagerMenu";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DocumentTitle from "@library/components/DocumentTitle";
import Heading from "@library/components/Heading";
import React from "react";

interface IProps {}

export default class OrganizeCategoriesPage extends React.Component<IProps> {
    private titleID = uniqueIDFromPrefix("organizeCategoriesTitle");

    public render() {
        const pageTitle = t("Navigation Manager");
        return (
            <>
                <FullKnowledgeModal titleID={this.titleID}>
                    <NavigationManagerMenu />
                    <div className="modal-scroll inheritHeight">
                        <div className="container inheritHeight">
                            <div className="navigationManager-container inheritHeight">
                                <DocumentTitle title={pageTitle}>
                                    <Heading
                                        id={this.titleID}
                                        depth={1}
                                        renderAsDepth={2}
                                        className="pageSubTitle navigationManager-header"
                                        title={pageTitle}
                                    />
                                </DocumentTitle>
                                <div className="inheritHeight">
                                    <NavigationManager knowledgeBaseID={1} rootNavigationItemID="knowledgeCategory1" />
                                </div>
                            </div>
                        </div>
                    </div>
                </FullKnowledgeModal>
            </>
        );
    }
}
