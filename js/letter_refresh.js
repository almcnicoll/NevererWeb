if (typeof letterGetter === 'undefined') { letterGetter = {}; }
letterGetter.letterHash = '';
letterGetter.search_letter = '';
letterGetter.letter_id = null;
letterGetter.timer = null;
letterGetter.url = root_path+"/ajax/get_letters.php?playlist_id="+playlist_id;
letterGetter.updateLetters = function(data, textStatus, jqXHR) {
    // Check hash
    if (data.hash == letterGetter.letterHash) { return; } // Result's the same - don't bother doing anything
    letterGetter.letterHash = data.hash;

    if ('updateLettersCustom' in letterGetter) { letterGetter.updateLettersCustom(data, textStatus, jqXHR); }
}
letterGetter.ajaxOptions = {
    async: true,
    cache: false,
    success: letterGetter.updateLetters,
    dataType: 'json',
    method: 'GET',
    timeout: letterGetter.timeout
};
letterGetter.getLetters = function() {
    $.ajax(letterGetter.url, letterGetter.ajaxOptions);
    letterGetter.timer = setTimeout('letterGetter.getLetters()',letterGetter.frequency);
}
letterGetter.init = function(initialDelay, frequency, timeout) {
    letterGetter.initialDelay = initialDelay;
    letterGetter.frequency = frequency;
    letterGetter.timeout = timeout;

    $(document).ready(
        function () {
            if (letterGetter.initialDelay==0) {
                letterGetter.getLetters();
            } else {
                letterGetter.timer = setTimeout('letterGetter.getLetters();',letterGetter.initialDelay); // Give it a little offset
            }
        }
    );
}