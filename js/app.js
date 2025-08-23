$(document).ready(function () {
  // Initialise all .toast elements on the page
  $(".toast").each(function () {
    var jqToast = $(this);
    var bsToast = new bootstrap.Toast(jqToast[0]); // wrap DOM element
    jqToast.data("bs.toast", bsToast); // store instance for later use
  });
});

function makeToast(
  message,
  messageClass = "warning",
  title = "",
  timestamp = null
) {
  // TODO - HIGH make title bar disappear if title is empty
  // TODO - HIGH make messageClass argument set colour scheme of message (error/warning/info/debug)
  // TODO - MEDIUM enable stacking toasts rather than overwriting each other
  if (timestamp == null) {
    var newDate = new Date();
    timestamp = newDate.toLocaleTimeString();
  }
  $("#toast-main").data("bs.toast").hide();
  $("#toast-title").html(title);
  $("#toast-body-message").html(message);
  $("#toast-timestamp").html(timestamp);
  $("#toast-main").data("bs.toast").show();
}
