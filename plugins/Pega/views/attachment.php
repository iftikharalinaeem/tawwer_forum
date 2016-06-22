<?php
/**
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Pega leads.
 *
 * @param array $attachment
 */
function WritePegaLeadAttachment($attachment) {
    // Don't display anything for guest.
    if (!Gdn::Session()->IsValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
        return;
    }

   $leadName = Gdn_Format::Text($attachment['FirstName'] . ' ' . $attachment['LastName']);
   ?>
   <div class="item-attachment">
      <div class="alert">
         <div class="media item">
            <div class="pull-left">
               <div class="media-object">
                  <i class="icon icon-user"></i>
               </div>
            </div>
            <div class="media-body">

               <div class="item-header">
                  <h4 class="media-heading item-heading"><?php echo T('Lead') ?> · <a rel="nofollow"
                       href="<?php echo C('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo ucfirst($attachment['Source']); ?></a>
                     <div class="item-meta">
                        <span><?php echo Gdn_Format::Date($attachment['DateInserted'], 'html') ?> <?php echo T('by'); ?>
                           <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                     </div>
                  </h4></div>
               <div class="item-body">

                  <dl class="dl-columns">
                     <dt><?php echo T('Name'); ?></dt>
                     <dd><a rel="nofollow" href="<?php echo C('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo $leadName; ?></a></dd>
                     <dt><?php echo T('Status'); ?></dt>
                     <dd><?php echo $attachment['Status']; ?></dd>
                     <dt><?php echo T('Last Updated'); ?></dt>
                     <dd><?php echo Gdn_Format::Date($attachment['LastModifiedDate'], 'html') ?></dd>
                     <dt><?php echo T('Company'); ?></dt>
                     <dd><?php echo Gdn_Format::Text($attachment['Company']); ?></dd>
                     <?php if (GetValue('Title', $attachment)) { ?>
                        <dt><?php echo T('Title'); ?></dt>
                        <dd><?php echo Gdn_Format::Text($attachment['Title']); ?></dd>
                     <?php } ?>

                  </dl>
               </div>

            </div>
         </div>
      </div>
   </div>
<?php
}

/**
 * Writes attachments for Pega cases.
 * @param array $attachment
 */
function WritePegaCaseAttachment($attachment) {
    // Don't display anything for guest.
    if (!Gdn::Session()->IsValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::Session()->CheckPermission('Garden.Staff.Allow')) {
        return;
    }

    ?>
   <div class="item-attachment">
      <div class="alert">
         <div class="media item">
            <div class="pull-left">
               <div class="media-object">
                  <i class="icon icon-case"></i>
               </div>
            </div>
            <div class="media-body">

               <div class="item-header">
                  <h4 class="media-heading item-heading"><?php echo T('Case') ?> · <a rel="nofollow" href="<?php echo C('Plugins.Pega.BaseUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo ucfirst($attachment['Source']); ?></a>
                     <div class="item-meta">
                        <span><?php echo Gdn_Format::Date($attachment['DateInserted'], 'html') ?> <?php echo T('by'); ?>
                           <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                     </div>
                  </h4></div>

               <div class="item-body">

                  <dl class="dl-columns">
                     <dt><?php echo T('Case Number'); ?></dt>
                     <dd><a rel="nofollow" href="<?php echo C('Plugins.Pega.BaseUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo $attachment['CaseNumber']; ?></a></dd>
                     <dt><?php echo T('Status'); ?></dt>
                     <dd><?php echo $attachment['Status']; ?></dd>
                     <dt><?php echo T('Last Updated'); ?></dt>
                     <dd><?php echo Gdn_Format::Date($attachment['LastModifiedDate'], 'html') ?></dd>
                  </dl>
               </div>

            </div>
         </div>
      </div>
   </div>
<?php
}
