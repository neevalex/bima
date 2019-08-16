<p>
    <?php
    foreach (LRM_Settings::get()->get_sections() as $section_key => $section) :
        printf('<label><input type="checkbox" name="lrm_import_sections[]" value="%1$s" checked="checked"> %2$s</label><br/>', $section_key, $section->name());
    endforeach;
    ?>
    <textarea rows="3" id="lrm-setting-messages-registration-terms" class="large-text"></textarea>
</p>