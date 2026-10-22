<?php
use Crosswords\Template;

$templates = Template::getVisibleTo($user->id);
$template_counts = Template::countUserCrosswordsByChecksum($user->id);
$template_can_delete = true;
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h2 class="mb-0">Grid templates</h2>
    <div class="d-grid gap-2 d-md-flex">
        <a class="btn btn-primary" href="<?= $config['root_path'] ?>/crossword/create">+ New crossword</a>
    </div>
</div>
<p class="text-body-secondary">
    A template is a grid layout - size, symmetry and the position of every clue - with no letters filled in.
    To make your own, open a crossword and choose <span class="bi bi-bookmark-plus-fill" aria-hidden="true"></span> <em>Save as template</em>;
    your templates are private to you.
</p>
<?php include 'inc/template_table.php'; ?>

<div class="modal fade" id="templateDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete template</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this template? Crosswords you've already made from it are not affected.</p>
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button class="btn btn-danger" id="templateDeleteConfirm">Yes</button>
                    <button class="btn btn-success" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
