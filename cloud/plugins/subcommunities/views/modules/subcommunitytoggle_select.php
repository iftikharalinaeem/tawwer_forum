<select class="subcommunity-toggle js-nav-dropdown">
    <?php foreach ($this->data('Subcommunities') as $Folder => $Row): ?>
        <option value="<?php echo htmlspecialchars(SubcommunityModel::getAlternativeUrl($Row)); ?>" <?php if ($this->data('Current.Folder') === $Folder) { echo 'selected'; } ?>><?php echo htmlspecialchars($Row[$this->LabelField]); ?></option>
    <?php endforeach; ?>
</select>
