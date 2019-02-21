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
import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import apiv2 from "@library/apiv2";
import ButtonLoader from "@library/components/ButtonLoader";
import { buttonClasses } from "@library/styles/buttonStyles";
import classNames from "classnames";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { checkCompact } from "@library/components/icons";
import { flexHelper } from "@library/styles/styleHelpers";
import { important } from "csx";

export function ArticleReactions(props: IProps) {
    const { isNoSubmitting, isYesSubmitting } = props;
    const classes = reactionStyles();

    const helpfulReactions = props.reactions.find(article => article.reactionType === ArticleReactionType.HELPFUL);

    if (!helpfulReactions) {
        return null;
    }

    // Build the text view.
    const { yes, total, userReaction } = helpfulReactions;

    const resultText =
        total < 1 ? (
            t("Be the first one to vote!")
        ) : (
            <Translate source="<0 /> out of <1 /> people found this helpful" c0={yes} c1={total} />
        );

    const title = userReaction !== null ? t("Thanks for your feedback!") : t("Was this article helpful?");

    // Build the buttons for the view.
    const buttonStyles = buttonClasses();
    const isDisabled = isYesSubmitting || isNoSubmitting || userReaction !== null;

    const noClasses = classNames(
        {
            [buttonStyles.primary]: isNoSubmitting || userReaction === "no",
            [classes.checkedButton]: userReaction === "no",
        },
        classes.votingButton,
    );
    let noContent: React.ReactNode = t("No");
    if (userReaction === "no") {
        noContent = <span className={classes.checkedButtonContent}>{checkCompact()}</span>;
    }
    if (isNoSubmitting) {
        noContent = <ButtonLoader />;
    }
    const noButton = (
        <Button disabled={isDisabled} className={noClasses} onClick={props.onNoClick}>
            {noContent}
        </Button>
    );

    let yesContent: React.ReactNode = t("Yes");
    if (userReaction === "yes") {
        yesContent = <span className={classes.checkedButtonContent}>{checkCompact()}</span>;
    }
    if (isYesSubmitting) {
        yesContent = <ButtonLoader />;
    }
    const yesClasses = classNames(
        {
            [buttonStyles.primary]: isYesSubmitting || userReaction === "yes",
            [classes.checkedButton]: userReaction === "yes",
        },
        classes.votingButton,
    );
    const yesButton = (
        <Button disabled={isDisabled} className={yesClasses} onClick={props.onYesClick}>
            {yesContent}
        </Button>
    );

    return (
        <section className={classes.frame}>
            <Heading title={title} className={classes.title} />
            <div className={classes.votingButtons}>
                {noButton}
                {yesButton}
            </div>
            <SignInLink isSignedIn={props.isSignedIn} />
            <Paragraph className={classes.resultText}>{resultText}</Paragraph>
        </section>
    );
}

/**
 * Small subcomponent for rendering a sign in prompt for signed out users.
 */
function SignInLink(props: { isSignedIn: boolean }) {
    if (props.isSignedIn) {
        return null;
    }

    const classes = reactionStyles();

    // Signin Link
    const signInLinkCallback = content => (
        <SmartLink to={`/entry/signin?target=${window.location.pathname}`} className={classes.link}>
            {content}
        </SmartLink>
    );

    return (
        <Paragraph className={classes.signInText}>
            <Translate source={"You need to <0>Sign In</0> to vote on this article"} c0={signInLinkCallback} />
        </Paragraph>
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
        textAlign: "center",
        margin: 8,
    });

    const checkedButtonContent = style({
        ...flexHelper().middle(),
        width: "100%",
    });

    const checkedButton = style({
        opacity: important(1) as any,
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

    return {
        link,
        title,
        frame,
        votingButton,
        checkedButtonContent,
        checkedButton,
        votingButtons,
        resultText,
        signInText,
    };
}

interface IOwnProps {
    articleID: number;
    reactions: IArticleReaction[];
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IUsersStoreState & IStoreState, ownProps: IOwnProps) {
    const currentUser = state.users.current;
    const reactionLoadable = state.knowledge.articlePage.reactionLoadable;
    let isSignedIn = false;
    if (currentUser.data && currentUser.data.userID !== UsersModel.GUEST_ID) {
        isSignedIn = true;
    }
    return {
        isSignedIn,
        isYesSubmitting:
            reactionLoadable.status === LoadStatus.LOADING &&
            reactionLoadable.data &&
            reactionLoadable.data.reaction === "yes",
        isNoSubmitting:
            reactionLoadable.status === LoadStatus.LOADING &&
            reactionLoadable.data &&
            reactionLoadable.data.reaction === "no",
    };
}

function mapDispatchToProps(dispatch, ownProps: IOwnProps) {
    const { articleID } = ownProps;
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
                helpful: "no",
            }),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(ArticleReactions);
