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
<!-- link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/basic.min.css" integrity="sha384-1w/YQcU9srPurd2IGPvCrzcmS0+1z+T1AYE0CBGgnMRuRgbt0CsGQcgNOjUQPLan" crossorigin="anonymous" -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css"
    integrity="sha384-hKRH7ZmTc4+t+iae668SDRfEsjc7HT3VrEMKuSwiDUK4pNQXd/v9BPVpIa0OLlp7" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"
    integrity="sha384-PwiT+fWTPpIySx6DrH1FKraKo+LvVpOClsjx0TSdMYTKi7BR1hR149f4VHLUUnfA" crossorigin="anonymous">
</script>
<script type="text/javascript" src="~ROOT~/js/crossword_import.js"></script>

<!-- MAIN PAGE -->
<h2>Import a crossword</h2>
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
    <div class="mb-3 col-md-2">
        &nbsp;
    </div>
    <div class="mb-3 col-md-8">
        <form action="~ROOT~/crossword/*/import?domain=ajax" method="POST" class='dropzone' id='import-dropzone'>
            <div id='import-dropzone' class='d-inline-block w-100 min-vh-50 vh-50'>
                <!-- h2 class='text-center align-middle'>Drop file here or click to upload</h2 -->
            </div>
        </form>
    </div>
    <div class="mb-3 col-md-2">
    </div>