<?php
/**
 * Returns the HTML for a telescoping category selector for analytics. Uses the Gdn_Form object to render the dropdown.
 *
 * @param Gdn_Form $form
 * @param array $data The data to pass to the dropdown function.
 * @param array $attr The attributes for the dropdown.
 * @param string $heading The heading for the dropdown.
 * @param int $depth The depth of the category filter.
 * @return string An HTML-formatted string ready for echo-ing.
 */
function getCategoryFilterHTML($form, $data, $attr = [], $heading = '', $depth = 1) {
    $heading = $heading ? $heading : t('Category');
    $attr['class'] = isset($attr['class']) ? $attr['class'].' js-category-telescope' : 'js-category-telescope';
    $attr['data-depth'] = $depth;

    $depth = ($depth < 10) ? '0'.$depth : $depth;

    return '
    <div class="js-category-telescope-wrapper">
        <div class="control-panel-subheading">'.$heading.'</div>'
        .$form->dropDown('cat'.$depth, $data, $attr).'
    </div>';
}