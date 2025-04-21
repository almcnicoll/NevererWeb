<?php
use Logging\LoggedError;
use Crosswords\Clue, Crosswords\Crossword, Crosswords\PlacedClue;

function throw_error($errors) {
    $retval = ['errors' => $errors];
    if (is_array($errors)) {
        $output = print_r($errors,true);
        error_log($output);
        @LoggedError::log('ajaxError',0,__FILE__,__LINE__,$output);
    } else {
        error_log($errors);
        @LoggedError::log('ajaxError',0,__FILE__,__LINE__,$errors);
    }
    die(json_encode($retval));
}
function populate_from_request($varnames) {
    foreach ($varnames as $varname) {
        if (isset($_REQUEST[$varname])) { 
            $safe_varname = str_replace('-','_',$varname);
            global $$safe_varname;
            $$safe_varname = $_REQUEST[$varname]; 
        }
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
        // Called as /ajax/crossword/*/list
        /** @var Crossword $crossword */
        $crosswords = Crossword::find(['user_id','=',$user->id]);
        die(json_encode($crosswords->toArray()));
    case 'get':
        // Called as /ajax/crossword/*/get/[id]
        $crossword_id = array_shift($params);
        /** @var Crossword $crossword */
        $crossword = Crossword::findFirst('id','=',$crossword_id);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        die(json_encode($crossword->expose()));
    case 'update':
        // Called as /ajax/crossword/*/update/[id]
        $id = array_shift($params);
        // Populate and save the entities
        $crossword = Crossword::getById($id);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        
        // Work out what's changed
        /** @var string $title */
        /** @var int $rows */
        /** @var int $cols */
        /** @var int $rotational_symmetry_order */
        populate_from_request('title','rows','cols','rotational-symmetry-order');
        
        $title_change = ($crossword->title != $title);
        $size_change = (($crossword->rows != $rows) || ($crossword->cols != $cols));
        $symmetry_change = ($crossword->rotational_symmetry_order != $rotational_symmetry_order);
        $change = $title_change | $size_change | $symmetry_change;

        // Make changes and save
        if ($title_change) { $crossword->title = $title; }

        // Save
        if ($change) { $crossword->save(); }

        // TODO - HIGH finish this function

        // Code after here not converted
        $placedClue->y = $_POST['row'];
        $placedClue->orientation = $_POST['orientation'];
        $clue->answer = $_POST['answer'];
        $clue->pattern = $_POST['pattern'];
        $clue->question = $_POST['clue'];
        $clue->explanation = $_POST['explanation'];

        // Alter and save symmetry clues
        foreach ($additionalClues as $apc) {
            // Get a template clue by rotating the updated $placedClue
            $template = $placedClue->getRotatedClue($apc->__tag);
            $template_clue = $template->getClue();
            $apc->x = $template->x; $apc->y = $template->y;
            $apc->orientation = $template->orientation;
            $ac = $apc->getClue();
            // Trim if the clue has shortened
            while ($ac->getLength() > $template_clue->getLength()) { 
                $ac->answer = substr($ac->answer, 0, -1); 
            }
            // Pad if the clue has lengthened
            while ($ac->getLength() < $template_clue->getLength()) { 
                $ac->answer .= '?'; 
            }
            $apc->save();
        }
        
        die(json_encode($pc));
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}