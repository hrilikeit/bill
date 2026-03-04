$(document).ready(function () {
    $(document).on("click", '#purchase_charge', function () {
        let fResolution = $('#field_resolution').find('select').val();
        let fPayment = $('#field_payment_method_id').find('select').val();
        $('#resolution_val').val(fResolution);
        $('#payment_method_id_val').val(fPayment);
    });

    $(document).on('change', '.img_add', function (e) {
        if ($("#videoID").attr('src') && $("#videoID").attr('src') != 'undefined'){
            alert('you have already uploaded the video')
        }
        else if (!$("#videoID").attr('src') || $("#videoID").attr('src') == 'undefined'){
            let _this = $(this);
            let fileName = _this.val();
            let idxDot = fileName.lastIndexOf(".") + 1;
            let extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
            if (this.files && this.files[0]) {
                for (var i = 0; i < this.files.length; i++) {
                    $('#post_myImg_multi').append('<img style = "width: 150px;height: 150px;heword-break: break-word;border: 1px solid #bfa9a9; border-radius: 6px; padding: 1px" src=' + URL.createObjectURL(e.target.files[i]) + '>');
                }
            }
            if (['jpg', 'jpeg', 'png'].includes(extFile)) {
                //TO DO
            }
            else {
                alert("Only jpg/jpeg and png files are allowed!");
                _this.val('');
            }
        }
    })

    $(function () {
        $(".multi_add").change(function () {
            if (this.files && this.files[0]) {
                for (var i = 0; i < this.files.length; i++) {
                    var reader = new FileReader();
                    reader.onload = imageIsLoaded;
                    reader.readAsDataURL(this.files[i]);
                }
            }
        });
    });

    function imageIsLoaded(e) {
        $('#myImg_multi').append('<img style="width: 100px;height: 100px; border: 1px solid #bfa9a9; border-radius: 6px; padding: 1px;" src=' + e.target.result + '>');
    };

    $(document).on('change', '.video_add', function (e) {
        if ($('#post_myImg_multi img').length != 0){
            alert('you have already uploaded the image')
        }
        else {
            let _this = $(this);
            let fileName = _this.val();
            let idxDot = fileName.lastIndexOf(".") + 1;
            let extFile = fileName.substr(idxDot, fileName.length).toLowerCase();
            $("#videoID").attr('src', URL.createObjectURL(e.target.files[0]))
            if (extFile == "mov" || extFile == "mp4" || extFile == "avi") {

            }
            else {
                alert("Only mov/mp4/avi files are allowed!");
                _this.val('');
            }
        }
    })

    var elms = document.getElementsByClassName('splide');

    for (var i = 0; i < elms.length; i++) {
        new Splide(elms[i]).mount();
    }

    $(document).on("click", '.medium_avatar', function (e) {
        e.preventDefault()
        if ($(".fan_open")[0]) {
            $(".fan_open")[0].click()
        }
    });

    // $(".switch_account").on('change', function () {
    //     if ($(this).is(':checked')) {
    //         switchStatus = $(this).is(':checked');
    //         $('.join_page_class h1').text('Sign Up Now as Creator');
    //         $('.join_page_class p').hide();
    //         $('input[name=\'type\']').val('Entertainer');
    //     }
    //     else {
    //         switchStatus = $(this).is(':checked');
    //         $('.join_page_class h1').text('Sign Up Now as Fan');
    //         $('.join_page_class p').show();
    //         $('input[name=\'type\']').val('Fan');
    //     }
    // });


    $('.checkbox_fan').prop('checked', true);
        $('.checkbox_fan').on('change', function() {
            if ($(this).is(':checked')) {
                $('.join_page_class h1').text('Sign Up Now as Fan');
                $('.join_page_class p').show();
                $('input[name="type"]').val('Fan');

                if ($('.checkbox_creator').is(':checked')) {
                    $('.checkbox_creator').prop('checked', false);
                }
            } else {
                $(this).prop('checked', true);
            }
        });

        $('.checkbox_creator').on('change', function() {
            if ($(this).is(':checked')) {
                $('.join_page_class h1').text('Sign Up Now as Creator');
                $('.join_page_class p').hide();
                $('input[name="type"]').val('Entertainer');

                if ($('.checkbox_fan').is(':checked')) {
                    $('.checkbox_fan').prop('checked', false);
                }
            } else {
                $(this).prop('checked', true);
            }
        });




    $(document).on('change', '#plane_select', function () {
        if (this.value === 'custom') {
            $('.plan_input').css('opacity', 1);
        }
        else {
            $('.plan_input').css('opacity', 0);
            $('#plan_input_val_hidden').val(this.value);
        }
    })

    $(document).on('submit', '.profile', function () {
        if ($('#plane_select').val() == 0 || $('#plane_select').val() == 4.97) {
            //TODO
        }
        else if ($('#plane_select').val() == 'custom') {
            var plVal = $('#plan_input_val').val();
            $('#plan_input_val_hidden').val(plVal);
        }
    });

    $(document).on('change', '#dollar_select', function () {
        if(this.value == 'sale' || this.value == 'shop'){
            $('.dollar_input').css('display', 'block');
            $('#dollar_input_val').prop('required', true);
            if (this.value == 'shop'){
                $('#btn_photo').prop('disabled', true);
                $('#btn_photo').css('opacity', '0.5');
            }else {
                $('#btn_photo').prop('disabled', false);
                $('#btn_photo').css('opacity', '1');
            }
        }
        else{
            $('#dollar_input_val').prop('required', false);
            $('.dollar_input').css('display', 'none');
            $('#btn_photo').prop('disabled', false);
            $('#btn_photo').css('opacity', '1');
        }
        $('#dollar_input_val').val(this.value);
    })

    $('.dollar_post_container').click(function (){
        imgSrc = $('#post_myImg_multi img').attr('src');
        if (imgSrc){
            $('#select2-dollar_select-results li:last').css('opacity', '0.5');
            $('#select2-dollar_select-results li:last').prop('disabled', true);
        }
        else {
            $('#select2-dollar_select-results li:last').prop('disabled', false);
            $('#select2-dollar_select-results li:last').css('opacity', '1');
        }
    })

    /*
    modal start -----------
    */
    let modal = document.getElementById('myModal_delete');

    $(document).on('click', '.delete_acc', function () {

        modal.style.display = "block";

    })

    // Get the <span> element that closes the modal
    $(document).on('click', '.close_delete', function () {
        modal.style.display = "none";
    })

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }


    $('#submitRequest').on('click', function (e) {
        e.preventDefault();
        const messageBody = $("#messageBody").val();
        $('.modal-header').html("<h2 style='text-align: center'>Support will confirm when deletion is complete</h2>");
        $('#textForm').css('display', 'none');
        $('#requestModalFooter').css('display', 'none');

        fetch('?mode=EntertainerProfile&job=delete_acc', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `messageBody=${encodeURIComponent(messageBody)}`,
        })
            .then((response) => {
                if (response.ok) {
                    console.log("Form submitted successfully!");
                } else {
                    console.error("Failed to submit the form.");
                }
            })

        setTimeout(() => {
            location.reload();
            // window.location.href = '?mode=EntertainerProfile&job=delete_acc';
        }, 5000);


    });

    // closeBtn.addEventListener('click', function () {
    //     modalWindow.style.display = 'none';
    // });
    /*modal end------------------*/

    // Select modal
    var mpopup = document.getElementById('mpopupBox');

    // Select trigger link
    var mpLink = document.getElementById("mpopupLink");
    // Select close action element
    var close = document.getElementsByClassName("close")[0];

    // Open modal once the link is clicked

    if (mpLink) {
        mpLink.onclick = function () {
            event.stopPropagation();
            mpopup.style.display = "block";
            var selectLength = $('select[name="payment_method_id"]').find("option").length;
            if (selectLength == 0) {
                $('#field_payment_method_id').closest('form').find('div').last().remove();
            }
        };

        // Close modal once close element is clicked
        close.onclick = function () {
            mpopup.style.display = "none";
        };

        document.addEventListener('click', function(event) {
            const modalContent = mpopup.querySelector('.modal-content');
            if (mpopup.style.display === 'block' && !modalContent.contains(event.target)) {
                mpopup.style.display = 'none';
            }
        });
    }

    // Select modal
    var unspopup = document.getElementById('unsubscribepopupBox');

    // Select trigger link
    var unsLink = document.getElementById("unsubscribepopup");
    // Select close action element
    var close = document.getElementsByClassName("close_unsubscribe")[0];

    // Open modal once the link is clicked

    if (unsLink) {
        unsLink.onclick = function () {
            unspopup.style.display = "block";
            var selectLength = $('select[name="payment_method_id"]').find("option").length;
            if (selectLength == 0) {
                $('#field_payment_method_id').closest('form').find('div').last().remove();
            }
        };
        // Close modal once close element is clicked
        close.onclick = function () {
            unspopup.style.display = "none";
        };
        // Close modal when user clicks outside of the modal box
        window.onclick = function (event) {
            if (event.target == unspopup) {
                unspopup.style.display = "none";
            }
        };
    }

    if (document.URL.includes('&type=Entertainer&id=')) {
        $('#content').addClass('entertainer_ID');
    }

    if (document.URL.includes('?mode=Login&job=forgot_pw')) {
        $('.container_19').addClass('forgot_pw');
    }

    if (document.URL.includes('?mode=Login&job=join')) {
        $('.container_19').addClass('_login');
    }

    if (document.URL.includes('?mode=Login&job=new_member')
        || document.URL.includes('?mode=Login&job=create-google&type=Fan')
        || document.URL.includes('?mode=Login&job=create-google&type=Entertainer')
        || document.URL.includes('?mode=Login&job=post_forgot_pw')) {
        $('.container_19').addClass('new_member');
    }

    $('select').each(function () {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            width: "100%",
        });
    });

    $('#dollar_select').each(function () {
        $(this).select2({
            minimumResultsForSearch: Infinity,
            width: "100%",
            dropdownCssClass: "test"
        });
    });

    $(document).on('click', '#post_submit', function () {
        let imgAdd = $('.img_add').val();
        let videoAdd = $('.video_add').val();
        let postText = $('#post_text').val();
        if (!imgAdd && !videoAdd) {
            if (!postText){
                alert('post fild required')
            }
        }
        $('#main_comment').submit();
    });

    if (document.URL.includes('?mode=EntertainerProfile')) {
        var messageEle = document.getElementById('post_text');
        var counterEle = document.getElementById('counter');

        if (messageEle) {
            messageEle.addEventListener('input', function (e) {
                var target = e.target;
                // Get the `maxlength` attribute
                var maxLength = target.getAttribute('maxlength');
                // Count the current number of characters
                var currentLength = target.value.length;
                counterEle.innerHTML = `${currentLength}/${maxLength}`;
            });
        }
    }

    if (document.URL.includes('?mode=EntertainerGallery&job=add') || document.URL.includes('?mode=EntertainerGallery&job=add_video')) {
        var nameEle = $('input.name_count');
        var ctEle = $('.counter');;
        nameEle.attr('maxlength', '60');
        nameEle.on('keyup', function (e) {
            var target = e.target;
            // Get the `maxlength` attribute
            var maxLength = target.getAttribute('maxlength');
            // Count the current number of characters
            var currentLength = target.value.length;
            ctEle.html(`${currentLength}/${maxLength}`);
        })
    }

    $('#formFile1').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });
    $('#formFile2').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });
    $('#formFile3').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });

    $("#show_type_container").change(function() {
        let selectedVal = $("#show_type_container option:selected").val();
        if(selectedVal === '1') {
            $(".show_type_container ").addClass("d_none");
            $('.fan-select-checked').removeAttr('required');
            $('#start-show-form-button').text('Go To Subscribers Show');
        }else if(selectedVal === '0') {
            $(".show_type_container ").removeClass("d_none")
            $('.fan-select-checked').attr("required", true);
            $('#start-show-form-button').text('Go To Private Show');
        }
        else if(selectedVal === '2') {
            $(".show_type_container ").addClass("d_none")
            $('.fan-select-checked').removeAttr("required");
            $('#start-show-form-button').text('Go To Public Show');
        }
    });

    $(document).on('change', '#fileInput', function (){
        $( "#avatarForm" ).trigger( "submit" );
    })

    $(document).on('change', '#fileInputDisplay', function (){
        $( "#displayForm" ).trigger( "submit" );
    })

    $('#signed_1').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });

    $('#signed_2').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });

    $('#signed_3').change(function () {
        let numfiles = $(this)[0].files.length;
        let parent = $(this).closest('.input-file');
        parent.find('ins').remove();
        for (i = 0; i < numfiles; i++) {
            parent.append('<ins>' + $(this)[0].files[i].name + '</ins>')
        }
    });

    $(document).on('click', '#myBtnBio', function (){
        $('#myModalBio').show();
        $('#myModalBio').css('display', 'flex');
    })

    $(document).on('click', '.close_bio_modal', function (){
        $('#myModalBio').hide();
    })

    $(document).on("contextmenu",function(){
        return false;
    });

    var modalGoogle = document.getElementById("myModalGoogle");
    var spanGoogle = document.getElementsByClassName("close")[0];
    var showModal = $('#myModalGoogle').hasClass('showModal')
    if (showModal){
        modalGoogle.style.display = "block";
    }
    if (spanGoogle){
        spanGoogle.onclick = function() {
            modalGoogle.style.display = "none";
        }
    }
    window.onclick = function(event) {
        if (event.target == modalGoogle) {
            modalGoogle.style.display = "none";
        }
    }

    if(!$('.quick_links').length){
        $('.left-section').prepend(`<div class="quick_links">
            <a class="event_class">
                <img border="0" src="/site_img/icons/20.png" alt="" height="16" width="16">
                    Events
            </a>
        </div>`)
    }

    $('.event_class').removeAttr('href');
    $(document).on('click', '.event_class', function (){
        $('#myModalEvent').show('modal');
    })
    $(document).on('click', '.close-event', function (){
        $('#myModalEvent').hide('modal');
    })

    var swiper = new Swiper(".mySwiper", {
        slidesPerView: 1,
        loop:true,
        spaceBetween: 10,
        pagination: {
            el: ".swiper-pagination",
            clickable: true,
        },
        navigation: {
            nextEl: ".button-next",
            prevEl: ".button-prev",
        },
    });

    $(document).on('click', '.public_live_show', function (){
        let _this = $(this);
        _this.closest('form').submit();
    })

    if ($('.blue_subscription').prop("disabled") == false){
        $('.blue_subscription').addClass('blue_subscription_style');
    }

    $('.id_verify').click(function (){
        if ($('#formFile1').get(0).files.length == 0){
            alert('Front of Photo ID no files selected');
        }
        if ($('#formFile2').get(0).files.length == 0){
            alert('Back of Photo ID no files selected');
        }
        if ($('#formFile3').get(0).files.length == 0){
            alert('Headshot of you holding your ID no files selected');
        }
    })

    $("input[name=birth_date_fmt]").attr('required', true);
    $("input[name=address]").attr('required', true);
    $("input[name=displayPhoto]").attr('required', true);

    if (document.URL.includes('?mode=EntertainerGallery&job=add_video_store')){
        $('form').attr('id', 'video_store_form');
    }
    $('#loader').hide();
    $('#video_store_form').submit(function (){
        $('#loader').show();
    })

    $('.start_show_form input[type=submit]').click(function (){
        if ($('.goal_checkbox').is(":checked")){
            $('.goal_input').val(1);
        }
        else {
            $('.goal_input').val(0);
        }
    })

    var modalPayment = document.getElementById("payment_modal");
    var spanPayment = document.getElementsByClassName("close")[0];
    $(document).on('click', '.buy-button', function (){
        modalPayment.style.display = "block";
    })
    if (spanPayment){
        spanPayment.onclick = function() {
            modalPayment.style.display = "none";
        }
    }
    window.onclick = function(event) {
        if (event.target == modalPayment) {
            modalPayment.style.display = "none";
        }
    }
});

/* Disable right click context menu */

function uploadIcon() {
    $('#fileInput').click();
}

function uploadDisplay() {
    $('#fileInputDisplay').click();
}

function postFilePhoto() {
    $('#post_file_photo').click();
}

function postFileVideo() {
    $('#post_file_video').click();
}

function topFunction() {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}

/* Dropdown video/photo */

$(document).ready(function(){
    $("#btn_video").click(function(){
        $("#dropdown_video").toggleClass("d_none");
        !$("#dropdown_photo").hasClass("d_none") ? $("#dropdown_photo").addClass("d_none") : "";
    });

    $("#btn_photo").click(function(){
        $("#dropdown_photo").toggleClass("d_none");
        !$("#dropdown_video").hasClass("d_none") ? $("#dropdown_video").addClass("d_none") : "";
    });

});