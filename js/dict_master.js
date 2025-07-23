// File: js/dict_master.js

/**
 * Main thread controller for dictionary sync
 * Listens to worker messages and performs AJAX using makeAjaxCall()
 */

const worker = new Worker("js/dict_worker.js");

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
