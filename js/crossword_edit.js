// Global variables
var rows = 0;
var cols = 0;
var selectedClue = 0;
var ajaxCallId = 0;
var ajaxCalls = new Object();

// Constants
const FLAG_CONFLICT = 1;
const FLAG_FEWMATCHES = 2;    
const FLAG_NOMATCHES = 4;

//#region Utility methods
/**
 * 
 * @param {Array} arr the array to search
 * @param {*} value the value to remove if found
 * @returns {bool} whether the value was found and removed
 */
function removeFromArray(arr,value) {
    var index = arr.indexOf(value);
    if (index > -1) {
        arr.splice(index,1);
        return true; // We removed it
    }
    return false; // Nothing to remove
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
//#endregion

//#region Ajax handling
// TODO - consider prompting an internet connection check if makeAjaxCall is regularly returning fails

/**
 * 
 * @param {string} method 'get' or 'post'
 * @param {string} url the URL to call
 * @param {*} data the data to pass
 * @param {function} done the function to call on success
 * @param {function} fail the function to call on failure
 * @param {function} always the function to call on both success and failure
 */
function makeAjaxCall(method, url, data = null, done = null, fail = null, always = null) {
    method = method.toLowerCase();
    if ((method!='get') && (method!='post')) {
        throw new Error("Invalid method specified");
    }
    // Assign an id
    var aId = ++ajaxCallId;
    // Add UI cue
    //$('#ajaxCount').css('width',(ajaxCalls.length + 1)*20);
    $('#ajaxCount').css('width',(Object.keys(ajaxCalls).length + 1)*20);
    // Manage any null args
    if (always == null) { always = function(){}; }
    if (done == null) { done = function(){}; }
    if (fail == null) { fail = function(){}; }
    // Add call to queue and make the call
    switch (method) {
        case 'get':
            ajaxCalls[aId] = $.get({url: url, data: data}).done(done).fail(fail).always(always).always(handleAjaxReturn);
            break;
        case 'post':
            ajaxCalls[aId] = $.post({url: url, data: data}).done(done).fail(fail).always(always).always(handleAjaxReturn);
            break;
    }
    ajaxCalls[aId].aId = aId; // So we can handle the return
}

//function( data|jqXHR, textStatus, jqXHR|errorThrown ) { }
function handleAjaxReturn(arg1, textStatus, arg3) {
    // Assign vars
    var jqXHR = (textStatus == 'success') ? arg3 : arg1;
    var data = (textStatus == 'success') ? arg1 : null;
    var errorThrown = (textStatus == 'success') ? null : arg3;
    // Manage success/failure in UI
    switch (textStatus) {
        case 'failure':
            // TODO - some kind of failure notification using or replacing displayAjaxError()
            break;
    }
    // Remove UI cue
    delete ajaxCalls[jqXHR.aId];
    //$('#ajaxCount').css('width',(ajaxCalls.length)*20);
    $('#ajaxCount').css('width',(Object.keys(ajaxCalls).length)*20);
}

/**
 * Handles ajax errors, displaying them to the user
 * @param {string} json the error(s) to display, as JSON
 */
function displayAjaxError(json) {
    // Do nothing for the moment
    // TODO - need code here - use Bootstrap Toasts methinks https://getbootstrap.com/docs/5.2/components/toasts/
}

//#endregion


//#region startup
$(document).ready(
    /** On-load actions here */
    function() {
        // Set variables
        rows = $('#crossword-edit tr').length;
        cols = $('#crossword-edit tr').first().children('td').length;

        // Set up modal events
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
        $('div.modal form').on('keypress', function(eventObject) { 
            if (eventObject.which==13) { $(this).parents('div.modal').find(':submit').trigger('click'); eventObject.preventDefault=true; } 
        })

        // Individual actions
        $('#new-clue-default').on('click',createClue);
        $('#edit-clue-default').on('click',editClue);
        $('td.crossword-grid-square').on('click',toggleSelect);
        $('td.crossword-grid-square').on('contextmenu', gridSquareRightClickHandler);
        $('#context-menu-menu-grid-square .dropdown-item').on('click', gridSquareMenuClickHandler);

        // Refresh data
        refreshGrid();
        refreshClueList();
    }
);
//#endregion

//#region page-refreshing
/**
 * Updates the UI grid with the supplied crossword data
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
            // NB for the data() calls below, we want to set attr() too, so that we can use jQuery attribute selectors later
            sq.data('placed-clue-ids',square.placed_clue_ids.join(',')).attr('data-placed-clue-ids',square.placed_clue_ids.join(','));
            sq.data('has-across-clue',(square.intersects & 1)>0).attr('data-has-across-clue',(square.intersects & 1)>0);
            sq.data('has-down-clue',(square.intersects & 2)>0).attr('data-has-down-clue',(square.intersects & 2)>0);
            var letter = (square.letter == '') ? '&nbsp;' : square.letter;
            sq.children('.letter-holder').html(letter);
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
    // TODO - HIGH test this code
    // Loop through the list of PlacedClues, updating as we go
    // NB - json comes in as a multidimensional array ([row][col])
    allClues = JSON.parse(json);
    // TODO - caught errors will return errors here - consider throwing them with a 400/500 error server-side - otherwise they need managing here - rudimentary method below
    if (allClues.hasOwnProperty('errors')) {
        displayAjaxError(json);
        return;
    }
    var lastOrientation = '';
    var lastClueNumber = 0;
    var unusedIds = $.map($(".clue-row"), function(n, i){
        return n.id;
    });
    // Clues should be in order - all across and then all down
    for (var i in allClues) {
        var pClue = allClues[i];
        var num = pClue.place_number;
        var ori = pClue.orientation;
        if (ori !== lastOrientation) { lastClueNumber = 0; lastOrientation = ori } // Change of orientation = back to the start of numbering
        var id = ori + '-' + num;
        var clueRow = $('tr#'+id+'.clue-row');
        if (clueRow.length > 0) {
            // We have this clue already - update it
            clueRow.find('.clue-number').text(num); // Update number
            clueRow.find('.clue-question').text(pClue.clue.question); // Update question
            //unusedIds.removeByValue(id); // And remove it from unused list
            removeFromArray(unusedIds,id);
        } else {
            // We need to add this clue
            var tbody_container = $('#clues-'+ori+'-container');
            var insertBefore = false;
            tbody_container.find('tr.clue-row').each(
                function() {
                    if (insertBefore === false) {
                        // Let's look for a row where our insert clue-number is lower then the row clue-number
                        if ($(this).data('clue-number') > num) {
                            insertBefore = $(this).attr('id');
                        }
                    }
                }
            );
            // Create the row
            var newRow = $('tr#clue-row-template').clone().attr('id',id).data('clue-orientation',ori).data('clue-number',num);
            newRow.find('.clue-number').text(num);
            newRow.find('.clue-question').text(pClue.clue.question);
            if (insertBefore === false) {
                // No clues to put before, so add it at the end
                tbody_container.append(newRow);
            } else {
                // Insert before the specified clue
                newRow.insertBefore('#'+insertBefore);
            }
        }
    }
    if (removeMissing) {
        for(var i in unusedIds) {
            $('#'+unusedIds[i]).remove();
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
//#endregion

//#region request functions
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
    makeAjaxCall('get', url, null, updateGridSquares);
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
    makeAjaxCall('get', url, null, updateClueList);
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
    makeAjaxCall('get', url, null, updateClues);
}
//#endregion

//#region data-validation
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
 * Takes the return from a placed_clue/find call and uses it to populate the edit form
 * @param {string} data the JSON string returned by the ajax call
 */
function populateEditForm(data) {
    // Parse returned array
    var arr = JSON.parse(data);
    // Retrieve primary clue
    var pc = arr['original'];
    var c = pc['clue'];
    // Parse additional (symmetry) clues
    var addClues = arr['additional'];
    var symClues;
    if ('_list' in addClues) {
        symClues = arr['additional']['_list'];
    } else {
        symClues = Array();
    }
    var symClueTexts = new Array();
    for (var i=0; i< symClues.length; i++) {
        var symClue = symClues[i];
        symClueTexts.push(symClue.place_number + ' ' + symClue.orientation);
    }
    var symClueText = symClueTexts.join(', ');
    // Put those variables into the modal form
    $('#edit-clue input#edit-clue-id').val( pc.id );
    $('#edit-clue input#edit-clue-row').val( pc.y );
    $('#edit-clue input#edit-clue-col').val( pc.x );
    $('#edit-clue select#edit-clue-orientation').val(pc.orientation);
    $('#edit-clue input#edit-clue-answer').val(c.answer);
    $('#edit-clue input#edit-clue-clue').val(c.question);
    $('#edit-clue input#edit-clue-explanation').val(c.explanation);
    // Symmetry clues message
    if (symClueTexts.length == 0) {
        $('#form-edit-clue-affected-clues-warning').hide();
        $('#form-edit-clue-affected-clues-details').text('');
    } else {
        $('#form-edit-clue-affected-clues-details').text(symClueText);
        $('#form-edit-clue-affected-clues-warning').show();
    }
    new bootstrap.Modal('#edit-clue').toggle();
    $('#edit-clue #edit-clue-answer').focus();
}
/** Triggers the AJAX to create a clue from the new-clue modal */
function createClue() {
    // Populate vars for validation (don't need them for saving as form is serialized)
    var row = $('#new-clue-row').val();
    var col = $('#new-clue-col').val();
    var answer = $('#new-clue-answer').val();
    //var clue = $('#new-clue-clue').val();
    //var explanation = $('#new-clue-explanation').val();

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
    makeAjaxCall('post', url, formData, refreshAll);

    // If all else is fine, hide the modal
    bootstrap.Modal.getInstance(document.getElementById('new-clue')).hide();
}

/** Triggers the AJAX to create a clue from the new-clue modal */
function editClue() {
    // Populate vars for validation (don't need them for saving as form is serialized)
    var id = $('#edit-clue-id').val();
    var row = $('#edit-clue-row').val();
    var col = $('#edit-clue-col').val();
    var answer = $('#edit-clue-answer').val();
    //var clue = $('#edit-clue-clue').val();
    //var explanation = $('#edit-clue-explanation').val();

    // Clear previous validation feedback
    $('#edit-clue').find('form').find('.is-invalid').removeClass('is-invalid');
    $('#edit-clue').find('form').find('.error-explain').remove();
    // Perform edit validation
    if (!$.isNumeric(row)) { fieldProblem('#edit-clue-row',"This field must be a number."); return; }
    if (!$.isNumeric(col)) { fieldProblem('#edit-clue-col',"This field must be a number."); return; }
    if (answer.length == 0) { fieldProblem('#edit-clue-answer',"This field must not be blank."); return; }
    var pattern = getAnswerPattern(answer);
    if (pattern === null) { fieldProblem('#edit-clue-answer',"This field must not be blank."); return; }
    $('#edit-clue-pattern').val(pattern);

    // Now fire off the request
    var url = root_path + '/placed_clue/*/update/' + id + '?domain=ajax';
    var formData = serializeForm('#edit-clue form','edit-clue-');
    makeAjaxCall('post', url, formData, refreshAll);

    // If all else is fine, hide the modal
    bootstrap.Modal.getInstance(document.getElementById('edit-clue')).hide();
}
//#endregion

//#region crossword functions
/**
 * Gets the pattern for the supplied clue, in the form "(n)", "(n,p)", "(n-p)" etc.
 * @param {string} answer the answer to the cryptic clue
 * @returns {string} the pattern for the clue or null if the answer is blank or invalid
 */
function getAnswerPattern(answer) {
    var reRemoves = /[^A-Z\s\-\?]+/gi; // NB includes question mark here, as it's used for unknowns
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
//#endregion

//#region UI-interaction
/**
 * Handles left-clicking a grid square:
 * If square is black, deselects everything
 * If square is white, selects its clue
 * If square is an intersection and a clue is already selected, selects the other intersecting clue
 */
function toggleSelect() {
    // Hide the right-click menu
    $('#context-menu-menu-grid-square').hide();
    // Handle left-click
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

/**
 * Handles right-clicks or other context menu launches
 * @param {Event} eventObject the event containing the various metadata
 */
function gridSquareRightClickHandler(eventObject) {
    // Stop propagation and right-click handling
    eventObject.stopPropagation();
    eventObject.preventDefault();
    // Retrieve and store the trigger square (id is square-r-c)
    var parts = eventObject.currentTarget.id.split('-');
    $('#context-menu-menu-grid-square').data('trigger-row',parts[1]).data('trigger-col',parts[2]);
    // Check the validity of each item
    // TODO - change from visibility to enabled/disabled to prevent menu from being unfamiliar each time?
    if(eventObject.currentTarget.classList.contains('black-square')) { $('#menu-grid-square-clear-grid-square').hide(); } else { $('#menu-grid-square-clear-grid-square').show(); }
    if($(eventObject.currentTarget).data('has-across-clue')) { 
        $('#menu-grid-square-new-clue-across').hide(); 
        $('#menu-grid-square-edit-clue-across').show(); 
    } else {
        $('#menu-grid-square-new-clue-across').show(); 
        $('#menu-grid-square-edit-clue-across').hide(); 
    }
    if($(eventObject.currentTarget).data('has-down-clue')) { 
        $('#menu-grid-square-new-clue-down').hide();
        $('#menu-grid-square-edit-clue-down').show();  
    } else { 
        $('#menu-grid-square-new-clue-down').show(); 
        $('#menu-grid-square-edit-clue-down').hide(); 
    }
    // Move and show menu
    $('#context-menu-menu-grid-square').css('left',eventObject.pageX).css('top',eventObject.pageY).show();
}

/**
 * Handles the clicking of any action from the GridSquareMenu context menu
 * @param {Event} eventObject 
 */
function gridSquareMenuClickHandler(eventObject) {
    // Determine what was clicked
    var action = eventObject.currentTarget.id;
    switch (action) {
        case 'menu-grid-square-new-clue-across':
            $('#new-clue input#new-clue-row').val( $('#context-menu-menu-grid-square').data('trigger-row') );
            $('#new-clue input#new-clue-col').val( $('#context-menu-menu-grid-square').data('trigger-col') );
            $('#new-clue select#new-clue-orientation').val('across');
            new bootstrap.Modal('#new-clue').toggle();
            $('#new-clue #new-clue-answer').focus();
            break;
        case 'menu-grid-square-new-clue-down':
            $('#new-clue input#new-clue-row').val( $('#context-menu-menu-grid-square').data('trigger-row') );
            $('#new-clue input#new-clue-col').val( $('#context-menu-menu-grid-square').data('trigger-col') );
            $('#new-clue select#new-clue-orientation').val('down');
            new bootstrap.Modal('#new-clue').toggle();
            $('#new-clue #new-clue-answer').focus();
            break;
        case 'menu-grid-square-edit-clue-across':
            // We need a database call to get PlacedClue from the square and orientation
            // Get vars
            var y = $('#context-menu-menu-grid-square').data('trigger-row');
            var x = $('#context-menu-menu-grid-square').data('trigger-col');
            var url = root_path + '/placed_clue/*/find/' + crossword_id + '?domain=ajax&orientation=across&x='+x+'&y='+y;
            makeAjaxCall('post', url, null, populateEditForm);
            break;
        case 'menu-grid-square-edit-clue-down':
            // We need a database call to get PlacedClue from the square and orientation
            // Get vars
            var y = $('#context-menu-menu-grid-square').data('trigger-row');
            var x = $('#context-menu-menu-grid-square').data('trigger-col');
            var url = root_path + '/placed_clue/*/find/' + crossword_id + '?domain=ajax&orientation=down&x='+x+'&y='+y;
            makeAjaxCall('post', url, null, populateEditForm);
            break;
        case 'menu-grid-square-clear-grid-square':
            // Get vars
            var y = $('#context-menu-menu-grid-square').data('trigger-row');
            var x = $('#context-menu-menu-grid-square').data('trigger-col');
            // Now fire off the request
            var url = root_path + '/grid/*/clear/' + crossword_id + '?domain=ajax&xMin='+x+'&xMax='+x+'&yMin='+y+'&yMax='+y;
            $.post({
                url: url
            })
            .done(refreshGrid)
            .fail(displayAjaxError);
            break;
        default:
            alert('Not yet implemented!');
            break;
    }
    // Hide menu
    $('#context-menu-menu-grid-square').hide();
}
//#endregion