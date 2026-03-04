	var l_q;
var tm;
function startAdminSearch()
{
	var q = document.getElementById('q').value.toLowerCase();
	if (q != l_q && (q.length > 2 || q == '')) {
		l_q = q;
		trs = document.getElementsByTagName('tr');
		var found;
		for (var i = 0; i < trs.length; i++)
		{
			found = false;
			if (q) {
				tds = trs[i].getElementsByTagName('td');
				for (var j = 0; j < tds.length; j++)
				{
					var c = tds[j].textContent.toLowerCase();
					if (c.indexOf(q) > -1) {
						found = true;
					}
				}
			} else {
				// Show all items if no
				found = true;
			}
			trs[i].style.display = (found ? '' : 'none');
		}
	}
	tm = setTimeout('startAdminSearch()', 600);
}

function endAdminSearch()
{
	clearTimeout(tm);
}

$(document).ready(function(){
	$("#select_approved_2257").change(function(){
		$(".approved_2257_div").hide();
		$("#"+$(this).val()).show();
	});

	$(".tbEntertainers").sortable({
		items: 'tr',
		cursor: 'pointer',
		axis: 'y',
		dropOnEmpty: false,
		start: function (e, ui) {
			ui.item.addClass("selected");
		},
		stop: function (e, ui) {
			ui.item.removeClass("selected");
			let orderData = [];
			$(this).find("tr").each(function (index) {
				if (index > 0) {
					$(this).find("td").eq(2).html(index);
				}
				orderData.push($(this).attr('data-id'))
			});
			$.post('/admin.php?mode=Administrator&job=entertainerOrder', {orderData}, function(data){

			});
		}
	});

	const currentUrl = window.location.href;
	if (currentUrl.indexOf('admin.php') > 0) {
		document.querySelector('#btn_mobile_right_menu').style.display = 'none';
		document.querySelector('#mobile_left_menu').style.display = 'none';
		document.querySelector('#btn_mobile_left_menu').style.display = 'none';
		document.querySelector('#mobile_right_menu').style.display = 'none';
	}

	$(document).on('change', '#individuals_0', function (){
		let _this = $(this);
		if(_this.is(":checked")){
			$('#field_recipients').find('input[type=checkbox]').prop('disabled', true);
			$('#field_delivery').find('input[type=checkbox]').prop('disabled', true);
			$('.all_members').prop('disabled', false);
		}
		else {
			$('#field_recipients').find('input[type=checkbox]').prop('disabled', false);
			$('#field_delivery').find('input[type=checkbox]').prop('disabled', false);
			$('.all_members').prop('disabled', true);
		}
	})
});


$(document).ready(function () {
	$(".all_members").select2();
});

function arhiveFile(userId) {
	var  fileName = userId + '.zip';
	$.ajax({
		type: "POST",
		url: "admin_files.php",
		data: {id: userId, job: 'archive'},
		success: function (response) {
			downloadFile("admin_files.php", userId);
		}
	});
}

function deleteFile(userId) {
	var result = window.confirm("Are you sure you want to permanently delete the files?");

	if (result) {
		$.ajax({
			type: "POST",
			url: "admin_files.php",
			data: {id: userId, job: 'delete'},
			success: function (response) {
				alert('Delete files successfully');
			}
		});
	}
}

function downloadFile(url, fileName) {
	var link = document.createElement('a');
	link.href = url + "?id=" + fileName;
	link.download = fileName;
	document.body.appendChild(link);
	link.click();

	document.body.removeChild(link);
}

/*
   modal start -----------
   */

	function openModalDeleteAccept() {
		$('#myModal_delete_accept').css('display', 'block')
	}
// let modal = document.getElementById('myModal_delete_accept');
//
// // $(document).on('click', '.delete_accept', function () {
// //
// // 	modal.style.display = "block";
// // })
//
// 	$('.delete_accept').on('click', function () {
// 		modal.style.display = "block";
// 	});

// let modal = document.getElementById('myModal_decline');
//
// $(document).on('click', '.delete_acc', function () {
//
// 	modal.style.display = "block";
// })
//
// // Get the <span> element that closes the modal
// $(document).on('click', '.close_delete', function () {
// 	modal.style.display = "none";
// })
//
// // When the user clicks anywhere outside of the modal, close it
// window.onclick = function (event) {
// 	if (event.target == modal) {
// 		modal.style.display = "none";
// 	}
// }
/*modal end------------------*/