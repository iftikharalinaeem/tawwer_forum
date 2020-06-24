<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

/**
 * Writes attachments for Pega leads.
 *
 * @param array $attachment
 */
function writePegaLeadAttachment($attachment) {
    // Don't display anything for guest.
    if (!Gdn::session()->isValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
        return;
    }

   $leadName = Gdn_Format::text($attachment['FirstName'] . ' ' . $attachment['LastName']);
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
                  <h4 class="media-heading item-heading"><?php echo t('Lead') ?> · <a rel="nofollow"
                       href="<?php echo c('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo ucfirst($attachment['Source']); ?></a>
                     <div class="item-meta">
                        <span><?php echo Gdn_Format::date($attachment['DateInserted'], 'html') ?> <?php echo t('by'); ?>
                           <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                     </div>
                  </h4></div>
               <div class="item-body">

                  <dl class="dl-columns">
                     <dt><?php echo t('Name'); ?></dt>
                     <dd><a rel="nofollow" href="<?php echo c('Plugins.Pega.AuthenticationUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo $leadName; ?></a></dd>
                     <dt><?php echo t('Status'); ?></dt>
                     <dd><?php echo $attachment['Status']; ?></dd>
                     <dt><?php echo t('Last Updated'); ?></dt>
                     <dd><?php echo Gdn_Format::date($attachment['LastModifiedDate'], 'html') ?></dd>
                     <dt><?php echo t('Company'); ?></dt>
                     <dd><?php echo Gdn_Format::text($attachment['Company']); ?></dd>
                     <?php if (getValue('Title', $attachment)) { ?>
                        <dt><?php echo t('Title'); ?></dt>
                        <dd><?php echo Gdn_Format::text($attachment['Title']); ?></dd>
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
function writePegaCaseAttachment($attachment) {
    // Don't display anything for guest.
    if (!Gdn::session()->isValid()) {
        return;
    }

    // Only display to Staff
    if (!Gdn::session()->checkPermission('Garden.Staff.Allow')) {
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
                  <h4 class="media-heading item-heading"><?php echo t('Case') ?> · <a rel="nofollow" href="<?php echo c('Plugins.Pega.BaseUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo ucfirst($attachment['Source']); ?></a>
                     <div class="item-meta">
                        <span><?php echo Gdn_Format::date($attachment['DateInserted'], 'html') ?> <?php echo t('by'); ?>
                           <?php echo $attachment['InsertUser']['ProfileLink']; ?></span>
                     </div>
                  </h4></div>

               <div class="item-body">

                  <dl class="dl-columns">
                     <dt><?php echo t('Case Number'); ?></dt>
                     <dd><a rel="nofollow" href="<?php echo c('Plugins.Pega.BaseUrl'); ?>/<?php echo $attachment['SourceID']; ?>"><?php echo $attachment['CaseNumber']; ?></a></dd>
                     <dt><?php echo t('Status'); ?></dt>
                     <dd><?php echo $attachment['Status']; ?></dd>
                     <dt><?php echo t('Last Updated'); ?></dt>
                     <dd><?php echo Gdn_Format::date($attachment['LastModifiedDate'], 'html') ?></dd>
                  </dl>
               </div>

            </div>
         </div>
      </div>
   </div>
<?php
}
