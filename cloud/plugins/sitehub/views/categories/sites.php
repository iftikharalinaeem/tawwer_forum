<?php if (!defined('APPLICATION')) return; ?>
<h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>

<ul class="DataList DataList-Sites">
    <?php
    foreach ($this->data('Sites') as $site) {
        ?>
        <li class="Item Item-Site">
            <?php
            echo anchor(htmlspecialchars($site['Name']), $site['Url'], 'Link-Site')
            ?>
        </li>
        <?php
    }
    ?>
</ul>