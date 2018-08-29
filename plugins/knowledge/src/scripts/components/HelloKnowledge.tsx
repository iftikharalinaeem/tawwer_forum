/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import React from "react";
import { t } from "@dashboard/application";
import { IStoreState, IArticlePageState } from "@knowledge/@types/state";
import { connect } from "react-redux";

interface IProps {
    articlePageState: IArticlePageState;
}

export class HelloKnowledge extends React.Component<IProps> {
    public render() {
        return <div>{t("Hello Knowledge")}</div>;
    }
}

function mapStateToProps(state: IStoreState) {
    return {
        articlePageState: state.knowledge.articlePage,
    };
}

const withRedux = connect(mapStateToProps);

export default withRedux(HelloKnowledge);
