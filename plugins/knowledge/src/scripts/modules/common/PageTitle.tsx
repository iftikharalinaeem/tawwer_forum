/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@library/components/PageHeading";
import { IArticle } from "@knowledge/@types/api";
import Sentence, { ISentence } from "@library/components/Sentence";

interface IProps {
    title: string;
    menu?: JSX.Element;
    meta?: ISentence;
    backUrl?: string | null;
}

export default class PageTitle extends React.Component<IProps> {
    public render() {
        return (
            <PanelWidget>
                <PageHeading backUrl={this.props.backUrl} title={this.props.title} menu={this.props.menu} />
                {this.props.meta && (
                    <div className="pageMetas metas">
                        <Sentence {...this.props.meta} recursiveChildClass="meta pageMetas-meta" />
                    </div>
                )}
            </PanelWidget>
        );
    }
}
