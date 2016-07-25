<select class="subcommunity-toggle js-nav-dropdown">
    <?php foreach ($this->data('Subcommunities') as $Folder => $Row): ?>
        <option value="<?php echo SubcommunityModel::getAlternativeUrl($Row); ?>" <?php if ($this->data('Current.Folder') === $Folder) { echo 'selected'; } ?>><?php echo $Row[$this->LabelField]; ?></option>
    <?php endforeach; ?>
</select>
