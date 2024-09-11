var rows = 0;
var cols = 0;
var selectedClue = 0;

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

        // Individual actions
        $('#new-clue-default').on('click',createClue);
        $('td.crossword-grid-square').on('click',toggleSelect);

        // Refresh data
        refreshGrid();
        refreshClueList();
    }
);

// Consider breaking out some of these into a separate JS file at some point?
/**
 * Handles ajax errors, displaying them to the user
 * @param {string} json the error(s) to display, as JSON
 */
function displayAjaxError(json) {
    // Do nothing for the moment
    // TODO - need code here - use Bootstrap Toasts methinks https://getbootstrap.com/docs/5.2/components/toasts/
}

/**
 * Retrieves data from the specified form(s) in a single object
 * @param {string} selector the selector of the form to serialize
 * @param {mixed} stripPrefix the string id to strip off the front of field names or {false} to not strip anything
 * @returns {Object} the data in object form
 */
function serializeForm(selector, stripPrefix = false) {
    // Retrieve and check selector
    var data = new Object();
    var forms = $(selector);
    if (forms.length == 0) { return data; }
    for (var i=0; i<forms.length; i++) {
        var form = forms[i];
        var tagName = $(form).prop('tagName');
        if ((tagName !== undefined) && (tagName.toUpperCase() == 'FORM')) {
            // Only work with <form> tags
            $(form).find('input,select,textarea').each(
                function() {
                    var key = $(this).attr('name');
                    if (key === undefined) { key = $(this).attr('id'); }
                    if (key !== undefined) {
                        // There's either a name or an id
                        if (stripPrefix) {
                            if (key.slice(0,stripPrefix.length) == stripPrefix) {
                                key = key.slice(stripPrefix.length);
                            }
                        }
                        data[key] = $(this).val();
                    }
                }
            );
        }
    }
    return data;
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
            sq.data('placed-clue-ids',square.placed_clue_ids.join(','));
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

/** Refreshes the clue list and grid */
function refreshAll() {
    refreshClueList();
    refreshGrid();
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

/**
 * Flags an input as being problematic with a border highlight and an explanatory message
 * @param {string} selector the jQuery selector for the field(s) to highlight
 * @param {string} message the message to display underneath the field, as plain text or HTML
 * @returns {void}
 */
function fieldProblem(selector, message) {
    $(selector).each(
        function() {
            $(this).addClass('is-invalid');
            if ($(this).siblings('.error-explain').length == 0) {
                $("<div class='error-explain invalid-feedback'>&nbsp;</div>").insertAfter($(this));
            }
            $(this).siblings('.error-explain').html(message);
        }
    );
}

/**
 * Gets the pattern for the supplied clue, in the form "(n)", "(n,p)", "(n-p)" etc.
 * @param {string} answer the answer to the cryptic clue
 * @returns {string} the pattern for the clue or null if the answer is blank or invalid
 */
function getAnswerPattern(answer) {
    var reRemoves = /[^A-Z\s\-]+/gi;
    var reSplitters = /[\s\-]+/gi;
    var reSplittersOnly = /^[\s\-]+$/gi;
    var working_answer = answer.replace(reRemoves,'');
    if (working_answer.length == 0) { return null; } // No valid letters
    if (reSplittersOnly.test(working_answer)) { return null; } // Only splitter characters
    var answer_parts = working_answer.split(reSplitters);
    var pattern_parts = new Array();
    for (var i in answer_parts) {
        pattern_parts[i] = answer_parts[i].length;
    }
    return '('+pattern_parts.join(',')+')';
}

/** Triggers the AJAX to create a clue from the new-clue modal */
function createClue() {
    // Populate vars
    var row = $('#new-clue-row').val();
    var col = $('#new-clue-col').val();
    var answer = $('#new-clue-answer').val();
    var clue = $('#new-clue-clue').val();
    var explanation = $('#new-clue-explanation').val();

    // Clear previous validation feedback
    $('#new-clue').find('form').find('.is-invalid').removeClass('is-invalid');
    $('#new-clue').find('form').find('.error-explain').remove();
    // Perform new validation
    if (!$.isNumeric(row)) { fieldProblem('#new-clue-row',"This field must be a number."); return; }
    if (!$.isNumeric(col)) { fieldProblem('#new-clue-col',"This field must be a number."); return; }
    if (answer.length == 0) { fieldProblem('#new-clue-answer',"This field must not be blank."); return; }
    var pattern = getAnswerPattern(answer);
    if (pattern === null) { fieldProblem('#new-clue-answer',"This field must not be blank."); return; }
    $('#new-clue-pattern').val(pattern);

    // Now fire off the request
    var url = root_path + '/placed_clue/*/create/' + crossword_id + '?domain=ajax';
    var formData = serializeForm('#new-clue form','new-clue-');
    $.post({
        url: url,
        data: formData
    })
    .done(refreshAll)
    .fail(displayAjaxError);

    // If all else is fine, hide the modal
    bootstrap.Modal.getInstance(document.getElementById('new-clue')).hide();
}

/**
 * Handles left-clicking a grid square:
 * If square is black, deselects everything
 * If square is white, selects its clue
 * If square is an intersection and a clue is already selected, selects the other intersecting clue
 */
function toggleSelect() {
    // TODO - click handling
    var cluesHereString = $(this).data('placed-clue-ids');
    if ((cluesHereString === undefined) || (cluesHereString === '')) {
        // Black square - deselect all clues
        selectClue(0);
    } else {
        // White square - see if we're already selecting a clue
        var cluesHere = cluesHereString.split(',');
        if (selectedClue == 0) {
            // No clue selected - select first in array
            selectClue(parseInt(cluesHere[0]));
        } else if (selectedClue == parseInt(cluesHere[0])) {
            // Clue selected is first in array - see if there's another that intersects
            if (cluesHere.length>1) {
                // Yes there is - select that
                selectClue(parseInt(cluesHere[1]));
            }
        } else {
            // Clue selected isn't first in array - toggle back to first one
            selectClue(parseInt(cluesHere[0]));
        }
    }
}

/**
 * Selects the specified clue in the UI
 * @param {int} id the id of the clue to select, or 0 to select nothing
 * @returns void
 */
function selectClue(id = 0) {
    // Set variables
    selectedClue = id;
    var reSel = new RegExp('\\b'+id+'\\b'); // To allow for standalone numbers and comma-delimited ones, but not digits within larger numbers
    // Remove selection classes
    $('.crossword-grid-square').removeClass('ui-select');
    if (id == 0) { return; } // Nothing more to do if we're just deselecting - save some unneeded looping
    // Add selection classes
    $('.crossword-grid-square').each(
        function() {
            if (reSel.test($(this).data('placed-clue-ids'))) {
                $(this).addClass('ui-select');
            }
        }
    );
}