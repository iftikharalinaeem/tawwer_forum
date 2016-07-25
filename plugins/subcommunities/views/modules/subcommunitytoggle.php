<nav class="subcommunity-toggle nav">
    <?php
    foreach ($this->data('Subcommunities') as $Folder => $Row) {
        echo anchor(
            $Row[$this->LabelField],
            SubCommunityModel::getAlternativeUrl($Row),
            $this->data('Current.Folder') === $Folder ? 'active nav-link' : 'nav-link'
        ).' ';
    }
    ?>
</nav>
