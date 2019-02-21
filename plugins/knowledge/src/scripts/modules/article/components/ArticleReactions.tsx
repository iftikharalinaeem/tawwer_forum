/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import SmartLink from "@library/components/navigation/SmartLink";
import { connect } from "react-redux";
import UsersModel, { IUsersStoreState } from "@library/users/UsersModel";
import Heading from "@library/components/Heading";
import Paragraph from "@library/components/Paragraph";
import { globalVariables } from "@library/styles/globalStyleVars";
import { style } from "typestyle";
import Button from "@library/components/forms/Button";
import { IArticle, ArticleReactionType } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import apiv2 from "@library/apiv2";

function ArticleReactions(props: IProps) {
    const classes = reactionStyles();

    const signInLinkCallback = content => (
        <SmartLink to={`/entry/signin?target=${window.location.pathname}`} className={classes.link}>
            {content}
        </SmartLink>
    );

    const helpfulReactions = props.article.reactions.find(
        article => article.reactionType === ArticleReactionType.HELPFUL,
    );

    if (!helpfulReactions) {
        return null;
    }

    const { yes, total, userReacted } = helpfulReactions;

    return (
        <section className={classes.frame}>
            <Heading title={t("Was this article helpful?")} className={classes.title} />
            <div className={classes.votingButtons}>
                <Button className={classes.votingButton} onClick={props.onNoClick}>
                    {t("No")}
                </Button>
                <Button className={classes.votingButton} onClick={props.onYesClick}>
                    {t("Yes")}
                </Button>
            </div>
            {!props.user ||
                (props.user.userID === UsersModel.GUEST_ID && (
                    <Paragraph className={classes.signInText}>
                        <Translate
                            source={"You need to <0>Sign In</0> to vote on this article"}
                            c0={signInLinkCallback}
                        />
                    </Paragraph>
                ))}
            <Paragraph className={classes.resultText}>
                <Translate source="<0 /> out of <1 /> people found this helpful" c0={yes} c1={total} />
            </Paragraph>
        </section>
    );
}

function reactionStyles(theme?: object) {
    const vars = globalVariables(theme);

    const frame = style({
        paddingTop: vars.baseUnit * 2,
        display: "flex",
        flexDirection: "column",
        alignItems: "center",
    });

    const title = style({
        fontSize: vars.fonts.size.large,
    });

    const votingButton = style({
        margin: 8,
    });

    const votingButtons = style({
        padding: 8,
    });

    const resultText = style({
        fontSize: vars.meta.fontSize,
        color: vars.meta.color.toString(),
    });

    const signInText = style({
        fontSize: vars.fonts.size.large,
    });

    const link = style({
        color: vars.links.color.toString(),
        fontWeight: vars.fonts.weights.bold,
    });

    return { link, title, frame, votingButton, votingButtons, resultText, signInText };
}

interface IOwnProps {
    article: IArticle;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IUsersStoreState, ownProps: IOwnProps) {
    return {
        user: state.users.current.data,
    };
}

function mapDispatchToProps(dispatch, ownProps: IOwnProps) {
    const { articleID } = ownProps.article;
    const articleActions = new ArticleActions(dispatch, apiv2);

    return {
        onYesClick: () =>
            articleActions.reactHelpful({
                articleID,
                helpful: "yes",
            }),
        onNoClick: () =>
            articleActions.reactHelpful({
                articleID,
                helpful: "yes",
            }),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(ArticleReactions);
