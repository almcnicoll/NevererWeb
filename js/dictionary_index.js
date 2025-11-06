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
// #region DOM ready
$(function () {
  $(".subscribe,.unsubscribe").on("click", ToggleSubscribe);
});
// #endregion
