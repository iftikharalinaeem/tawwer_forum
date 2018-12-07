/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
                    <div className="container">
                        <DocumentTitle title={pageTitle}>
                            <Heading depth={1} renderAsDepth={2} className="pageSubTitle" title={pageTitle} />
                        </DocumentTitle>
                        <NavigationManager knowledgeBaseID={1} />
                    </div>
                </FullKnowledgeModal>
            </>
        );
    }
}
