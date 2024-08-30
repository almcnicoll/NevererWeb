<?php

function throw_error($errors) {
    $retval = ['errors' => $errors];
    if (is_array($errors)) { error_log(print_r($errors,true)); } else { error_log($errors); }
    die(json_encode($retval));
}
function populate_from_request($varnames) {
    foreach ($varnames as $varname) {
        if (isset($_REQUEST[$varname])) { $$varname = $_REQUEST[$varname]; }
    }
}

//error_log(print_r($params,true));

if ((!is_array($params))||(count($params) == 0)) {
    $file = str_replace(__DIR__,'',__FILE__);
    throw_error("No valid action passed to {$file}");
}
$action = array_shift($params);

//error_log($action);

switch ($action) {
    case 'list':
        // Called as /ajax/placed_clue/list/[crossword_id]
        $crossword_id = array_shift($params);
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        //TODO - output list of clues serialized to JSON here
        die(json_encode([]));
    case 'get':
        // Called as /ajax/placed_clue/get/[id]
        $pc_id = array_shift($params);
        $placed_clue = PlacedClue::findFirst('id','=',$pc_id);
        if ($placed_clue === null) { throw_error("Cannot find clue with id {$pc_id}"); }
        $crossword_id = $placed_clue->crossword_id;
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        //TODO - output clue serialized to JSON here
        die(json_encode([]));
    case 'create':
        // TODO - Validation here
        // Populate and save the entities
        error_log(print_r($_POST,true));
        LoggedError::log(LoggedError::TYPE_PHP, 1, __FILE__, __LINE__, print_r($_REQUEST,true));
        $pc = new PlacedClue();
        $pc->x = $_POST['col'];
        $pc->y = $_POST['row'];
        $pc->orientation = $_POST['orientation'];
        $pc->save();
        $c = new Clue();
        $c->answer = $_POST['answer'];
        $c->pattern = $_POST['pattern'];
        $c->question = $_POST['clue'];
        $c->explanation = $_POST['explanation'];
        $c->placedclue_id = $pc->id;
        $c->save();
        die(json_encode([])); // TODO - consider returning a success/fail, or perhaps the PlacedClue itself in JSON
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}