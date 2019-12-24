/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ManageKnowledgeBasesPage } from "@knowledge/knowledge-settings/ManageKnowledgeBasesPage";
import ManageThemingPage from "@themingapi/theming-ui-settings/ManageThemingPage";
import ThemeEditorPage from "@themingapi/theme/ThemeEditorPage";
import { addComponent } from "@library/utility/componentRegistry";
import { registerReducer } from "@library/redux/reducerRegistry";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { combineReducers } from "redux";

addComponent("theming-ui-manage", ManageThemingPage);
addComponent("theme-editor", ThemeEditorPage);
