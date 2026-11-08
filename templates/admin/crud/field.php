<?php
/**
 * Một trường của biểu mẫu dùng chung.
 *
 * @var array{name: string, label: string, type: string, rules?: string, hint?: string, options?: array<string|int, string>} $field
 * @var mixed $value
 * @var array<string, string> $errors
 * @var ?\App\Models\MediaFile $media   tệp đang chọn (trường media/file)
 */
$name = $field['name'];
$required = str_contains($field['rules'] ?? '', 'required');
$invalid = isset($errors[$name]) ? ' form-field--invalid' : '';
$inputTypes = ['text' => 'text', 'date' => 'date', 'time' => 'time', 'email' => 'email', 'tel' => 'tel', 'url' => 'url'];
?>
<?php if ($field['type'] === 'media' || $field['type'] === 'file'): ?>
    <?= partial('admin/partials/media-field', [
        'name' => $name,
        'label' => $field['label'],
        'media' => $media,
        'kind' => $field['type'] === 'media' ? 'image' : '',
    ]) ?>
    <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => $name]) ?>
<?php elseif ($field['type'] === 'pages'): ?>
    <fieldset class="form-field<?= $invalid ?>">
        <legend class="form-field__label"><?= e($field['label']) ?></legend>
        <?php if (($field['options'] ?? []) === []): ?>
            <p class="form-field__hint"><?= e($field['empty'] ?? '') ?></p>
        <?php endif; ?>
        <?php foreach ($field['options'] ?? [] as $optionValue => $optionLabel): ?>
            <label class="checkbox">
                <input type="checkbox" name="<?= e($name) ?>[]" value="<?= e($optionValue) ?>" <?= in_array((int) $optionValue, (array) $value, true) ? 'checked' : '' ?>>
                <?= e($optionLabel) ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
<?php else: ?>
    <div class="form-field<?= $invalid ?>">
        <label for="<?= e($name) ?>"><?= e($field['label']) ?><?= $required ? ' *' : '' ?></label>
        <?php if ($field['type'] === 'select'): ?>
            <select id="<?= e($name) ?>" name="<?= e($name) ?>">
                <?php foreach ($field['options'] ?? [] as $optionValue => $optionLabel): ?>
                    <option value="<?= e($optionValue) ?>" <?= (string) $value === (string) $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
                <?php endforeach; ?>
            </select>
        <?php elseif ($field['type'] === 'textarea'): ?>
            <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" rows="4"><?= e($value) ?></textarea>
        <?php elseif ($field['type'] === 'rich'): ?>
            <textarea id="<?= e($name) ?>" name="<?= e($name) ?>" data-rich-editor="compact"
                      data-library-label="<?= e(t('pages.insert_from_library')) ?>"><?= e($value) ?></textarea>
        <?php else: ?>
            <input type="<?= e($inputTypes[$field['type']] ?? 'text') ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"<?= $required ? ' required' : '' ?>>
        <?php endif; ?>
        <?php if (($field['hint'] ?? '') !== ''): ?>
            <p class="form-field__hint"><?= e($field['hint']) ?></p>
        <?php endif; ?>
        <?= partial('admin/partials/field-error', ['errors' => $errors, 'field' => $name]) ?>
    </div>
<?php endif; ?>
