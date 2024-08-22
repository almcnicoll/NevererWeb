var rows = 0;
var cols = 0;

const FLAG_CONFLICT = 1;
const FLAG_FEWMATCHES = 2;    
const FLAG_NOMATCHES = 4;

$(document).ready(
    function() {
        // On-load actions here
        rows = $('#crossword-edit tr').length;
        cols = $('#crossword-edit tr').first().children('td').length;

        refreshGrid();
    }
);

/**
 * Handles ajax errors, displaying them to the user
 * @param {string} json the error(s) to display, as JSON
 */
function displayAjaxError(json) {
    // Do nothing for the moment
}

function updateGridSquares(json) {
    // Loop through the list of grid squares, updating as we go
    // NB - json comes in as a multidimensional array ([row][col])
    all_squares = JSON.parse(json);
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
