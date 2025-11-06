<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="translate_form" onsubmit="ovesio.translateFormSave(event)">
  <div class="ov-reset">
    <!-- Status Switch -->
    <div class="ov-form-group">
      <label class="ov-form-label">Status:</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="translate_status" value="" type="hidden">
          <?php if ($translate_status): ?>
          <input name="translate_status" type="checkbox" id="translation_status" value="1" checked>
          <?php else: ?>
          <input name="translate_status" type="checkbox" id="translation_status" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
        <span class="ov-switch-label"><?php echo $text_activated; ?></span>
      </div>
    </div>

    <!-- Include Out of Stock -->
    <div class="ov-form-group">
      <label class="ov-form-label">Include produse out of stock (stoc 0):</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="translate_include_stock_0" value="" type="hidden">
          <?php if ($translate_include_stock_0): ?>
          <input name="translate_include_stock_0" value="1" type="checkbox" id="translation_include_out_of_stock" checked>
          <?php else: ?>
          <input name="translate_include_stock_0" value="1" type="checkbox" id="translation_include_out_of_stock">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
        <span class="ov-switch-label"><?php echo $text_included; ?></span>
      </div>
    </div>

    <!-- Include Disabled Resources -->
    <div class="ov-form-group ov-form-group-vertical">
      <label class="ov-form-label">Include resursele dezactivate:</label>
      <div class="ov-form-field">
        <div class="ov-well">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($resources_list as $key => $label): ?>
            <label class="ov-checkbox-label">
              <input name="translate_include_disabled[<?php echo $key; ?>]" value="" type="hidden">
              <?php if (!empty($translate_include_disabled[$key])): ?>
              <input name="translate_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="translate_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox">
              <?php endif; ?>
              <span><?php echo $label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Workflow -->
    <div class="ov-form-group">
      <label class="ov-form-label"><?php echo $text_workflow_used; ?></label>
      <div class="ov-form-field">
        <select name="translate_workflow" class="ov-form-control">
          <?php foreach ($workflows_list as $workflow): ?>
          <?php if ($translate_workflow && $workflow['id'] == $translate_workflow['id']): ?>
          <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>" selected><?php echo $workflow['name']; ?></option>
          <?php else: ?>
          <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>"><?php echo $workflow['name']; ?></option>
          <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- Translated Fields -->
    <div class="ov-form-group ov-form-group-vertical ov-mb-0">
      <label class="ov-form-label"><?php echo $text_translated_fields; ?>:</label>

      <?php foreach ($translate_fields_schema as $resource_type => $fields): ?>
      <div class="ov-form-field">
        <h5><?php echo $resources_list[$resource_type]; ?>:</h5>
        <div class="ov-well" style="margin-top: 0; margin-bottom: 1rem;">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($fields as $field_key => $field_label): ?>
            <label class="ov-checkbox-label">
              <input name="translate_fields[<?php echo $resource_type; ?>][<?php echo $field_key; ?>]" value="" type="hidden">
              <?php if (!empty($translate_fields[$resource_type][$field_key])): ?>
              <input name="translate_fields[<?php echo $resource_type; ?>][<?php echo $field_key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="translate_fields[<?php echo $resource_type; ?>][<?php echo $field_key; ?>]" value="1" type="checkbox">
              <?php endif; ?>
              <span><?php echo $field_label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Language Translation Table -->
      <h4 class="ov-table-section-title"><?php echo $text_language_translation; ?></h4>
    <table class="ov-table">
      <thead class="ov-table-header">
        <tr>
          <th><?php echo $text_system_language; ?></th>
          <th><?php echo $text_ovesio_language; ?></th>
          <th><?php echo $text_translate; ?></th>
          <th><?php echo $text_translate_from; ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($system_languages as $language_id => $language): ?>
        <tr>
          <td><?php echo $language['name']; ?></td>
          <td>
            <select name="language_settings[<?php echo $language_id; ?>][code]" class="ov-form-control">
              <option disabled><?php echo $text_please_select_matching_language; ?></option>
              <?php foreach ($ovesio_languages as $code => $ov_lang): ?>
              <?php if ($language_settings[$language_id]['code'] == $code): ?>
              <option value="<?php echo $code; ?>" selected><?php echo $ov_lang['name']; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $ov_lang['name']; ?></option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </td>
          <td>
            <label class="ov-form-switch" style="margin-bottom: 0;">
              <input name="language_settings[<?php echo $language_id; ?>][translate]" value="" type="hidden">
              <?php if (!empty($language_settings[$language_id]['translate'])): ?>
              <input name="language_settings[<?php echo $language_id; ?>][translate]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="language_settings[<?php echo $language_id; ?>][translate]" value="1" type="checkbox">
              <?php endif; ?>
              <span class="ov-switch-slider"></span>
            </label>
          </td>
          <td>
            <select name="language_settings[<?php echo $language_id; ?>][translate_from]" class="ov-form-control">
              <option value="" disabled><?php echo $text_select_source_language; ?></option>
              <?php foreach ($ovesio_languages as $code => $ov_lang): ?>
              <?php if ($language_settings[$language_id]['translate_from'] == $code): ?>
              <option value="<?php echo $code; ?>" selected><?php echo $ov_lang['name']; ?></option>
              <?php else: ?>
              <option value="<?php echo $code; ?>"><?php echo $ov_lang['name']; ?></option>
              <?php endif; ?>
              <?php endforeach; ?>
            </select>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</form>