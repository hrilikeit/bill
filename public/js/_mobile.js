$(document).ready(function () {
    $("#btn_mobile_right_menu").click(function () {
        $(".mobile_userinfo").toggleClass('mobile_userinfo_hidden');
        $("#mobile_left_menu").addClass('mobile_mobile_info_hidden');
    });
    $("#btn_mobile_left_menu").click(function () {
        $("#mobile_left_menu").toggleClass('mobile_mobile_info_hidden');
        $(".mobile_userinfo").addClass('mobile_userinfo_hidden');
    });

    let text = document.getElementsByClassName('mobile_userinfo');
    let new_text = text[0].innerHTML.split('|').join('')
    text = text[0].innerHTML = new_text;
});

