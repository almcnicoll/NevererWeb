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
        /** @var int $trim_top */
        /** @var int $trim_left */
        /** @var int $trim_bottom */
        /** @var int $trim_right */
        /** @var int $rotational_symmetry_order */
        populate_from_request(['title','rows','cols','trim_top','trim_bottom','rotational-symmetry-order']);
        // NB - trim_top and trim_left default to zero, but allow us to trim rows or cols from top or left instead of bottom / right
        
        $title_change = ($crossword->title != $title);
        $size_change = (($crossword->rows != $rows) || ($crossword->cols != $cols));
        $symmetry_change = ($crossword->rotational_symmetry_order != $rotational_symmetry_order);
        $change = $title_change | $size_change | $symmetry_change;

        // Update title
        if ($title_change) { $crossword->title = $title; }

        // Update size
        if ($size_change) {
            // Set the trims for the remaining dimensions
            $trim_bottom = ($crossword->rows - $rows) - $trim_top;
            $trim_right = ($crossword->cols - $cols) - $trim_left;
            $pcs = $crossword->getPlacedClues();
            // Now check each clue and update as needed
            // NB - we DON'T need to save after each change, because although alterLength refers to the database by calling getClue(),
            //  it caches the resulting Clue object, so subsequent changes will be made to that cached version. This means we don't
            //  run into any issues if we need to trim start and end of the same clue.
            foreach ($pcs as $pc) {
                $altered = false;
                // Check if the clue overlaps the trim areas
                // TOP - clue co-ords are 0-based, so if y is smaller than trim_top then 
                if ($pc->y < $trim_top) {
                    $altered = true;
                    if ($pc->orientation == PlacedClue::ACROSS) { 
                        $pc->delete();
                    } else {
                        $pc->alterLength(0-$trim_top, true, $trim_top - $pc->y);
                    }
                }
                // LEFT - clue co-ords are 0-based, so if x is smaller than trim_left then 
                if ($pc->x < $trim_left) {
                    $altered = true;
                    if ($pc->orientation == PlacedClue::DOWN) { 
                        $pc->delete();
                    } else {
                        $pc->alterLength(0-$trim_left, true, $trim_left - $pc->x);
                    }
                }
                // BOTTOM - clue co-ords are 0-based, so if y+length-1 is greater than new rows then
                $clue_last_square = ($pc->y + $pc->getLength() - 1);
                if ($crossword->rows > $clue_last_square) {
                    $altered = true;
                    if ($pc->orientation == PlacedClue::ACROSS) { 
                        $pc->delete();
                    } else {
                        $pc->alterLength($crossword->rows-$clue_last_square, false);
                    }
                }
                // RIGHT - clue co-ords are 0-based, so if y+length-1 is greater than new rows then
                $clue_last_square = ($pc->x + $pc->getLength() - 1);
                if ($crossword->cols > $clue_last_square) {
                    $altered = true;
                    if ($pc->orientation == PlacedClue::DOWN) { 
                        $pc->delete();
                    } else {
                        $pc->alterLength($crossword->cols-$clue_last_square, false);
                    }
                }

                // Now save if this clue has changed
                if ($altered) {
                    $pc->save();
                }
            }
            $crossword->rows = $rows;
            $crossword->cols = $cols;
        }

        // Update rotational symmetry
        if ($symmetry_change) {
            if ($rotational_symmetry_order > $crossword->rotational_symmetry_order) {
                // We need to act - symmetry order has increased
                $placedClues = $crossword->getPlacedClues();
                foreach ($placedClues as $pc) {
                    /** @var PlacedClue $pc */
                    $existingClues = $crossword->getExistingSymmetryClues($pc, $rotational_symmetry_order);
                    if (count($existingClues) !== ($rotational_symmetry_order-1)) {
                        // We need to add some more
                        $additionalClues = $crossword->getNewSymmetryClues($pc, $rotational_symmetry_order);
                        foreach ($additionalClues as $ac) {
                            $matched = false;
                            foreach ($existingClues as $ec) {
                                if ($ac->x == $ec->x && $ac->y == $ex->y) {
                                    $matched = true; break; // There's a clue in the symmetry position already
                                }
                            }
                            if (!$matched) {
                                // There's currently no clue matching this for symmetry - so save the new one
                                $ac->save();
                            }
                        }
                    }
                }
            }
        }

        // Save
        if ($change) { $crossword->save(); }
        
        die(json_encode($crossword));
    default:
        $file = str_replace(__DIR__,'',__FILE__);
        throw_error("Invalid action {$action} passed to {$file}");
}