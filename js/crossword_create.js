function verifyRowsCols() {
    if($('#rotational_symmetry_order').val().toString() == '4') {
        // Ensure cols and rows are equal
        switch($(document.activeElement).attr('id')) {
            case 'rows':
                // Match cols to rows
                $('#cols').val($('#rows').val());
                break;
            case 'cols':
                // Match rows to cols
                $('#rowss').val($('#cols').val());
                break;
            default:
                // Match cols to rows
                $('#cols').val($('#rows').val());
                break;
        }
    }
}

$(document).ready(
    function() {
        $('#rotational_symmetry_order').on('change',verifyRowsCols);
        $('#rows,#cols').on('change',verifyRowsCols);

        $('#title').focus();
    }
);