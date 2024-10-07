/**
 * 
 * @param {string} message the message to print to the debug pane 
 * @param {string} category the category of the message (intended values: success, error, warning, info)
 */
debugPane.print = function(message, category = 'info') {
    // Set div class
    var messageClass;
    switch (category.toLowerCase()) {
        case 'success':
            messageClass = 'success';
            break;
        case 'error':
            messageClass = 'danger';
            break;
        case 'warning':
            messageClass = 'warning';
            break;
        default:
            messageClass = 'info';
            break;
    }
    // Append to div
    $('#debug-info').append("<div class='alert alert-"+messageClass+"'>"+message+"</div>");
    // Scroll down
    $("#debug-info").scrollTop(function() { return this.scrollHeight; });
};
debugPane.clear = function() {
    $('#debug-info').html('');
};