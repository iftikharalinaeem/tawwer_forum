/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import SmartLink from "@library/components/navigation/SmartLink";
import Paragraph from "@library/components/Paragraph";
import classNames from "classnames";
import Translate from "@library/components/translation/Translate";

interface IProps {
    icon: string;
    iconAltText?: string; // If you want alternative alt text, title is passed in
    title: string;
    description: string;
    url: string;
    className?: string;
    headingLevel?: 2 | 3 | 4 | 5 | 6;
}

/**
 * Render a knowledge base tile
 */
export default class KnowledgeBaseItem extends React.Component<IProps> {
    public static defaultProps = {
        iconAltText: 'Icon for "<0/>"',
        headingLevel: 3,
    };
    public render() {
        const { icon, title, description, url, className, iconAltText, headingLevel } = this.props;
        const alt = `${<Translate source={iconAltText} c0={title} />}`;
        const H = `${headingLevel}`;

        return (
            <div className={classNames("kbItem", className)}>
                <SmartLink to={url}>
                    {icon && (
                        <div className="kbItem-iconFrame">
                            <img src={icon} alt={alt} />
                        </div>
                    )}
                    <H className="kbItem-title">{title}</H>
                    {description && <Paragraph>{description}</Paragraph>}
                </SmartLink>
            </div>
        );
    }
}
