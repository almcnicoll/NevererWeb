//#region jQuery DOM ready
$(function () {
  // Transfer data from PHP scripts
  transferData();
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
  // Handle mobile behaviour on any letter-input fields
  $("input.no-mobile-auto, textarea.no-mobile-auto").attr({
    autocapitalize: "off",
    autocorrect: "off",
    spellcheck: "false",
  });

  // Handle copy buttons
  $(".copy-button").on("click", function () {
    let target = $(this).data("copy-source");
    // Select text inside element
    let copyText = $("#" + target).val();
    // Copy text to clipboard
    copyToClipboard(copyText);
  });
});
//#endregion

function copyToClipboard(text) {
  // Modern browsers
  if (navigator.clipboard && navigator.clipboard.writeText) {
    return navigator.clipboard
      .writeText(text)
      .then(() => makeToast("Copied to clipboard!", "success"))
      .catch(() => fallbackCopy(text));
  } else {
    fallbackCopy(text);
  }
}
function fallbackCopy(text) {
  // Older browsers
  const temp = $("<input>").val(text).appendTo("body").select();
  try {
    document.execCommand("copy");
    makeToast("Copied to clipboard!", "success");
  } catch (e) {
    makeToast("Copy failed!", "error");
  }
  temp.remove();
}

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

function transferData() {
  let transferScripts = $("script.data-transfer");
  transferScripts.each(function () {
    let jsonData = $(this).text();
    let scope = $(this).data("scope") ?? "window";
    if (scope !== "window") {
      if (window[scope] === undefined) {
        window[scope] = {};
      }
    }
    let data = JSON.parse(jsonData);
    for (var varName in data) {
      switch (scope) {
        case "window":
          // Window scope
          window[varName] = data[varName];
          break;
        default:
          // Scope inside a variable
          window[scope][varName] = data[varName];
          break;
      }
    }
  });
}
