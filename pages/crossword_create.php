<?php
    use Crosswords\Crossword, Crosswords\Template;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    // If creating from a template, check that it exists and that the user may use it
    $template = null;
    if (isset($_REQUEST['template'])) {
        $template = Template::getVisibleById((int)$_REQUEST['template'], $user->id);
        if ($template === null) {
            $error_messages[] = "That template doesn't exist.";
        }
    }

    // If form submitted, handle creation
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            // Create crossword in db
            if ($template !== null) {
                $crossword = $template->createCrossword($_REQUEST['title'], $user->id);
            } elseif (!isset($_REQUEST['template'])) {
                $crossword = new Crossword();
                $crossword->title = $_REQUEST['title'];
                $crossword->rows = $_REQUEST['rows'];
                $crossword->cols = $_REQUEST['cols'];
                $crossword->rotational_symmetry_order = $_REQUEST['rotational_symmetry_order'];
                $crossword->user_id = $user->id;
                $crossword->save();
            }

            if (isset($crossword)) {
                header("Location: {$config['root_path']}/crossword/edit/{$crossword->id}");
            }
        }
    }

    $template_counts = Template::countUserCrosswordsByChecksum($user->id);
?>
<script type="text/javascript" src="~ROOT/js/crossword_create.js"></script>

<!-- MAIN PAGE -->
<h2>Create a crossword</h2>
<?php
if (count($error_messages)>0) {
    foreach($error_messages as $error_message) {
?>
<div class="row">
    <div class="col-12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
    }
}
?>
<?php if ($template !== null): ?>
<div class="row">
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <?= $template->getPreviewSvg() ?>
            <div>
                <h5 class="card-title"><?= htmlspecialchars($template->name) ?></h5>
                <div><?= $template->cols ?> &times; <?= $template->rows ?>, symmetry: <?= $template->getSymmetryLabel() ?></div>
                <?php $matches = $template_counts[$template->getChecksum()] ?? 0; ?>
                <div class="text-body-secondary"><?= $matches ?> of your crosswords already <?= $matches == 1 ? "has" : "have" ?> this grid.</div>
                <a href="<?= $config['root_path'] ?>/crossword/create">Choose a different template or start from scratch</a>
            </div>
        </div>
    </div>
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">What's your crossword called?</label>
            <input type="text" class="form-control" name="title" id="title" placeholder="" aria-describedby="title-help">
            <div class="form-text" id="title-help">This will appear as a title, and can be changed later.</div>
        </div>
        <div class="mb-3">
            <input type="hidden" value="<?= $template->id ?>" name="template" id="template">
            <input type="hidden" value="formsubmitted" name="action" id="action">
            <button type="submit" class="btn btn-primary">Create!</button>
        </div>
    </form>
</div>
<?php else: ?>
<div class="row">
    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">What's your crossword called?</label>
            <input type="text" class="form-control" name="title" id="title" placeholder="" aria-describedby="title-help">
            <div class="form-text" id="title-help">This will appear as a title, and can be changed later.</div>
        </div>
        <div class="mb-3">
            <label for="rotational_symmetry_order" class="form-label">What symmetry should the crossword have?</label>
            <select class="form-control" name="rotational_symmetry_order" id="rotational_symmetry_order" aria-describedby="rotational_symmetry_order-help">
                <option value='1'>No symmetry</option>
                <option value='2' selected>2nd-order (180-degree) symmetry</option>
                <option value='4'>4th-order (90-degree) symmetry</option>
            </select>
            <div class="form-text" id="rotational_symmetry_order-help">Should the pattern of black and white squares be symmetrical? It may be hard to change this later without losing work.</div>
        </div>
        <div class="mb-3">
            <label for="rows" class="form-label">Number of rows</label>
            <input type="number" min="1" max="50" step="1" value="15" class="form-control" name="rows" id="rows" placeholder="" aria-describedby="rows-help">
            <div class="form-text" id="rows-help">How many rows should the grid have?</div>
        </div>
        <div class="mb-3">
            <label for="cols" class="form-label">Number of columns</label>
            <input type="number" min="1" max="50" step="1" value="15" class="form-control" name="cols" id="cols" placeholder="" aria-describedby="cols-help">
            <div class="form-text" id="cols-help">How many columns should the grid have?</div>
        </div>
        <div class="mb-3">
            <input type="hidden" value="formsubmitted" name="action" id="action">
            <button type="submit" class="btn btn-primary">Create!</button>
        </div>
    </form>
</div>

<h3>...or start from a template</h3>
<?php
    $templates = Template::getVisibleTo($user->id);
    $template_can_delete = false;
    include 'inc/template_table.php';
?>
<?php endif; ?>
