var templateIdToDelete = null;

$(document).ready(function () {
  $("#templateDeleteModal").on("show.bs.modal", function (event) {
    templateIdToDelete = $(event.relatedTarget).data("id");
  });

  $("#templateDeleteConfirm").on("click", function () {
    $.post(root_path + "/template/*/delete/" + templateIdToDelete + "?domain=ajax")
      .done(function () {
        top.location.reload();
      })
      .fail(function (jqXHR) {
        let message = "Could not delete the template. Please try again later.";
        try {
          message = JSON.parse(jqXHR.responseText).errors || message;
        } catch (e) {}
        bootstrap.Modal.getInstance(document.getElementById("templateDeleteModal")).hide();
        makeToast(message, "error");
      });
  });
});
