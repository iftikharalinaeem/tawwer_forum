/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IArticleRevisionFragment } from "@knowledge/@types/api";
import { Link } from "react-router-dom";

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
export default class RevisionsList extends React.Component<IProps> {
    public render() {
        const history = this.props.children.map(child => {
            const { name, photoUrl, dateLastActive } = child.insertUser;

            return (
                <li className="articleRevisionHistory-item panelList-item">
                    <Link to={child.url} className="articleRevisionHistory-link panelList-link">
                        <img
                            src={photoUrl}
                            className="sr-only articleRevisionHistory-photo"
                            alt={`${t("User: ")}${name}`}
                        />
                        <span className="articleRevisionHistory-userName">{name}</span>
                    </Link>
                </li>
            );
        });

        return (
            <div className="articleRevisionHistory related">
                <Heading className="panelList-title" title={t("Revisions")} depth={1} renderAsDepth={2} />
                <ul className="articleRevisionHistory-items panelList-items">{history}</ul>
            </div>
        );
    }
}
