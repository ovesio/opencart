<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="generate_seo_form" onsubmit="ovesio.generateSeoFormSave(event)">
  <div class="ov-reset">
    <!-- Status Switch -->
    <div class="ov-form-group">
      <label class="ov-form-label"><?php echo $entry_status; ?>:</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_seo_status" value="" type="hidden">
          <?php if ($generate_seo_status): ?>
          <input name="generate_seo_status" type="checkbox" id="meta_status" value="1" checked>
          <?php else: ?>
          <input name="generate_seo_status" type="checkbox" id="meta_status" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <div class="ov-form-group">
      <label class="ov-form-label"><?php echo $text_only_for_generated_content; ?>:</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_seo_only_for_action" value="" type="hidden">
          <?php if ($generate_seo_only_for_action): ?>
          <input name="generate_seo_only_for_action" type="checkbox" id="meta_status" value="1" checked>
          <?php else: ?>
          <input name="generate_seo_only_for_action" type="checkbox" id="meta_status" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <!-- Generate Meta Tags For -->
    <div class="ov-form-group ov-form-group-vertical">
      <label class="ov-form-label"><?php echo $text_generate_seo_for; ?>:</label>
      <div class="ov-form-field">
        <div class="ov-well">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($resources_list as $key => $label): ?>
            <label class="ov-checkbox-label">
            <input name="generate_seo_for[<?php echo $key; ?>]" value="" type="hidden">
              <?php if (!empty($generate_seo_for[$key])): ?>
              <input name="generate_seo_for[<?php echo $key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="generate_seo_for[<?php echo $key; ?>]" value="1" type="checkbox">
              <?php endif; ?>
              <span><?php echo $label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Include Out of Stock -->
    <div class="ov-form-group">
      <label class="ov-form-label">Include produse out of stock (stoc 0):</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_seo_include_stock_0" value="" type="hidden">
          <?php if ($generate_seo_include_stock_0): ?>
          <input name="generate_seo_include_stock_0" value="1" type="checkbox" id="meta_include_out_of_stock" checked>
          <?php else: ?>
          <input name="generate_seo_include_stock_0" value="1" type="checkbox" id="meta_include_out_of_stock">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <!-- Generate on Resource Update -->
    <div class="ov-form-group">
      <label class="ov-form-label">
        <?php echo $text_generate_seo_live_update; ?>:<br>
        <small class="ov-text-sm ov-helper-text"><?php echo $text_generate_seo_live_update_helper; ?></small>
      </label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_seo_live_update" value="" type="hidden">
          <?php if ($generate_seo_live_update): ?>
          <input name="generate_seo_live_update" type="checkbox" id="meta_regenerate_on_update" checked>
          <?php else: ?>
          <input name="generate_seo_live_update" type="checkbox" id="meta_regenerate_on_update">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <!-- Include Disabled Resources -->
    <div class="ov-form-group ov-form-group-vertical">
      <label class="ov-form-label"><?php echo $text_include_disabled_resources; ?>:</label>
      <div class="ov-form-field">
        <div class="ov-well">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($resources_list as $key => $label): ?>
            <label class="ov-checkbox-label">
              <input name="generate_seo_include_disabled[<?php echo $key; ?>]" value="" type="hidden">
              <?php if (!empty($generate_seo_include_disabled[$key])): ?>
              <input name="generate_seo_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="generate_seo_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox">
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
      <label class="ov-form-label"><?php echo $text_workflow_used; ?>:</label>
      <div class="ov-form-field">
        <select name="generate_seo_workflow" class="ov-form-control" id="meta_workflow_type">
          <?php foreach ($workflows_list as $workflow): ?>
          <?php if ( $generate_seo_workflow && $workflow['id'] == $generate_seo_workflow['id']): ?>
          <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>" selected><?php echo $workflow['name']; ?></option>
          <?php else: ?>
          <option value="<?php echo $workflow['id']; ?>@<?php echo htmlentities($workflow['name']); ?>"><?php echo $workflow['name']; ?></option>
          <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
</form>