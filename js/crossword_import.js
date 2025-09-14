/*
function import_ready() {
    let dropzone_options = {
        url: "~ROOT~/crossword/*"+"/import?domain=ajax"
    };
    let myDropzone = new Dropzone("div#import-dropzone", dropzone_options);
}

$(document).ready(import_ready);
*/
$(document).ready(function () {
  Dropzone.options.myDropzone = {
    importDropzone: {
      paramName: "import.json",
      maxFilesize: 1, // MB
      init: function () {
        // Add event listeners here if needed
        // this.on("addedfile", file => { alert('x'); });
      },
      accept: function (file, done) {
        // code here
      },
    },
  };
});
