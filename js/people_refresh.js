if (typeof peopleGetter === 'undefined') { peopleGetter = {}; }
peopleGetter.url = root_path+"/ajax/get_participants.php?playlist_id="+playlist_id;
peopleGetter.kickUrl = root_path+"/ajax/kick_participant.php?playlist_id="+playlist_id+"&user_id=";

peopleGetter.updatePeopleList = function(data, textStatus, jqXHR) {
    if ('updatePeopleListCustom' in peopleGetter) {peopleGetter.updatePeopleListCustom(data, textStatus, jqXHR); }
}
peopleGetter.ajaxOptions = {
    async: true,
    cache: false,
    success: peopleGetter.updatePeopleList,
    dataType: 'json',
    method: 'GET',
    timeout: peopleGetter.timeout
};
peopleGetter.kickAjaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
};
peopleGetter.getParticipants = function() {
    $.ajax(peopleGetter.url, peopleGetter.ajaxOptions);
    peopleGetter.timer = setTimeout('peopleGetter.getParticipants()',peopleGetter.frequency);
}
peopleGetter.unkickParticipant = function() {
    var uid = $(this).data('user-id');
    $(this).css('cursor','wait');
    $(this).parent().parent().css('cursor','wait');
    $.ajax(peopleGetter.kickUrl+uid+"&kick=false", peopleGetter.kickAjaxOptions);
}
peopleGetter.kickParticipant = function() {
    var uid = $(this).data('user-id');
    $(this).css('cursor','wait');
    $(this).parent().parent().css('cursor','wait');
    $.ajax(peopleGetter.kickUrl+uid+"&kick=true", peopleGetter.kickAjaxOptions);
}
peopleGetter.init = function(initialDelay, frequency, timeout) {
    peopleGetter.initialDelay = initialDelay;
    peopleGetter.frequency = frequency;
    peopleGetter.timeout = timeout;

    $(document).ready(
        function () {
            if (peopleGetter.initialDelay==0) {
                peopleGetter.getParticipants();
            } else {
                peopleGetter.timer = setTimeout('peopleGetter.getParticipants()',peopleGetter.initialDelay);
            }

            // Handle kick-user
            $('#people-table').on('click','td.kick-user a',peopleGetter.kickParticipant);
            // Handle unkick-user
            $('#people-table').on('click','td.unkick-user a',peopleGetter.unkickParticipant);
        }
    );
}