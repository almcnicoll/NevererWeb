var rows = 0;
var cols = 0;

$(document).ready(
    function() {
        // On-load actions here
        rows = $('#crossword-edit tr').length;
        cols = $('#crossword-edit tr').first().children('td').length;
    }
);

/**
 * Retrieves the latest contents of the grid within the specified (0-based) coordinates
 * @param {int} xMin the minimum column (inclusive) to retrieve
 * @param {int} xMax the maximum column (inclusive) to retrieve
 * @param {int} yMin the minimum row (inclusive) to retrieve
 * @param {int} yMax the maximum row (inclusive) to retrieve
 */
function refreshPartialGrid(xMin, xMax, yMin, yMax) {
    // 
}

/**
 * Refreshes the entire crossword grid
 */
function refreshGrid() {
    refreshPartialGrid(0,cols-1,0,rows-1);
}
