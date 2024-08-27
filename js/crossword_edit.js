var rows = 0;
var cols = 0;

const FLAG_CONFLICT = 1;
const FLAG_FEWMATCHES = 2;    
const FLAG_NOMATCHES = 4;

$(document).ready(
    /** On-load actions here */
    function() {
        // Set variables
        rows = $('#crossword-edit tr').length;
        cols = $('#crossword-edit tr').first().children('td').length;

        // Set up modal focus events
        $('div.modal').each(
            function() {
                if ($(this).find('.focussed-input').length > 0) {
                    var div = $(this)[0];
                    var inp = $(this).find('.focussed-input')[0];
                    div.addEventListener('shown.bs.modal', function() {
                        inp.focus();
                    });
                }
            }
        );

        // Refresh data
        refreshGrid();
        refreshClueList();
    }
);

/**
 * Handles ajax errors, displaying them to the user
 * @param {string} json the error(s) to display, as JSON
 */
function displayAjaxError(json) {
    // Do nothing for the moment
    // TODO - need code here - perhaps a notification area
}

/**
 * 
 * @param {string} json the JSON string of GridSquare objects to refresh
 * @returns {void}
 */
function updateGridSquares(json) {
    // Loop through the list of grid squares, updating as we go
    // NB - json comes in as a multidimensional array ([row][col])
    all_squares = JSON.parse(json);
    // TODO - caught errors will return errors here - consider throwing them with a 400/500 error server-side - otherwise they need managing here - rudimentary method below
    if (all_squares.hasOwnProperty('errors')) {
        displayAjaxError(json);
        return;
    }
    for (var y in all_squares) {
        for (var x in all_squares[y]) {
            var square = all_squares[y][x];
            var sq = $('#square-'+y+'-'+x);
            if (square.black_square) { sq.addClass('black-square'); } else { sq.removeClass('black-square'); }
            if (square.clue_number === null) { sq.children('.clue-number').text(''); } else { sq.children('.clue-number').text(square.clue_number); }
            if (square.flags & FLAG_CONFLICT) { sq.addClass('conflict'); } else { sq.removeClass('conflict'); }
            if (square.flags & FLAG_FEWMATCHES) { sq.addClass('few-matches'); } else { sq.removeClass('few-matches'); }
            if (square.flags & FLAG_NOMATCHES) { sq.addClass('no-matches'); } else { sq.removeClass('no-matches'); }
            sq.children('.letter-holder').text(square.letter);
        }
    }
}

/**
 * Updates the clue list with the specified clues
 * @param {string} json the JSON string of PlacedClue objects to refresh
 * @param {boolean} removeMissing whether to remove any clues that aren't in the list (desirable when updating entire list)
 * @returns {void}
 */
function updateClueList(json, removeMissing=true) {
    // Loop through the list of PlacedClues, updating as we go
    // NB - json comes in as a multidimensional array ([row][col])
    all_squares = JSON.parse(json);
    // TODO - caught errors will return errors here - consider throwing them with a 400/500 error server-side - otherwise they need managing here - rudimentary method below
    if (all_squares.hasOwnProperty('errors')) {
        displayAjaxError(json);
        return;
    }
    for (var y in all_squares) {
        for (var x in all_squares[y]) {
            var square = all_squares[y][x];
            var sq = $('#square-'+y+'-'+x);
            if (square.black_square) { sq.addClass('black-square'); } else { sq.removeClass('black-square'); }
            if (square.clue_number === null) { sq.children('.clue-number').text(''); } else { sq.children('.clue-number').text(square.clue_number); }
            if (square.flags & FLAG_CONFLICT) { sq.addClass('conflict'); } else { sq.removeClass('conflict'); }
            if (square.flags & FLAG_FEWMATCHES) { sq.addClass('few-matches'); } else { sq.removeClass('few-matches'); }
            if (square.flags & FLAG_NOMATCHES) { sq.addClass('no-matches'); } else { sq.removeClass('no-matches'); }
            sq.children('.letter-holder').text(square.letter);
        }
    }
}

/**
 * Updates the specified clues in the list, without altering any others
 * @param {string} json the JSON string of the PlacedClue object to refresh
 */
function updateClues(json) {
    updateClueList(json,false);
}

/**
 * Retrieves the latest contents of the grid within the specified (0-based) coordinates
 * @param {int} xMin the minimum column (inclusive) to retrieve
 * @param {int} xMax the maximum column (inclusive) to retrieve
 * @param {int} yMin the minimum row (inclusive) to retrieve
 * @param {int} yMax the maximum row (inclusive) to retrieve
 */
function refreshPartialGrid(xMin, xMax, yMin, yMax) {
    // Make the request
    var url = root_path + '/grid/*/get/'+crossword_id+'?domain=ajax&xMin='+xMin+'&yMin='+yMin+'&xMax='+xMax+'&yMax='+yMax;
    $.get({
        url: url
    })
    .done(updateGridSquares)
    .fail(displayAjaxError);
}

/**
 * Refreshes the entire crossword grid
 */
function refreshGrid() {
    refreshPartialGrid(0,cols-1,0,rows-1);
}

/**
 * Refreshes the entire list of clues
 * @returns {void}
 */
function refreshClueList() {
    // Make the request
    var url = root_path + '/placed_clue/*/list/'+crossword_id+'?domain=ajax';
    $.get({
        url: url
    })
    .done(updateClueList)
    .fail(displayAjaxError);
}

/**
 * Refreshes the specified clue
 * @param {int} id the id of the placedclue to refresh
 * @returns {void}
 */
function refreshClue(id) {
    // Make the request
    var url = root_path + '/placed_clue/*/get/'+id+'?domain=ajax';
    $.get({
        url: url
    })
    .done(updateClues)
    .fail(displayAjaxError);
}