var rows = 0;
var cols = 0;

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
    var url = root_path + '/grid/*/get/'+crossword_id+'?domain=ajax&xMin='+xMin+'&xMax='+xMax+'&yMin='+yMin+'&yMax='+yMax;
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
