/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import DocumentTitle from "@library/components/DocumentTitle";
import NavigationManager from "@knowledge/modules/navigation/NavigationManager";
import { RouteComponentProps } from "react-router";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import EditorMenu from "plugins/knowledge/src/scripts/modules/editor/components/EditorMenu";
import { NavigationManagerMenu } from "plugins/knowledge/src/scripts/modules/navigation/NavigationManagerMenu";

interface IProps extends RouteComponentProps<{}> {}

export default class OrganizeCategoriesPage extends React.Component<IProps> {
    private titleID = uniqueIDFromPrefix("organzieCategoriesTitle");

    public render() {
        return (
            <FullKnowledgeModal titleID={this.titleID}>
                <div className="container">
                    <NavigationManagerMenu />
                    <DocumentTitle title={t("Organize Categories")} />
                    <NavigationManager />
                </div>
            </FullKnowledgeModal>
        );
    }
}
