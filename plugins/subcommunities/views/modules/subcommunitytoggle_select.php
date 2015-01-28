<select class="subcommunity-toggle js-nav-dropdown">
    <?php foreach ($this->Data('Subcommunities') as $Folder => $Row): ?>
        <option value="<?php echo $Row['Url']; ?>" <?php if ($this->Data('Current.Folder') === $Folder) echo 'selected' ?>><?php echo $Row[$this->LabelField]; ?></option>
    <?php endforeach; ?>
</select>
