getModels()

function getModels(page = 1) {
    $.ajax({
        url: '?mode=Api&job=get_all_models',
        type: 'GET',
        data: {
            memberId: memberId,
            page: page,
        },
        dataType: 'json',
        success: function(response) {
            var dataResponse = JSON.parse(response);
            pagination(dataResponse);
        },
        error: function(xhr, status, error) {
            console.error(xhr.responseText);
        }
    });
}

function pagination(response) {
    models = response.data;
    currentPage = response.page;
    countPage = response.pages;

    makeModel(models);

    var htmlCode = '<div style="text-align: center">';

    htmlCode += '<button ' + (currentPage == 1 ? 'disabled ' : '') +'onclick="getModels(' + (currentPage - 1) + ')">Previous Page</button>';

    if (currentPage > 3) {
        htmlCode += ' <button class="active" onclick="getModels(1)">1</button>...';
    }
    for (var i = (currentPage - 2); i <= (currentPage + 2); i++) {
        if ( i > 0 && i<= countPage) {
            htmlCode += '<button class="active" ' + (currentPage == i ? 'style="color: red;"' : '') + ' onclick="getModels(' + i + ')">' + i + '</button>';

        }
    }

    if (currentPage < (countPage - 2)) {
        htmlCode += '...<button class="active" onclick="getModels(' + countPage + ')">' + countPage +'</button> ';
    }
    htmlCode += '<button ' + (currentPage == countPage ? 'disabled ' : '' ) +'onclick="getModels(' + (currentPage + 1) + ')">Next Page</button>';
    htmlCode += '</div>'

    $("#pagination").html(htmlCode);

}

function makeModel(models) {

    var htmlCode = '';

    models.forEach(model => {
        htmlCode += '<div class="avatar_link">' + model.name +
        '<br>' +
        '<a href="?mode=EntertainerProfile&amp;entertainer_id=' + model.id + '">' +
            '<img src="/entertainerAvatar.php?a=' + model.member_id +'" class="little_avatar" alt="" border="0">' +
            '</a>' +
            '</div>';
    });


    $("#creators").html(htmlCode);
}
