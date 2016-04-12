<nav class="subcommunity-toggle nav">
    <?php
    foreach ($this->data('Subcommunities') as $Folder => $Row) {
        echo anchor($Row[$this->LabelField], $Row['Url'], $this->data('Current.Folder') === $Folder ? 'active nav-link' : 'nav-link').' ';
    }
    ?>
</nav>
