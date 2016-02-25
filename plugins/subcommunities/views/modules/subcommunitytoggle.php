<nav class="subcommunity-toggle">
    <?php
    foreach ($this->data('Subcommunities') as $Folder => $Row) {
        echo anchor($Row[$this->LabelField], $Row['Url'], $this->data('Current.Folder') === $Folder ? 'active' : '').' ';
    }
    ?>
</nav>
