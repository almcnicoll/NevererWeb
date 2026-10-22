<?php
/**
 * Shared list of grid templates, used by the template index and the crossword-create page.
 * Expects: $templates (Template[]), $template_counts (checksum => number of the user's crosswords), $config
 * Optional: $template_can_delete (bool) - show a delete button on the user's own templates
 */
$template_can_delete = $template_can_delete ?? false;
?>
<div class="table-responsive">
    <table class="table table-sm table-striped table-hover align-middle template-table">
        <thead>
            <tr>
                <th>Grid</th>
                <th>Template</th>
                <th class="text-nowrap">Size</th>
                <th>Symmetry</th>
                <th title="How many of your crosswords have exactly this grid, whether or not you made them from the template">Your crosswords</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $t): $matches = $template_counts[$t->getChecksum()] ?? 0; ?>
            <tr>
                <td><?= $t->getPreviewSvg() ?></td>
                <th scope="row">
                    <?= htmlspecialchars($t->name) ?>
                    <?php if ($t->user_id === null): ?><span class="badge text-bg-secondary">Built-in</span><?php endif; ?>
                </th>
                <td class="text-nowrap"><?= $t->cols ?> &times; <?= $t->rows ?></td>
                <td><?= $t->getSymmetryLabel() ?></td>
                <td><?= $matches ?></td>
                <td>
                    <div class="d-grid gap-2 d-md-flex">
                        <a class="btn btn-sm btn-primary" href="<?= $config['root_path'] ?>/crossword/create?template=<?= $t->id ?>">Use</a>
                        <?php if ($template_can_delete && $t->user_id !== null): ?>
                        <a href="#" class="btn btn-sm btn-danger" title="Delete template" data-bs-toggle="modal"
                            data-bs-target="#templateDeleteModal" data-id="<?= $t->id ?>"><span class="bi bi-trash3"></span></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
