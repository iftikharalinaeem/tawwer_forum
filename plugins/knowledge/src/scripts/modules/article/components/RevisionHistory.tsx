/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IArticleRevisionFragment } from "@knowledge/@types/api";
import { Link } from "react-router-dom";
import Sentence, { InlineTypes } from "@library/components/Sentence";

export interface IArticleRevisionWithUrl extends IArticleRevisionFragment {
    url: string;
}

interface IProps {
    children: IArticleRevisionWithUrl[];
}

/*
    articleRevisionID: 1,
    articleID: 1,
    status: "published",
    name: "Some Name",
    format: Format.RICH,
    bodyRendered: "Hello",
    locale: "en",
    insertUser: {
        userID: 1,
        name: "Joe",
        photoUrl: "#",
        dateLastActive: "2019-10-18",
    },
    dateInserted: "2019-10-19",
    url: revisionUrl,
 */

/**
 * Implements the Article Revision History component
 */
export default class RevisionHistory extends React.Component<IProps> {
    public render() {
        const history = this.props.children.map(child => {
            const { name, photoUrl, dateLastActive } = child.insertUser;

            const dummyDate = {
                children: "2019-10-19",
                type: InlineTypes.DATETIME,
            };

            return (
                <li className="articleRevisionHistory-item">
                    <Link to={child.url} className="articleRevisionHistory-link">
                        <img
                            src={photoUrl}
                            className="sr-only articleRevisionHistory-photo"
                            alt={`${t("User: ")}${name}`}
                        />
                        <span className="articleRevisionHistory-userName">{name}</span>
                        <Sentence {...dummyDate} />
                    </Link>
                </li>
            );
        });

        return (
            <div className="articleRevisionHistory">
                <Heading title={t("Revisions")} className="" depth={1} />
                <ul className="articleRevisionHistory-items">{history}</ul>
            </div>
        );
    }
}
