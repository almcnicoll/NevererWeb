function ToggleSubscribe(e) {
  e.preventDefault();
  $(e.currentTarget).parent().children().attr("disabled", "disabled");
  let href = e.currentTarget.href;
  let url = new URL(href);
  let params = new URLSearchParams(url.search);
  params.set("domain", "ajax");
  url.search = params;
  $.ajax({
    url: url,
    type: "POST",
    success: (response) => {
      $(e.currentTarget).parent().children().removeAttr("disabled").toggle();
    },
    error: (xhr, status, errorType) => {
      $(e.currentTarget).parent().children().removeAttr("disabled");
      errObj = JSON.parse(xhr.responseText);
      makeToast(
        errObj.errors.join(", "),
        "error",
        "Failed to update subscription"
      );
    },
  });
}

function FlagTome(e) {
  let tome_id = $(e.currentTarget).data("id");
  $("#deleteConfirm").data("id", tome_id);
}

function DeleteTome(e) {
  // Stop link firing
  e.preventDefault();
  // Get tome_id
  let tome_id = $(e.currentTarget).data("id");
  if (tome_id == null) {
    alert("Could not delete dictionary. Please try again later.");
    return;
  }
  // Send request
  let url = "~ROOT~/dictionary/*/delete/" + tome_id + "?domain=ajax";
  let ajaxOptions = {
    async: true,
    cache: false,
    dataType: "json",
    method: "POST",
    timeout: 10000,
    complete: ReportOnTomeDelete,
    data: {
      tome_id: tome_id,
    },
  };
  $("html, html *").css("cursor", "wait"); // Wait cursor
  $.ajax(url, ajaxOptions);
}

function ReportOnTomeDelete(jqXHR, textStatus) {
  // Handle callback
  $("html,html *").css("cursor", "auto"); // Put cursor back
  $("#deleteModalCloseX").trigger("click"); // Close modal
  top.location.reload(); // Reload page
}

function SetDefaultDictionary(e) {
  let tome_id = $(e.currentTarget).attr("value");
  if (tome_id == null) {
    alert("Could not set default dictionary. Please try again later.");
    return;
  }
  $("html, html *").css("cursor", "wait"); // Wait cursor
  // Send request
  let url = "~ROOT~/dictionary/*/setdefault/" + tome_id + "?domain=ajax";
  let ajaxOptions = {
    async: true,
    cache: false,
    dataType: "json",
    method: "POST",
    timeout: 10000,
    complete: ReportOnDefaultDictionarySet,
    data: {
      tome_id: tome_id,
    },
  };
  $("html, html *").css("cursor", "wait"); // Wait cursor
  $.ajax(url, ajaxOptions);
}

function ReportOnDefaultDictionarySet(jqXHR, textStatus) {
  // Handle callback
  $("html,html *").css("cursor", "auto"); // Put cursor back
  top.location.reload(); // Reload page
}

// #region DOM ready
$(function () {
  $(".subscribe,.unsubscribe").on("click", ToggleSubscribe);
  $(".delete").on("click", FlagTome);
  $("#deleteConfirm").on("click", DeleteTome);
  $("input.default-dictionary-radio").on("change", SetDefaultDictionary);
});
// #endregion
