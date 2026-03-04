$(document).ready(function() {
	$("#field_creator_to_fan .creator_to_fan").keyup(function() {
		if($.trim($(this).val()) != ''){
		$.ajax({
			type: "GET",
			url: "/getStage.php",
			data: 'keyword=' + $.trim($(this).val()),
			beforeSend: function() {
				$("#field_creator_to_fan .creator_to_fan").css("background", "#FFF url(../site_img/LoaderIcon.gif) no-repeat 165px");
			},
			success: function(data) {
				if($("#field_creator_to_fan").find("#suggesstion-box").length > 0){
					$("#field_creator_to_fan #suggesstion-box").html(data).show();
				}else{
					$("#field_creator_to_fan .creator_to_fan").after("<div id='suggesstion-box'>"+data+"</div>");
					$("#field_creator_to_fan").append("<input type=\"hidden\" name=\"entertainer_id\" id=\"selected_entertainer\" value=\"\">");
				}
				$("#field_creator_to_fan .creator_to_fan").css("background", "#FFF");
				$("#country-list").on('click','li', function(){
					console.log($(this));
					$("#field_creator_to_fan .creator_to_fan").val($(this).html());
					$("#selected_entertainer").val($(this).attr("en_id"));
					$("#suggesstion-box").hide();
				})
			}
		});
	}
	});
});