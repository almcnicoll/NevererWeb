<?php
    use Crosswords\Crossword;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    // If form submitted, handle creation
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            // Create crossword in db
            $crossword = new Crossword();
            $crossword->title = $_REQUEST['title'];
            $crossword->rows = $_REQUEST['rows'];
            $crossword->cols = $_REQUEST['cols'];
            $crossword->rotational_symmetry_order = $_REQUEST['rotational_symmetry_order'];
            $crossword->user_id = $user->id;
            $crossword->save();

            header("Location: {$config['root_path']}/crossword/edit/{$crossword->id}");
        }
    }
?>
<script type="text/javascript">
    // Default to display name being destination
    function verifyRowsCols() {
        if($('#rotational_symmetry_order').val().toString() == '4') {
            // Ensure cols and rows are equal
            switch($(document.activeElement).attr('id')) {
                case 'rows':
                    // Match cols to rows
                    $('#cols').val($('#rows').val());
                    break;
                case 'cols':
                    // Match rows to cols
                    $('#rowss').val($('#cols').val());
                    break;
                default:
                    // Match cols to rows
                    $('#cols').val($('#rows').val());
                    break;
            }
        }
    }

    $(document).ready(
        function() {
            $('#rotational_symmetry_order').on('change',verifyRowsCols);
            $('#rows,#cols').on('change',verifyRowsCols);

            $('#title').focus();
        }
    );
</script>

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