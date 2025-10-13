// Global variables
var rows = 0;
var cols = 0;
var selectedClue = 0;
var ajaxCallId = 0;
var ajaxCalls = new Object();
var ajaxErrorsCount = 0;

// Constants
const FLAG_CONFLICT = 1;
const FLAG_FEWMATCHES = 2;
const FLAG_NOMATCHES = 4;

//#region Dexie.js
// Define the database
var SolveCache = {};

SolveCache.initDb = function () {
  SolveCache.db = new Dexie("crosswordCache");

  SolveCache.db.version(1).stores({
    crosswords: "id", // primary key: crossword id
  });

  SolveCache.db.open();
};

// Save crossword progress
SolveCache.saveCrosswordProgress = function (id, width, height, letters) {
  return SolveCache.db.crosswords.put({
    id: id,
    width: width,
    height: height,
    letters: letters.replaceAll("?", " "),
  });
};
SolveCache.saveCrosswordProgressArr = function (id, width, height, grid) {
  var letters = grid
    .map(function (row) {
      return row.join("");
    })
    .join("");
  return SolveCache.saveCrosswordProgress(id, width, height, letters);
};

// Load crossword grid (returns a Promise resolving to 2D array)
SolveCache.loadCrosswordGrid = function (id) {
  return SolveCache.db.crosswords.get(id).then(function (entry) {
    if (!entry) return null;

    var width = entry.width;
    var height = entry.height;
    var letters = entry.letters;

    SolveCache.grid = [];
    for (var r = 0; r < height; r++) {
      var start = r * width;
      SolveCache.grid.push(letters.slice(start, start + width).split(""));
      for (var c = 0; c < width; c++) {
        $(`#square-${r}-${c} .letter-holder`).text(
          SolveCache.grid[r][c].toUpperCase()
        );
      }
    }
    return SolveCache.grid;
  });
};
//#endregion

//#region Utility methods
/**
 *
 * @param {Array} arr the array to search
 * @param {*} value the value to remove if found
 * @returns {bool} whether the value was found and removed
 */
function removeFromArray(arr, value) {
  var index = arr.indexOf(value);
  if (index > -1) {
    arr.splice(index, 1);
    return true; // We removed it
  }
  return false; // Nothing to remove
}
//#endregion

//#region Ajax handling

/**
 *
 * @param {string} method 'get' or 'post'
 * @param {string} url the URL to call
 * @param {*} data the data to pass
 * @param {function} done the function to call on success
 * @param {function} fail the function to call on failure
 * @param {function} always the function to call on both success and failure
 */
function makeAjaxCall(
  method,
  url,
  data = null,
  done = null,
  fail = null,
  always = null
) {
  method = method.toLowerCase();
  if (method != "get" && method != "post") {
    throw new Error("Invalid method specified");
  }
  // Assign an id
  var aId = ++ajaxCallId;
  // Add UI cue
  //$('#ajaxCount').css('width',(ajaxCalls.length + 1)*20);
  $("#ajaxCount").css("width", (Object.keys(ajaxCalls).length + 1) * 20);
  // Manage any null args
  if (always == null) {
    always = function () {};
  }
  if (done == null) {
    done = function () {};
  }
  if (fail == null) {
    fail = function () {};
  }
  // Add call to queue and make the call
  switch (method) {
    case "get":
      ajaxCalls[aId] = $.get({ url: url, data: data })
        .done(done)
        .fail(fail)
        .always(always)
        .always(handleAjaxReturn);
      break;
    case "post":
      ajaxCalls[aId] = $.post({ url: url, data: data })
        .done(done)
        .fail(fail)
        .always(always)
        .always(handleAjaxReturn);
      break;
  }
  ajaxCalls[aId].aId = aId; // So we can handle the return
}

/**
 * handles the return of an ajax call: the argument order depends on whether the call was successful
 * The signature is function( data|jqXHR, textStatus, jqXHR|errorThrown ) { }
 * @param {mixed} arg1 the first argument - either the data returned or the jqXHR object
 * @param {string} textStatus the status message
 * @param {mixed} arg3 the third argument - either the jqXHR object or the error thrown
 */
function handleAjaxReturn(arg1, textStatus, arg3) {
  // Assign vars
  var jqXHR = textStatus == "success" ? arg3 : arg1;
  var data = textStatus == "success" ? arg1 : null;
  var errorThrown = textStatus == "success" ? null : arg3;
  // Manage success/failure in UI
  switch (textStatus) {
    case "failure":
      ajaxErrorsCount++;
      if (ajaxErrorsCount > 5) {
        var newline = "\n";
        errorThrown = `Please check your internet connection.${newline}${errorThrown}`;
      }
      makeToast(errorThrown, "error");
      break;
    case "success":
      ajaxErrorsCount = 0;
      break;
  }
  // Remove UI cue
  delete ajaxCalls[jqXHR.aId];
  //$('#ajaxCount').css('width',(ajaxCalls.length)*20);
  $("#ajaxCount").css("width", Object.keys(ajaxCalls).length * 20);
}

/**
 * Handles ajax errors, displaying them to the user
 * @param {string} json the error(s) to display, as JSON
 */
function displayAjaxError(json) {
  let obj;
  // Parse if json is a string
  if (typeof json === "string") {
    obj = JSON.parse(json);
  } else {
    obj = json;
  }
  // Get the properties and build a string
  let text = "";
  for (var prop in obj) {
    if (text != "") {
      text += "\n";
    }
    text += prop + ": " + obj[prop];
  }
  // Now show the toast
  makeToast(text);
}

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
  if (all_squares.hasOwnProperty("errors")) {
    displayAjaxError(json);
    return;
  }
  for (var y in all_squares) {
    for (var x in all_squares[y]) {
      var square = all_squares[y][x];
      var sq = $("#square-" + y + "-" + x);
      if (square.black_square) {
        sq.addClass("black-square");
      } else {
        sq.removeClass("black-square");
      }
      if (square.clue_number === null) {
        sq.children(".clue-number").text("");
      } else {
        sq.children(".clue-number").text(square.clue_number);
      }
      if (square.flags & FLAG_CONFLICT) {
        sq.addClass("conflict");
      } else {
        sq.removeClass("conflict");
      }
      if (square.flags & FLAG_FEWMATCHES) {
        sq.addClass("few-matches");
      } else {
        sq.removeClass("few-matches");
      }
      if (square.flags & FLAG_NOMATCHES) {
        sq.addClass("no-matches");
      } else {
        sq.removeClass("no-matches");
      }
      // NB for the data() calls below, we want to set attr() too, so that we can use jQuery attribute selectors later
      sq.data("placed-clue-ids", square.placed_clue_ids.join(",")).attr(
        "data-placed-clue-ids",
        square.placed_clue_ids.join(",")
      );
      sq.data("has-across-clue", (square.intersects & 1) > 0).attr(
        "data-has-across-clue",
        (square.intersects & 1) > 0
      );
      sq.data("has-down-clue", (square.intersects & 2) > 0).attr(
        "data-has-down-clue",
        (square.intersects & 2) > 0
      );
      var letter = square.letter == "" ? "" : square.letter;
      sq.children(".letter-holder")
        .data("letter", letter)
        .attr("data-letter", letter);
      sq.children(".letter-holder").html("&nbsp;");
    }
  }

  // Load answers from cache
  SolveCache.loadCrosswordGrid(window.crossword_id).then(function (grid) {
    if (!grid) {
      console.log("No data found for crossword " + window.crossword_id);
      grid = [];
      for (let y = 0; y < window.rows; y++) {
        grid[y] = [];
        for (let x = 0; x < window.cols; x++) {
          grid[y][x] = " ";
          $(`#square-${y}-${x} .letter-holder`).text(grid[y][x].toUpperCase());
        }
      }
      SolveCache.grid = grid;
      SolveCache.saveCrosswordProgressArr(
        window.crossword_id,
        window.cols,
        window.rows,
        grid
      );
    } else {
      console.log("Loaded grid:", grid);
    }
  });

  if (selectedClue != 0) {
    selectClue(selectedClue);
  }
}
/**
 * Updates the clue list with the specified clues
 * @param {string} json the JSON string of PlacedClue objects to refresh
 * @param {boolean} removeMissing whether to remove any clues that aren't in the list (desirable when updating entire list)
 * @returns {void}
 */
function updateClueList(json, removeMissing = true) {
  // Loop through the list of PlacedClues, updating as we go
  // NB - json comes in as a multidimensional array ([row][col])
  allClues = JSON.parse(json);
  // TODO - caught errors will return errors here - consider throwing them with a 400/500 error server-side - otherwise they need managing here - rudimentary method below
  if (allClues.hasOwnProperty("errors")) {
    displayAjaxError(json);
    return;
  }
  var lastOrientation = "";
  var lastClueNumber = 0;
  // unusedIds stores ids of rows which haven't been created/updated, and which should therefore be deleted
  // speciifying in #clue-list table is KEY, otherwise template rows are removed!
  var unusedIds = $.map($("#clue-list .clue-row"), function (n, i) {
    return n.id;
  });
  // Clues should be in order - all across and then all down
  for (var i in allClues) {
    var pClue = allClues[i];
    var pcId = pClue.id;
    var num = pClue.place_number;
    var ori = pClue.orientation;
    if (ori !== lastOrientation) {
      lastClueNumber = 0;
      lastOrientation = ori;
    } // Change of orientation = back to the start of numbering
    var id = ori + "-" + num;
    var clueRow = $("tr#" + id + ".clue-row");
    if (clueRow.length > 0) {
      // We have this clue already - update it
      clueRow.find(".clue-number").text(num); // Update number
      if (pClue.clue.question == null || pClue.clue.question == "") {
        clueRow.find(".clue-question").html("<i>" + pClue.clue.answer + "</i>"); // Update question text with answer in italics
      } else {
        clueRow
          .find(".clue-question")
          .html(
            `<span class="clue-question">${pClue.clue.question}</span><span class="clue-pattern"> ${pClue.clue.pattern}</span>`
          ); // Update question text
      }
      //unusedIds.removeByValue(id); // And remove it from unused list
      removeFromArray(unusedIds, id);
    } else {
      // We need to add this clue
      var tbody_container = $("#clues-" + ori + "-container");
      var insertBefore = false;
      tbody_container.find("tr.clue-row").each(function () {
        if (insertBefore === false) {
          // Let's look for a row where our insert clue-number is lower then the row clue-number
          if ($(this).data("clue-number") > num) {
            insertBefore = $(this).attr("id");
          }
        }
      });
      // Create the row
      var newRow = $("tr#clue-row-template")
        .clone()
        .attr("id", id)
        .data("clue-orientation", ori)
        .data("clue-number", num)
        .data("placed-clue-id", pcId)
        .attr("data-clue-orientation", ori)
        .attr("data-clue-number", num)
        .attr("data-placed-clue-id", pcId);
      newRow.find(".clue-number").text(num);
      if (pClue.clue.question == null || pClue.clue.question == "") {
        newRow.find(".clue-question").html("<i>" + pClue.clue.answer + "</i>"); // Update question text with answer in italics
      } else {
        newRow
          .find(".clue-question")
          .html(
            `<span class="clue-question">${pClue.clue.question}</span><span class="clue-pattern"> ${pClue.clue.pattern}</span>`
          ); // Update question text
      }
      if (insertBefore === false) {
        // No clues to put before, so add it at the end
        tbody_container.append(newRow);
      } else {
        // Insert before the specified clue
        newRow.insertBefore("#" + insertBefore);
      }
    }
  }
  if (removeMissing) {
    for (var i in unusedIds) {
      $("#" + unusedIds[i]).remove();
    }
  }
}
/**
 * Updates the specified clues in the list, without altering any others
 * @param {string} json the JSON string of the PlacedClue object to refresh
 */
function updateClues(json) {
  updateClueList(json, false);
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
  var url =
    root_path +
    "/grid/*/get/" +
    crossword_id +
    "?domain=ajax&xMin=" +
    xMin +
    "&yMin=" +
    yMin +
    "&xMax=" +
    xMax +
    "&yMax=" +
    yMax;
  makeAjaxCall("get", url, null, updateGridSquares);
}

/**
 * Refreshes the entire crossword grid
 */
function refreshGrid() {
  refreshPartialGrid(0, cols - 1, 0, rows - 1);
}

/**
 * Refreshes the entire list of clues
 * @returns {void}
 */
function refreshClueList() {
  // Make the request
  var url = root_path + "/placed_clue/*/list/" + crossword_id + "?domain=ajax";
  makeAjaxCall("get", url, null, updateClueList);
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
  var url = root_path + "/placed_clue/*/get/" + id + "?domain=ajax";
  makeAjaxCall("get", url, null, updateClues);
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
  $(selector).each(function () {
    $(this).addClass("is-invalid");
    if ($(this).siblings(".error-explain").length == 0) {
      $("<div class='error-explain invalid-feedback'>&nbsp;</div>").insertAfter(
        $(this)
      );
    }
    $(this).siblings(".error-explain").html(message);
  });
}

/**
 * Takes the return from a placed_clue/find call and uses it to populate the edit form
 * @param {string} data the JSON string returned by the ajax call
 */
function populateEditForm(data) {
  // Parse returned array
  var arr = JSON.parse(data);
  // Retrieve primary clue
  var pc = arr["original"];
  var c = pc["clue"];
  // Parse additional (symmetry) clues
  var addClues = arr["additional"];
  var symClues;
  if ("_list" in addClues) {
    symClues = arr["additional"]["_list"];
  } else {
    symClues = Array();
  }
  var symClueTexts = new Array();
  for (var i = 0; i < symClues.length; i++) {
    var symClue = symClues[i];
    symClueTexts.push(symClue.place_number + " " + symClue.orientation);
  }
  var symClueText = symClueTexts.join(", ");

  // Parse intersecting clues
  var intCluesList = arr["intersecting"];
  if (!intCluesList instanceof Array) {
    intCluesList = Array();
  }
  for (var ii = 0; ii < intCluesList.length; ii++) {
    // Work out where it overlaps
    var intClue = intCluesList[ii];
    var srcPos; // 0-based position in the source/donor (intersecting) clue
    var destPos; // 0-based position in the dest/target (editing) clue
    if (pc.orientation == "across" && intClue.orientation == "down") {
      srcPos = pc.y - intClue.y;
      destPos = intClue.x - pc.x;
    } else if (pc.orientation == "down" && intClue.orientation == "across") {
      srcPos = pc.x - intClue.x;
      destPos = intClue.y - pc.y;
    }

    // Only do the replacement if we've got an actual letter (not a question mark etc.)
    var intersectLetter = intClue.clue.bare_letters
      .substr(srcPos, 1)
      .toUpperCase();
    if (intersectLetter >= "A" && intersectLetter <= "Z") {
      // Work outwhere to put it in c.answer, allowing for punctuation and spaces
      destPos = increaseSpacedPos(c.answer, destPos);
      // This should ensure we use the right part of the intersecting clue, even if there's punctuation and spaces in it
      c.answer =
        c.answer.substr(0, destPos) +
        intClue.clue.bare_letters.substr(srcPos, 1).toUpperCase() +
        c.answer.substr(destPos + 1);
    }
  }

  // Put those variables into the modal form
  $("#edit-clue input#edit-clue-id").val(pc.id);
  $("#edit-clue input#edit-clue-row").val(pc.y);
  $("#edit-clue input#edit-clue-col").val(pc.x);
  $("#edit-clue select#edit-clue-orientation").val(pc.orientation);
  $("#edit-clue input#edit-clue-answer").val(c.answer);
  $("#edit-clue input#edit-clue-answer").data("old-answer", c.answer);
  $("table.word-list tbody td").remove();
  $("#edit-clue-suggested-words-pattern").text(c.answer);
  $("#edit-clue input#edit-clue-clue").val(c.question);
  $("#edit-clue input#edit-clue-explanation").val(c.explanation);
  refreshSuggestedWordList("edit");
  // Symmetry clues message
  if (symClueTexts.length == 0) {
    $("#form-edit-clue-affected-clues-warning").hide();
    $("#form-edit-clue-affected-clues-details").text("");
  } else {
    $("#form-edit-clue-affected-clues-details").text(symClueText);
    $("#form-edit-clue-affected-clues-warning").show();
  }
  new bootstrap.Modal("#edit-clue").toggle();
  $("#edit-clue #edit-clue-answer").focus();
}
/** Triggers the AJAX to create a clue from the new-clue modal */
function createClue() {
  // Populate vars for validation (don't need them for saving as form is serialized)
  var row = $("#new-clue-row").val();
  var col = $("#new-clue-col").val();
  var answer = $("#new-clue-answer").val();
  //var clue = $('#new-clue-clue').val();
  //var explanation = $('#new-clue-explanation').val();

  // Clear previous validation feedback
  $("#new-clue").find("form").find(".is-invalid").removeClass("is-invalid");
  $("#new-clue").find("form").find(".error-explain").remove();
  // Perform new validation
  if (!$.isNumeric(row)) {
    fieldProblem("#new-clue-row", "This field must be a number.");
    return;
  }
  if (!$.isNumeric(col)) {
    fieldProblem("#new-clue-col", "This field must be a number.");
    return;
  }
  if (answer.length == 0) {
    fieldProblem("#new-clue-answer", "This field must not be blank.");
    return;
  }
  var pattern = getAnswerPattern(answer);
  if (pattern === null) {
    fieldProblem("#new-clue-answer", "This field must not be blank.");
    return;
  }
  $("#new-clue-pattern").val(pattern);

  // Now fire off the request
  var url =
    root_path + "/placed_clue/*/create/" + crossword_id + "?domain=ajax";
  var formData = serializeForm("#new-clue form", "new-clue-");
  makeAjaxCall("post", url, formData, refreshAll);

  // If all else is fine, hide the modal
  bootstrap.Modal.getInstance(document.getElementById("new-clue")).hide();
}

/** Triggers the AJAX to create a clue from the new-clue modal */
function editClue() {
  // Populate vars for validation (don't need them for saving as form is serialized)
  var id = $("#edit-clue-id").val();
  var row = $("#edit-clue-row").val();
  var col = $("#edit-clue-col").val();
  var answer = $("#edit-clue-answer").val();
  //var clue = $('#edit-clue-clue').val();
  //var explanation = $('#edit-clue-explanation').val();

  // Clear previous validation feedback
  $("#edit-clue").find("form").find(".is-invalid").removeClass("is-invalid");
  $("#edit-clue").find("form").find(".error-explain").remove();
  // Perform edit validation
  if (!$.isNumeric(row)) {
    fieldProblem("#edit-clue-row", "This field must be a number.");
    return;
  }
  if (!$.isNumeric(col)) {
    fieldProblem("#edit-clue-col", "This field must be a number.");
    return;
  }
  if (answer.length == 0) {
    fieldProblem("#edit-clue-answer", "This field must not be blank.");
    return;
  }
  var pattern = getAnswerPattern(answer);
  if (pattern === null) {
    fieldProblem("#edit-clue-answer", "This field must not be blank.");
    return;
  }
  $("#edit-clue-pattern").val(pattern);

  // Now fire off the request
  var url = root_path + "/placed_clue/*/update/" + id + "?domain=ajax";
  var formData = serializeForm("#edit-clue form", "edit-clue-");
  makeAjaxCall("post", url, formData, refreshAll);

  // If all else is fine, hide the modal
  bootstrap.Modal.getInstance(document.getElementById("edit-clue")).hide();
}

/** Updates the trim_... fields with the appropriate values, based on other values changing on the form */
function updateTrims() {
  // Calcs
  var old_rows = $("#edit-settings-old_rows").val();
  var new_rows = $("#edit-settings-rows").val();
  var trim_top = $("#edit-settings-trim_top").val();
  var row_increase = new_rows - old_rows;
  trim_bottom = 0 - row_increase - trim_top;
  var old_cols = $("#edit-settings-old_cols").val();
  var new_cols = $("#edit-settings-cols").val();
  var trim_left = $("#edit-settings-trim_left").val();
  var col_increase = new_cols - old_cols;
  trim_right = 0 - col_increase - trim_left;
  // Update fields
  $("#edit-settings-trim_bottom").val(trim_bottom);
  $("#edit-settings-trim_right").val(trim_right);
}

/** Triggers the AJAX to create a clue from the new-clue modal */
function editSettings() {
  // Populate vars for validation (don't need them for saving as form is serialized)
  var id = $("#edit-settings-id").val();
  rows = $("#edit-settings-rows").val();
  cols = $("#edit-settings-cols").val();
  var title = $("#edit-settings-title").val();

  // Clear previous validation feedback
  $("#edit-settings")
    .find("form")
    .find(".is-invalid")
    .removeClass("is-invalid");
  $("#edit-settings").find("form").find(".error-explain").remove();
  // Perform edit validation
  if (!$.isNumeric(rows)) {
    fieldProblem("#edit-settings-rows", "This field must be a number.");
    return;
  }
  if (!$.isNumeric(cols)) {
    fieldProblem("#edit-settings-cols", "This field must be a number.");
    return;
  }
  if (title.length == 0) {
    fieldProblem("#edit-settings-title", "This field must not be blank.");
    return;
  }

  // Now fire off the request
  var url = root_path + "/crossword/*/update/" + id + "?domain=ajax";
  var formData = serializeForm("#edit-settings form", "edit-settings-");
  makeAjaxCall("post", url, formData, refreshAll);

  // If all else is fine, hide the modal
  bootstrap.Modal.getInstance(document.getElementById("edit-settings")).hide();
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
  var working_answer = answer.replace(reRemoves, "");
  if (working_answer.length == 0) {
    return null;
  } // No valid letters
  if (reSplittersOnly.test(working_answer)) {
    return null;
  } // Only splitter characters
  var answer_parts = working_answer.split(reSplitters);
  var pattern_parts = new Array();
  for (var i in answer_parts) {
    pattern_parts[i] = answer_parts[i].length;
  }
  return "(" + pattern_parts.join(",") + ")";
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
  $("#context-menu-menu-grid-square").hide();
  // Handle left-click
  var cluesHereString = $(this).data("placed-clue-ids");
  if (cluesHereString === undefined || cluesHereString === "") {
    // Black square - deselect all clues
    selectClue(0);
  } else {
    // White square - see if we're already selecting a clue
    // TODO HIGH - populate solver input field here
    var cluesHere = cluesHereString.split(",");
    if (selectedClue == 0) {
      // No clue selected - select first in array
      selectClue(parseInt(cluesHere[0]));
    } else if (selectedClue == parseInt(cluesHere[0])) {
      // Clue selected is first in array - see if there's another that intersects
      if (cluesHere.length > 1) {
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
  let startX = null;
  let startY = null;
  selectedClue = id;
  var reSel = new RegExp("\\b" + id + "\\b"); // To allow for standalone numbers and comma-delimited ones, but not digits within larger numbers
  // Remove selection classes
  $(".crossword-grid-square").removeClass("ui-select");
  $(".clue-row").removeClass("ui-select");
  if (id == 0) {
    setAnswerEntry("");
    return;
  } // Nothing more to do if we're just deselecting - save some unneeded looping
  // Add selection classes
  // Grid squares
  let cachedAnswer = "";
  $(".crossword-grid-square").each(function () {
    if (reSel.test($(this).data("placed-clue-ids"))) {
      $(this).addClass("ui-select");
      let [junk, y, x] = $(this).attr("id").split("-");
      cachedAnswer += SolveCache.grid[y][x];
      // Set start pos from clue
      if (startX == null) {
        startX = x;
      }
      if (startY == null) {
        startY = y;
      }
    }
  });
  // Clue list
  let pcOrientation;
  $(".clue-row").each(function () {
    if ($(this).data("placed-clue-id") == id) {
      $(this).addClass("ui-select");
      pcOrientation = $(this).data("clue-orientation");
    }
  });
  setAnswerEntry(cachedAnswer, id, pcOrientation, startX, startY);
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
  var parts = eventObject.currentTarget.id.split("-");
  $("#context-menu-menu-grid-square")
    .data("trigger-row", parts[1])
    .data("trigger-col", parts[2]);
  // Check the validity of each item
  if (eventObject.currentTarget.classList.contains("black-square")) {
    $("#menu-grid-square-clear-grid-square").hide();
  } else {
    $("#menu-grid-square-clear-grid-square").show();
  }
  if ($(eventObject.currentTarget).data("has-across-clue")) {
    $("#menu-grid-square-new-clue-across").hide();
    $("#menu-grid-square-edit-clue-across").show();
    $("#menu-grid-square-delete-clue-across").show();
  } else {
    $("#menu-grid-square-new-clue-across").show();
    $("#menu-grid-square-edit-clue-across").hide();
    $("#menu-grid-square-delete-clue-across").hide();
  }
  if ($(eventObject.currentTarget).data("has-down-clue")) {
    $("#menu-grid-square-new-clue-down").hide();
    $("#menu-grid-square-edit-clue-down").show();
    $("#menu-grid-square-delete-clue-down").show();
  } else {
    $("#menu-grid-square-new-clue-down").show();
    $("#menu-grid-square-edit-clue-down").hide();
    $("#menu-grid-square-delete-clue-down").hide();
  }
  // Move and show menu
  $("#context-menu-menu-grid-square")
    .css("left", eventObject.pageX)
    .css("top", eventObject.pageY)
    .show();
}

/**
 * Handles the clicking of any action from the GridSquareMenu context menu
 * @param {Event} eventObject
 */
function gridSquareMenuClickHandler(eventObject) {
  // Determine what was clicked
  var action = eventObject.currentTarget.id;
  switch (action) {
    case "menu-grid-square-new-clue-across":
      $("#new-clue input#new-clue-row").val(
        $("#context-menu-menu-grid-square").data("trigger-row")
      );
      $("#new-clue input#new-clue-col").val(
        $("#context-menu-menu-grid-square").data("trigger-col")
      );
      $("#new-clue select#new-clue-orientation").val("across");
      $("table.word-list tbody td").remove();
      new bootstrap.Modal("#new-clue").toggle();
      $("#new-clue #new-clue-clue").val("");
      $("#new-clue #new-clue-explanation").val("");
      $("#new-clue #new-clue-answer").trigger("focus").val("");
      break;
    case "menu-grid-square-new-clue-down":
      $("#new-clue input#new-clue-row").val(
        $("#context-menu-menu-grid-square").data("trigger-row")
      );
      $("#new-clue input#new-clue-col").val(
        $("#context-menu-menu-grid-square").data("trigger-col")
      );
      $("#new-clue select#new-clue-orientation").val("down");
      $("table.word-list tbody td").remove();
      new bootstrap.Modal("#new-clue").toggle();
      $("#new-clue #new-clue-clue").val("");
      $("#new-clue #new-clue-explanation").val("");
      $("#new-clue #new-clue-answer").trigger("focus").val("");
      break;
    case "menu-grid-square-edit-clue-across":
      // We need a database call to get PlacedClue from the square and orientation
      // Get vars
      var y = $("#context-menu-menu-grid-square").data("trigger-row");
      var x = $("#context-menu-menu-grid-square").data("trigger-col");
      var url =
        root_path +
        "/placed_clue/*/find/" +
        crossword_id +
        "?domain=ajax&orientation=across&x=" +
        x +
        "&y=" +
        y;
      makeAjaxCall("post", url, null, populateEditForm);
      break;
    case "menu-grid-square-edit-clue-down":
      // We need a database call to get PlacedClue from the square and orientation
      // Get vars
      var y = $("#context-menu-menu-grid-square").data("trigger-row");
      var x = $("#context-menu-menu-grid-square").data("trigger-col");
      var url =
        root_path +
        "/placed_clue/*/find/" +
        crossword_id +
        "?domain=ajax&orientation=down&x=" +
        x +
        "&y=" +
        y;
      makeAjaxCall("post", url, null, populateEditForm);
      break;
    case "menu-grid-square-clear-grid-square":
      // Get vars
      var y = $("#context-menu-menu-grid-square").data("trigger-row");
      var x = $("#context-menu-menu-grid-square").data("trigger-col");
      // Now fire off the request
      var url =
        root_path +
        "/grid/*/clear/" +
        crossword_id +
        "?domain=ajax&xMin=" +
        x +
        "&xMax=" +
        x +
        "&yMin=" +
        y +
        "&yMax=" +
        y;
      makeAjaxCall("post", url, null, refreshAll, displayAjaxError);
      break;
    case "menu-grid-square-delete-clue-across":
      // Get vars
      var y = $("#context-menu-menu-grid-square").data("trigger-row");
      var x = $("#context-menu-menu-grid-square").data("trigger-col");
      // Now fire off the request
      var url =
        root_path +
        "/placed_clue/*/delete/?domain=ajax&crossword_id=" +
        crossword_id +
        "&orientation=across&x=" +
        x +
        "&y=" +
        y;
      makeAjaxCall("post", url, null, refreshAll, displayAjaxError);
      break;
    case "menu-grid-square-delete-clue-down":
      // Get vars
      var y = $("#context-menu-menu-grid-square").data("trigger-row");
      var x = $("#context-menu-menu-grid-square").data("trigger-col");
      // Now fire off the request
      var url =
        root_path +
        "/placed_clue/*/delete/?domain=ajax&crossword_id=" +
        crossword_id +
        "&orientation=down&x=" +
        x +
        "&y=" +
        y;
      makeAjaxCall("post", url, null, refreshAll, displayAjaxError);
      break;
    default:
      alert("Not yet implemented!");
      break;
  }
  // Hide menu
  $("#context-menu-menu-grid-square").hide();
}

function setAnswerEntry(
  newValue,
  pcId = null,
  pcOrientation = null,
  startX = 0,
  startY = 0
) {
  newValue = newValue.replaceAll(" ", "?");
  $("#answer-entry").val(newValue);
  if (newValue.length == 0) {
    $("#answer-entry").attr("disabled", "disabled");
    $("#answer-entry").attr("readonly", "readonly");
    $("#answer-entry").data("placed-clue-id", "");
    $("#answer-entry").data("clue-orientation", "");
    $("#answer-entry").data("clue-startX", 0);
    $("#answer-entry").data("clue-startY", 0);
  } else {
    $("#answer-entry").removeAttr("disabled");
    $("#answer-entry").removeAttr("readonly");
    $("#answer-entry").data("placed-clue-id", pcId);
    $("#answer-entry").data("clue-orientation", pcOrientation);
    $("#answer-entry").data("clue-startX", startX);
    $("#answer-entry").data("clue-startY", startY);
    $("#answer-entry")[0].focus();
    $("#answer-entry")[0].select();
  }
}

function handleAnswerKeydown(e) {
  if (e.key === " ") {
    // detect space bar
    e.preventDefault(); // stop the space being added
    const start = this.selectionStart;
    const end = this.selectionEnd;
    const val = this.value;
    this.value = val.slice(0, start) + "?" + val.slice(end);
    this.setSelectionRange(start + 1, start + 1);
    window.setTimeout(() => {
      $(this).trigger("input");
    }, 250);
  }
}

function handleAnswerInput(e) {
  // Get the vars
  let word = $("#answer-entry").val();
  let orientation = $("#answer-entry").data("clue-orientation");
  let startX = $("#answer-entry").data("clue-startX");
  let startY = $("#answer-entry").data("clue-startY");
  // Display letters on grid
  for (var i = 0; i < word.length; i++) {
    switch (orientation) {
      case "down":
        $(`#square-${+startY + +i}-${startX} .letter-holder`).text(
          word[i].toUpperCase().replaceAll("?", " ")
        );
        SolveCache.grid[+startY + +i][+startX] = word[i]
          .toUpperCase()
          .replaceAll("?", " ");
        break;
      case "across":
        $(`#square-${startY}-${+startX + +i} .letter-holder`).text(
          word[i].toUpperCase()
        );
        SolveCache.grid[+startY][+startX + +i] = word[i]
          .toUpperCase()
          .replaceAll("?", " ");
        break;
      default:
        console.log($`unknown clue orientation ${orientation}`);
    }
  }
  // Save letters to db
  SolveCache.saveCrosswordProgressArr(
    crossword_id,
    cols,
    rows,
    SolveCache.grid
  );
}
//#endregion

//#region startup
$(
  // On-load actions here
  function () {
    // Set variables
    rows = $("#crossword-edit tr").length;
    cols = $("#crossword-edit tr").first().children("td").length;

    // Individual actions
    $("td.crossword-grid-square").on("click", toggleSelect);
    $("#print__Trigger").on("click", function () {
      window.print();
    });

    // Refresh data
    refreshGrid();
    refreshClueList();

    // Populate from db / Solvecache.grid

    // Watch for answer entry
    setAnswerEntry("");
    $("#answer-entry").on("input", handleAnswerInput);
    $("#answer-entry").on("keydown", handleAnswerKeydown);

    // Initialise solving cache
    SolveCache.initDb();
  }
);
//#endregion
