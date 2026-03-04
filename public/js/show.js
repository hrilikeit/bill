function startShow(f) {
    var formData = new FormData($("#start_show_form")[0]);

     if (validateShow(formData)) {
         $.ajax({
                 type: "POST",
                 url: "startshow.php",
                 data: formData,
                 processData: false,
                 contentType: false,
                 success: function (response) {
                      var url = JSON.parse(response).url;
                     // window.open(url);
                     window.location.href = url;
                 }
             });
     }
}

function joinShow(f) {
    var formData = new FormData($("#join_show_form")[0]);
    $.ajax({
        type: "POST",
        url: "joinshow.php",
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            var url = JSON.parse(response).url;
            // window.open(url);
            window.location.href = url;
        }
    });
}

function validateShow(formData) {
    var showType = formData.get('show_type');
    var fanID = formData.get('fan_id');

    if (showType == 0 && fanID == '') {
        alert('Please select Fan')
        return false;
    }

    return true;
}