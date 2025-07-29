// File: js/dict_master.js

/**
 * Main thread controller for dictionary sync
 * Listens to worker messages and performs AJAX using makeAjaxCall()
 */

var worker_path = "~ROOT~/js/dict_worker.js";
const worker = new Worker(worker_path);

worker.onmessage = function (e) {
    const msg = e.data;

    if (msg.type === "fetch") {
        var url = msg.data.url;
        makeAjaxCall(
            msg.method,
            '~ROOT~/'+url+'?domain=ajax',
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

$(document).ready( function() {
    worker.postMessage({ type: 'startSync', root_path: root_path });
});