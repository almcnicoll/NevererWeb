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
            global $$varname;
            $$varname = $_REQUEST[$varname]; 
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
        // Called as /ajax/placed_clue/*/list/[crossword_id]
        $crossword_id = array_shift($params);
        /** @var Crossword $crossword */
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        $pcList = $crossword->getPlacedClues();
        die(json_encode($pcList->toArray()));
    case 'get':
        // Called as /ajax/placed_clue/*/get/[id]
        $pc_id = array_shift($params);
        /** @var PlacedClue $placedClue */
        $placedClue = PlacedClue::findFirst('id','=',$pc_id);
        if ($placedClue === null) { throw_error("Cannot find clue with id {$pc_id}"); }
        $crossword_id = $placedClue->crossword_id;
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        die(json_encode($placedClue->expose()));
    case 'find':
        // Called as /ajax/placed_clue/*/find/[crossword-id]?orientation=across|down&x=[x]&y=[y]
        // Specifies a cell and an orientation, and retrieves the matching PlacedClue
        // TODO - return symmetry_clues as an additional array index
        // TODO - check that this code works when two across clues are on the same row and when two down clues are on the same column
        $crossword_id = array_shift($params);
        $findCriteria = [];
        populate_from_request(['orientation','x','y']);
        switch (strtolower($orientation)) {
            case 'across':
                $findCriteria = [
                                ['crossword_id','=',$crossword_id],
                                ['orientation','=',strtolower($orientation)],
                                ['x','<=',$x],
                                ['y','=',$y]
                ];
                break;
            case 'down':
                $findCriteria = [
                                ['crossword_id','=',$crossword_id],
                                ['orientation','=',strtolower($orientation)],
                                ['y','<=',$y],
                                ['x','=',$x]
                ];
                break;
            default:
                throw new InvalidArgumentException("Invalid value {$orientation} passed to placed_clue/find");
        }
        $possibleMatches = PlacedClue::find($findCriteria);
        foreach ($possibleMatches as $possibleMatch) {
            /** @var PlacedClue $possibleMatch */
            $clue = $possibleMatch->getClue();
            switch ($possibleMatch->orientation) {
                case 'across':
                    if (($possibleMatch->x + strlen($clue->answer) - 1) >= $x) { die(json_encode($possibleMatch->expose())); }
                    break;
                case 'down':
                    if (($possibleMatch->y + strlen($clue->answer) - 1) >= $y) { die(json_encode($possibleMatch->expose())); }
                    break;
                default:
                    // Shouldn't happen
                    break;
            }
        }
        // No match
        die(json_encode([]));
    case 'symmetry_clues':
        // Called as /ajax/placed_clue/*/symmetry_clues/[id]
        $pc_id = array_shift($params);
        /** @var PlacedClue $placedClue */
        $placedClue = PlacedClue::findFirst('id','=',$pc_id);
        if ($placedClue === null) { throw_error("Cannot find clue with id {$pc_id}"); }
        $symmetryClues = $placedClue->getSymmetryClues();
        $crossword_id = $placedClue->crossword_id;
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        die(json_encode($symmetryClues->expose()));
    case 'create':
        // Called as /ajax/placed_clue/*/create/[crossword_id]
        // TODO - Validation here
        $crossword_id = array_shift($params);
        // Populate and save the entities
        $pc = new PlacedClue();
        $pc->crossword_id = $crossword_id;
        $pc->x = $_POST['col'];
        $pc->y = $_POST['row'];
        $pc->orientation = $_POST['orientation'];
        $c = $pc->getClue();
        $c->answer = $_POST['answer'];
        $c->pattern = $_POST['pattern'];
        $c->question = $_POST['clue'];
        $c->explanation = $_POST['explanation'];
        $pc->save();
        
        // Work out if we need to create other new clues for symmetry
        $crossword = $pc->getCrossword();
        if ($crossword != null) {
            $additionalClues = $crossword->getNewSymmetryClues($pc);
            foreach($additionalClues as $apc) { $apc->save(); }
        }
        
        die(json_encode([])); // TODO - consider returning a success/fail, or perhaps the PlacedClue itself in JSON
    case 'update':
        // Called as /ajax/placed_clue/*/update/[id]
        // TODO - HIGH - this messes up symmetry clues if there are non-pattern characters (spaces, hyphens) in the updated clue
        // TODO - Validation here
        $id = array_shift($params);
        // Populate and save the entities
        $placedClue = PlacedClue::getById($id);
        if ($placedClue === null) { throw_error("Cannot find PlacedClue with id {$id}"); }
        $crossword_id = $placedClue->crossword_id;
        $crossword = Crossword::findFirst(['id','=',$crossword_id]);
        if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
        if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
        
        // Work out what's changed, for symmetry-clue-update purposes
        $clue = $placedClue->getClue();
        $orientation_change = ($placedClue->orientation != $_POST['orientation']);
        $length_change = strlen($_POST['answer']) - strlen($clue->answer);
        $x_change = $_POST['col'] - $placedClue->x;
        $y_change = $_POST['row'] - $placedClue->y;

        // Get symmetry clues - must do this before alterations are made to the original
        $additionalClues = $placedClue->getSymmetryClues();

        // Make changes and save
        $placedClue->x = $_POST['col'];
        $placedClue->y = $_POST['row'];
        $placedClue->orientation = $_POST['orientation'];
        $clue->answer = $_POST['answer'];
        $clue->pattern = $_POST['pattern'];
        $clue->question = $_POST['clue'];
        $clue->explanation = $_POST['explanation'];
        $placedClue->save();

        // Alter and save symmetry clues
        // TODO - test this
        foreach ($additionalClues as $apc) {
            // Get a template clue by rotating the updated $placedClue
            $template = $placedClue->getRotatedClue($apc->__tag);
            $template_clue = $template->getClue();
            $apc->x = $template->x; $apc->y = $template->y;
            $apc->orientation = $template->orientation;
            $ac = $apc->getClue();
            // Trim if the clue has shortened
            while ($ac->getLength() > $template_clue->getLength()) { $ac->answer = substr($ac->answer, 0, -1); }
            // Pad if the clue has lengthened
            while ($ac->getLength() < $template_clue->getLength()) { $ac->answer .= '?'; }
            $apc->save();
        }
        
        die(json_encode([])); // TODO - consider returning a success/fail, or perhaps the PlacedClue itself in JSON
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}