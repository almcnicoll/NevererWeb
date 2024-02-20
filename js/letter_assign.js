if (typeof letterAssigner === 'undefined') { letterAssigner = {}; }    

letterAssigner.url = root_path+"/ajax/assign_letters.php?playlist_id="+playlist_id;
letterAssigner.unassignUrl = root_path+"/ajax/unassign_letter.php?letter_id=";

letterAssigner.updateLettersNow = function() {
    // Refresh immediately
    clearTimeout(letterGetter.timer);
    letterGetter.getLetters();
    $("html,html *").css("cursor","auto");
    $(letterAssigner.assignButton).prop('disabled',false);
    
    // Switch tab    
    $('#nav1-content-2').addClass('show active');
    $('#nav1-content-1').removeClass('show active');
    $('#nav1-tab-2').addClass('active');
    $('#nav1-tab-1').removeClass('active');
    $('#nav1-tab-2').attr('aria-selected',"true");
    $('#nav1-tab-1').attr('aria-selected',"false");
}

letterAssigner.ajaxOptions = {
    async: true,
    cache: false,
    dataType: 'json',
    method: 'GET',
    timeout: 4000,
    complete: letterAssigner.updateLettersNow
};

letterAssigner.init = function(assignButton=null,reassignButton=null) {
    letterAssigner.assignButton = assignButton;
    letterAssigner.reassignButton = reassignButton;
    $(document).ready(
        function () {
            // Assign button
            if (letterAssigner.assignButton!=null) {
                $(letterAssigner.assignButton).on('click',function() {
                    $(letterAssigner.assignButton).prop('disabled',true);
                    $(letterAssigner.reassignButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(letterAssigner.url, letterAssigner.ajaxOptions);
                });
            }
            // Reassign button
            if (letterAssigner.reassignButton!=null) {
                $(letterAssigner.reassignButton).on('click',function() {
                    $(letterAssigner.assignButton).prop('disabled',true);
                    $(letterAssigner.reassignButton).prop('disabled',true);
                    $("html, html *").css("cursor","wait");
                    $.ajax(letterAssigner.url+'&from_scratch=true', letterAssigner.ajaxOptions);
                });
            }
            // Unassign letter icons
            $('body').on('click','a.unassign-letter',function() {
                $("html, html *").css("cursor","wait");
                var letter_id = $(this).data('letter-id');
                var unassignUrl = letterAssigner.unassignUrl + letter_id;
                $.ajax(unassignUrl, letterAssigner.ajaxOptions);
            })
        }
    );
}