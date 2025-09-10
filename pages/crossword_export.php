<?php
    use Crosswords\Crossword;
    
    $fatal_error = false;

    $error_messages = [];
    if (isset($_REQUEST['error_message'])) {
        $error_messages[] = $_REQUEST['error_message'];
    }

    $crossword = null;

    if (!isset($params[0])) {
        $error_messages[] = "You need to choose which crossword to edit.";
        $fatal_error = true;
    } elseif (!is_numeric($params[0])) {
        $error_messages[] = "{$params[0]} isn't a valid crossword id.";
        //DisplayMessage::add("{$params[0]} isn't a valid crossword id.", DisplayMessage::LVL_ERROR, 1);
        $fatal_error = true;
    } else {
        $crossword = Crossword::getById($params[0]);
        if ($crossword->user_id != $_SESSION['USER_ID']) {
            $error_messages[] = "You can only edit crosswords that you created.";
            //DisplayMessage::add("You can only edit crosswords that you created.", DisplayMessage::LVL_ERROR, 1);
            $fatal_error = true;
        }
    }

    if (count($error_messages)>0) {
        // If we can't edit, redirect to index and give error messages
        echo json_encode(['errors' => $error_messages]);
        die();
    }

    if (isset($_SESSION['PAGE_LOADCOUNTS'][__FILE__])) {
        $_SESSION['PAGE_LOADCOUNTS'][__FILE__]++;
    } else {
        $_SESSION['PAGE_LOADCOUNTS'][__FILE__] = 1;
    }

    header("Content-Type: application/json; charset=UTF-8");

    // Prevent caching (forces a fresh fetch each time)
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $crossword->exportAsJSON();
    die();
?>