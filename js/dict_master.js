// File: js/dict_master.js

/**
 * Main thread controller for dictionary sync
 * Listens to worker messages and performs AJAX using makeAjaxCall()
 */

// TODO - HIGH fix the path issues here (NB index.php current logging all requests to error_log to debug)
// dict_worker.js is beingh requested from server as /neverer-web/crossword/edit/js/dict_worker.js
// which clearly doesn't work
var curr_path = window.location.href;
var sub_path = curr_path.replace(root_path,"");
var levels = (sub_path.match(/\//g) || []).length;
var up_level = "../";
var up_levels = up_level.repeat(levels);
var worker_path = up_level.repeat(levels)+"js/dict_worker.js";
const worker = new Worker(worker_path);

worker.onmessage = function (e) {
    const msg = e.data;

    if (msg.type === "fetch") {
        makeAjaxCall(
            msg.method,
            root_path + "/tome/*/list?domain=ajax",
            msg.data,
            function (response) {
                worker.postMessage({
                    type: "fetched",
                    id: msg.id,
                    success: true,
                    payload: response
                });
            },
            function (error) {
                worker.postMessage({
                    type: "fetched",
                    id: msg.id,
                    success: false,
                    payload: error
                });
            }
        );
    }
};

worker.postMessage({ type: 'startSync', root_path: root_path });