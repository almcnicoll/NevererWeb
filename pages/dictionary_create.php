<?php
    use Dictionaries\Tome;
    use Dictionaries\TomeEntry;
    use Security\User;
    use Basic\db;
    use Dictionaries\Subscription;

    // Deal with any messages from request
    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    // If form submitted, handle creation
    if (isset($_REQUEST['action'])) {
        if ($_REQUEST['action'] == 'formsubmitted') {
            // Create tome in db
            $tome = new Tome();
            $tome->name = $_REQUEST['name'];
            $tome->source = null;
            $tome->source_type = Tome::TYPE_LOCAL;
            $tome->source_format = Tome::FORMAT_SQL;
            $tome->readable = $_REQUEST['readable'];
            $tome->writeable = $_REQUEST['writeable'];
            $tome->user_id = $user->id;
            $tome->subscribed_by_default = 0;
            $tome->last_updated = date('Y-m-d H:i:s');
            $tome->save();
            // Subscribe to it
            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->tome_id = $tome->id;
            $subscription->subscribed = 1;
            $subscription->save();

            header("Location: {$config['root_path']}/dictionary/index");
        }
    }
    ?>
<!-- MAIN PAGE -->
<h2>Create a dictionary</h2>
<?php
    // Display error messages
    if (count($error_messages)>0) {
        foreach($error_messages as $error_message) {
    ?>
<div class="row">
    <div class="span12 alert alert-danger"><?= $error_message ?></div>
</div>
<?php
        }
    }

?>
<form method="POST">
    <div class="row">
        <div class="mb-3 col-4">
            <label for="name" class="form-label">What's your dictionary called?</label>
            <input type="text" class="form-control border-secondary-subtle" name="name" id="name" placeholder=""
                value="<?= $user->display_name ?>'s dictionary" aria-describedby="name-help">
            <div class="form-text" id="name-help">This will appear as a name, and can be changed later.</div>
        </div>
        <div class="mb-3 col-4">
            <label for="readable" class="form-label">Who should be able to read from this dictionary?</label>
            <select class="form-control border-secondary-subtle" name="readable" id="readable"
                aria-describedby="readable-help">
                <option value='1' selected>Only me</option>
                <option value='2'>Everyone</option>
            </select>
            <div class="form-text" id="writeable-help">Think about whether you want others to be able to use your
                dictionary as a word list.</div>
        </div>
        <div class="mb-3 col-4">
            <label for="writeable" class="form-label">Who should be able to write to this dictionary?</label>
            <select class="form-control border-secondary-subtle" name="writeable" id="writeable"
                aria-describedby="writeable-help">
                <option value='0'>Nobody</option>
                <option value='1' selected>Only me</option>
                <option value='2'>Everyone</option>
            </select>
            <div class="form-text" id="writeable-help">Think about whether you want yourself or others to be able to
                make changes to this dictionary.</div>
        </div>
    </div>
    <div class="row">
        <div class="mb-3">
            <input type="hidden" value="formsubmitted" name="action" id="action">
            <button type="submit" class="btn btn-primary">Create!</button>
        </div>
    </div>
</form>