<?php
    use UI\Grid;
    use Crosswords\Crossword;

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
        case 'get':
            // Called as /ajax/grid/get/[id]?xMin=&yMin=&xMax=&yMax=
            Grid::ensureLoaded();
            $crossword_id = array_shift($params);
            /** @var Crossword $crossword */
            $crossword = Crossword::findFirst(['id','=',$crossword_id]);
            if ($crossword === null) { throw_error("Cannot find crossword with id {$crossword_id}"); }
            if (!$crossword->isOwnedBy($user->id)) { throw_error("Crossword with id {$crossword_id} does not belong to user #{$user->id}"); }
            $xMin = 0; $xMax = $crossword->cols-1; $yMin = 0; $yMax = $crossword->rows-1;
            populate_from_request(['xMin','xMax','yMin','yMax']);
            $grid = $crossword->getGrid($xMin,$yMin,$xMax,$yMax);
            die(json_encode($grid->toArray()));
        default:
            $file = str_replace(__DIR__,'',__FILE__);
            throw_error("Invalid action {$action} passed to {$file}");
    }