$(document).ready(function () {
  // Initialise all .toast elements on the page
  $(".toast").each(function () {
    var jqToast = $(this);
    var bsToast = new bootstrap.Toast(jqToast[0]); // wrap DOM element
    jqToast.data("bs.toast", bsToast); // store instance for later use
  });
  // Show any that have been produced by PHP
  $(".php-toast").each(function () {
    let toastClass = $(this).data("toast-class") ?? null;
    let toastTitle = $(this).data("toast-title") ?? null;
    let toastTime = $(this).data("toast-time") ?? null;
    let toastText = $(this).text();
    makeToast(toastText, toastClass, toastTitle, toastTime);
  });
});

let toastClasses = {
  warning: "bg-warning",
  error: "bg-danger",
};

function makeToast(
  message,
  messageClass = "warning",
  title = "",
  timestamp = null
) {
  // TODO - MEDIUM enable stacking toasts rather than overwriting each other
  if (timestamp == null) {
    var newDate = new Date();
    timestamp = newDate.toLocaleTimeString();
  }
  $("#toast-main").data("bs.toast").hide();
  $("#toast-title").html(title);
  if (title == "") {
    $("#toast-title").parent().hide();
  } else {
    $("#toast-title").parent().show();
  }
  $("#toast-body-message").html(message);
  $("#toast-timestamp").html(timestamp);
  $("#toast-main").data("bs.toast").show();
  let titleClass = toastClasses[messageClass] ?? toastClasses["warning"];
  $("#toast-title")
    .parent()
    .removeClass()
    .addClass("toast-header")
    .addClass(titleClass);
}

/*
// Utility function: creates and shows a Bootstrap toast
function showToast(message, title = "Notice") {
    // Create a unique ID so multiple toasts can coexist
    const id = "toast-" + Date.now();

    // Toast HTML (Bootstrap 5 structure)
    const $toast = $(`
        <div id="${id}" class="toast align-items-center text-bg-primary border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}:</strong> ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `);

    // Append to container
    $("#toast-container").append($toast);

    // Initialise and show
    const toast = new bootstrap.Toast($toast[0], {
        delay: 4000 // 4 seconds by default
    });
    toast.show();

    // Clean up after hidden
    $toast.on("hidden.bs.toast", function () {
        $(this).remove();
    });
}
*/
