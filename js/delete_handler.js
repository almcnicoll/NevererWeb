var deleteHandler = {};
deleteHandler.idToDelete = null;

deleteHandler.reportOnDelete = function (jqXHR, textStatus) {
  // Handle callback
  $("html,html *").css("cursor", "auto"); // Put cursor back
  $("#deleteModalCloseX").trigger("click"); // Close modal
  top.location.reload(); // Reload page
};

deleteHandler.deleteCrossword = function (local) {
  // Check we've specified an id
  if (deleteHandler.idToDelete == null) {
    alert("Could not delete crossword. Please try again later.");
    return;
  }
  deleteHandler.url =
    "~ROOT~/crossword/*/delete?domain=ajax&crossword_id=" +
    deleteHandler.idToDelete;
  deleteHandler.ajaxOptions = {
    async: true,
    cache: false,
    dataType: "json",
    method: "POST",
    timeout: 10000,
    complete: deleteHandler.reportOnDelete,
    data: {
      deleteLocal: local,
    },
  };
  $("html, html *").css("cursor", "wait"); // Wait cursor
  $.ajax(deleteHandler.url, deleteHandler.ajaxOptions);
};

$(document).ready(function () {
  $("#deleteHere").click(function () {
    deleteHandler.deleteCrossword();
  });

  // When modal is about to show
  $("#crosswordDeleteModal").on("show.bs.modal", function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var id = button.data("id"); // Extract info from data-* attributes

    // Store the id
    deleteHandler.idToDelete = id;
  });
});
