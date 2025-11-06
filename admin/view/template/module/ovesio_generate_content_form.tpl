<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="generate_content_form" onsubmit="ovesio.generateContentFormSave(event)">
  <div class="ov-reset">
    <!-- Status Switch -->
    <div class="ov-form-group">
      <label class="ov-form-label"><?php echo $entry_status; ?>:</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_content_status" value="" type="hidden">
          <?php if ($generate_content_status): ?>
          <input name="generate_content_status" type="checkbox" id="content_status" value="1" checked>
          <?php else: ?>
          <input name="generate_content_status" type="checkbox" id="content_status" value="1">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <!-- Generate Description For -->
    <div class="ov-form-group ov-form-group-vertical">
      <label class="ov-form-label"><?php echo $text_generate_content_for; ?>:</label>
      <div class="ov-form-field">
        <div class="ov-well">
          <div style="display: flex; flex-direction: column; gap: 0.5rem;">
            <?php foreach ($resources_list as $key => $label): ?>
            <label class="ov-checkbox-label">
            <input name="generate_content_for[<?php echo $key; ?>]" value="" type="hidden">
              <?php if (!empty($generate_content_for[$key])): ?>
              <input name="generate_content_for[<?php echo $key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="generate_content_for[<?php echo $key; ?>]" value="1" type="checkbox">
              <?php endif; ?>
              <span><?php echo $label; ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Min Description Length -->
    <div class="ov-form-group ov-form-group-vertical">
      <label class="ov-form-label"><?php echo $text_generate_content_min_length; ?>:</label>
      <div class="ov-form-field">
        <div class="ov-d-flex ov-gap-2">
          <?php foreach ($resources_list as $key => $label): ?>
          <div style="flex: 1;">
            <label class="ov-text-sm"><?php echo $label; ?> (<?php echo $text_characters; ?>):</label>
            <input name="generate_content_when_description_length[<?php echo $key; ?>]" type="number" class="ov-form-control" value="<?php echo $generate_content_when_description_length[$key]; ?>" min="0">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Include Out of Stock -->
    <div class="ov-form-group">
      <label class="ov-form-label">Include produse out of stock (stoc 0):</label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_content_include_stock_0" value="" type="hidden">
          <?php if ($generate_content_include_stock_0): ?>
          <input name="generate_content_include_stock_0" value="1" type="checkbox" checked>
          <?php else: ?>
          <input name="generate_content_include_stock_0" value="1" type="checkbox">
          <?php endif; ?>
          <span class="ov-switch-slider"></span>
        </label>
      </div>
    </div>

    <!-- Regenerate on Changes -->
    <div class="ov-form-group">
      <label class="ov-form-label">
        <?php echo $text_generate_content_live_update; ?>:<br>
        <small class="ov-text-sm ov-helper-text"><?php echo $text_generate_content_live_update_helper; ?></small>
      </label>
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label class="ov-form-switch">
          <input name="generate_content_live_update" value="" type="hidden">
          <?php if ($generate_content_live_update): ?>
          <input name="generate_content_live_update" type="checkbox" checked>
          <?php else: ?>
          <input name="generate_content_live_update" type="checkbox">
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
              <input name="generate_content_include_disabled[<?php echo $key; ?>]" value="" type="hidden">
              <?php if (!empty($generate_content_include_disabled[$key])): ?>
              <input name="generate_content_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox" checked>
              <?php else: ?>
              <input name="generate_content_include_disabled[<?php echo $key; ?>]" value="1" type="checkbox">
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
        <select name="generate_content_workflow" class="ov-form-control">
          <?php foreach ($workflows_list as $workflow): ?>
          <?php if ($generate_content_workflow && $workflow['id'] == $generate_content_workflow['id']): ?>
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