/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ArticleReactionType, IArticleReaction } from "@knowledge/@types/api";
import ArticleActions from "@knowledge/modules/article/ArticleActions";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import ButtonLoader from "@library/components/ButtonLoader";
import Button from "@library/components/forms/Button";
import Heading from "@library/components/Heading";
import { checkCompact } from "@library/components/icons";
import SmartLink from "@library/components/navigation/SmartLink";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";
import { buttonClasses, ButtonTypes } from "@library/styles/buttonStyles";
import UsersModel, { IUsersStoreState } from "@library/users/UsersModel";
import classNames from "classnames";
import React from "react";
import { connect } from "react-redux";
import { reactionClasses } from "@knowledge/modules/article/components/articleReactionStyles";

export function ArticleReactions(props: IProps) {
    const { isNoSubmitting, isYesSubmitting } = props;
    const classes = reactionClasses();

    const helpfulReactions = props.reactions.find(article => article.reactionType === ArticleReactionType.HELPFUL);

    // If we don't have reaction data bail out early.
    if (!helpfulReactions) {
        return null;
    }

    // Build the text for the view.
    const { yes, total, userReaction } = helpfulReactions;

    const resultText =
        total < 1 ? (
            t("Be the first one to vote!")
        ) : (
            <Translate source="<0 /> out of <1 /> people found this helpful" c0={yes} c1={total} />
        );

    const title = userReaction !== null ? t("Thanks for your feedback!") : t("Was this article helpful?");

    const buttonsDisabled = isYesSubmitting || isNoSubmitting || userReaction !== null || !props.isSignedIn;

    return (
        <section className={classes.frame}>
            <Heading title={title} className={classes.title} />
            <div className={classes.votingButtons}>
                <ReactionButton
                    reactionData={helpfulReactions}
                    isSignedIn={props.isSignedIn}
                    title={t("Yes")}
                    reactionValue="yes"
                    isSubmitting={!!isYesSubmitting}
                    isDisabled={buttonsDisabled}
                    onClick={props.onYesClick}
                />
                <ReactionButton
                    reactionData={helpfulReactions}
                    isSignedIn={props.isSignedIn}
                    title={t("No")}
                    reactionValue="no"
                    isSubmitting={!!isNoSubmitting}
                    isDisabled={buttonsDisabled}
                    onClick={props.onNoClick}
                />
            </div>
            <SignInLink isSignedIn={props.isSignedIn} />
            <Paragraph className={classes.resultText}>{resultText}</Paragraph>
        </section>
    );
}

/**
 * Small subcomponent for rendering a single reaction button.
 */
function ReactionButton(props: {
    reactionData: IArticleReaction;
    isSignedIn: boolean;
    title: string;
    reactionValue: string;
    isSubmitting: boolean;
    isDisabled: boolean;
    onClick: () => void;
}) {
    const { reactionValue, reactionData, title, isSubmitting, isDisabled, onClick } = props;
    const { userReaction } = reactionData;
    const styles = reactionClasses();

    // Content can be either a checkbox, a loader, or some text.
    let content: React.ReactNode = title;
    if (userReaction === reactionValue) {
        content = <span className={styles.checkedButtonContent}>{checkCompact()}</span>;
    }
    if (isSubmitting) {
        content = <ButtonLoader />;
    }
    const classes = classNames(
        {
            [styles.checkedButton]: userReaction === reactionValue,
        },
        styles.votingButton,
    );
    return (
        <Button baseClass={ButtonTypes.PRIMARY} disabled={isDisabled} className={classes} onClick={onClick}>
            {content}
        </Button>
    );
}

/**
 * Small subcomponent for rendering a sign in prompt for signed out users.
 */
function SignInLink(props: { isSignedIn: boolean }) {
    if (props.isSignedIn) {
        return null;
    }

    const classes = reactionClasses();

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

interface IOwnProps {
    articleID: number;
    reactions: IArticleReaction[];
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

/**
 * Map in state from the redux store.
 */
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

/**
 * Map in some bound actions.
 */
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
