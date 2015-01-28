<nav class="subcommunity-toggle">
    <?php
    foreach ($this->Data('Subcommunities') as $Folder => $Row) {
        echo Anchor($Row[$this->LabelField], $Row['Url'], $this->Data('Current.Folder') === $Folder ? 'active' : '').' ';
    }
    ?>
</nav>
