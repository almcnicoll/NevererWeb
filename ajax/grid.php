<?php
    use UI\Grid;
    use Crosswords\Crossword;
use Crosswords\PlacedClue;

    function throw_error($errors) {
        $retval = ['errors' => $errors];
        if (is_array($errors)) { error_log(print_r($errors,true)); } else { error_log($errors); }
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
        case 'get':
            // Called as /ajax/grid/get/[id]?xMin=&yMin=&xMax=&yMax=
            Grid::ensureLoaded();
            // Retrieve crossword
            $crossword_id = array_shift($params);
            /** @var Crossword $crossword */
            $crossword = Crossword::findFirst(['id','=',$crossword_id]);
            if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
            if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
            // Retrieve grid from crossword
            $xMin = 0; $xMax = $crossword->cols-1; $yMin = 0; $yMax = $crossword->rows-1;
            populate_from_request(['xMin','xMax','yMin','yMax']);
            $grid = $crossword->getGrid($xMin,$yMin,$xMax,$yMax);
            die(json_encode($grid->toArray()));
        case 'clear':
            // Called as /ajax/grid/clear/[id]/?xMin=&yMin=&xMax=&yMax=
            Grid::ensureLoaded(); PlacedClue::ensureLoaded(); Crossword::ensureLoaded();
            // Retrieve crossword
            $crossword_id = array_shift($params);
            /** @var Crossword $crossword */
            $crossword = Crossword::findFirst(['id','=',$crossword_id]);
            if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
            if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
            // Retrieve grid from crossword
            $xMin = 0; $xMax = $crossword->cols-1; $yMin = 0; $yMax = $crossword->rows-1;
            populate_from_request(['xMin','xMax','yMin','yMax']);
            $grid = $crossword->getGrid($xMin,$yMin,$xMax,$yMax);
            // Now loop through the area, looking for affected clues
            $affectedPlacedClues = [];
            for ($y=$yMin;$y<=$yMax;$y++) {
                for ($x=$xMin;$x<=$xMax;$x++) {
                    $affectedPlacedClues = array_merge($affectedPlacedClues,$grid[$y][$x]->placed_clue_ids);
                }
            }
            $affectedPlacedClues = array_unique($affectedPlacedClues);
            // Now loop through the affected clues, working out which letters to change to question-marks
            /** @var PlacedClue $placedClue */
            foreach($affectedPlacedClues as $placedClueId) {
                // Retrieve object
                /** @var PlacedClue $placedClue */
                $placedClue = PlacedClue::getById($placedClueId);
                // For each clue, clear between specified rows/columns
                switch ($placedClue->orientation) {
                    case PlacedClue::ACROSS:
                        // Determine which columns to/from need clearing
                        $startClear = $xMin - $placedClue->x;
                        $endClear = $xMax - $placedClue->x;
                        break;
                    case PlacedClue::DOWN:
                        // Determine which columns to/from need clearing
                        $startClear = $yMin - $placedClue->y;
                        $endClear = $yMax - $placedClue->y;
                        break;
                    default:
                        // Ignore clues with no orientation
                        continue;
                }
                // Retrieve clue and clear the relevant parts
                $placedClue->clearBetween($startClear,$endClear, true);
            }
            $output = ['placed_clues' => $affectedPlacedClues];
            die(json_encode($output));
        default:
            $file = str_replace(__DIR__,'',__FILE__);
            throw_error("Invalid action {$action} passed to {$file}");
    }