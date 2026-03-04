function el(id) {return document.getElementById(id);}
 
//$(document).ready(function() {
//    if (el('squarifier')) {
//    	el('squarifier').onmousedown = startDrag;
//    }
//});


function openComments(post_id)
{
	c = document.getElementById('post' + post_id);
	c.style.display = '';
}

function openCommentInput(post_id)
{
	c = el('comment_input' + post_id);
	c.style.display = '';
	
	t = el('textarea' + post_id);
	t.focus();
}

var last_q;
var tm;
var http;
function startPopupSearch()
{
	q = el('q').value;
	if (q && q.length > 2 && q != last_q) {
		http = new httpReq();
		last_q = q;
		http.req.open('get', './q.php?q=' + q, false);
		http.req.send(null);
		t = http.req.responseText;
		if (t) {
			el('popup').style.display = '';
			el('popup_results').innerHTML = t;
		}
	}
	
	tm = setTimeout('startPopupSearch()', 250);

}

function stopPopupSearch()
{
	last_q = '';
	clearTimeout(tm);
	setTimeout('shutOffPopup()', 3000);
}

function shutOffPopup()
{
	el('popup').style.display = 'none';
}

function changeTextarea(s)
{
	$('FORM TEXTAREA').css({'font-size' : s + 'px'});	
}

function showResults()
{
}

function updateStar(r)
{
	el('rating').value = r;
	for (var i = 1; i <= r; i++)
	{
		el('star' + i).src = '/site_img/icons/star_16.png';
	}
	if (r < 5) {
		for (var i = (r + 1); i <= 5; i++)
		{
			el('star' + i).src = '/site_img/icons/star_off16.png';
		}
	}
}

function updateDate(f)
{
	mm = el(f + '_mm').value;
	dd = el(f + '_dd').value;
	yyyy = el(f + '_yyyy').value;
	hh = el(f + '_hh').value;
	ii = el(f + '_ii').value;
	am = el(f + '_am').value;
	
	if (am == 'pm') {hh = parseInt(hh) + parseInt(12);}
	sqlds = yyyy + '-' + mm + '-' + dd + ' ' + hh + ':' + ii + ':00';
	el(f).value = sqlds;
}

var chatTimeout = null;
function startMeeting(mid, s)
{
	// var online = document.getElementById('online');
	// var count = document.getElementById('count');
	// http = new httpReq();
	// var ws = (el('viewer') || el('webshow')) ? '&ws=1' : '';
	// http.req.open("GET", '/meeting.php?id=' + mid + '&s=' + s + ws, false);
	// http.req.send(null);
}

function updateChatWindow(http, mid)
{
	new_comments = http.req.responseText;
	parts = new_comments.split('|||||');
	if (parts[2] !== undefined) {
		if (chatTimeout) {
			clearTimeout(chatTimeout);
		}
		chatTimeout = setTimeout('startMeeting(' + mid + ',' + parts[2] + ')', 5000);
	}
	if (parts[3] !== undefined && parts[3]) {
		html = online.innerHTML;
		html = html + parts[3];
		online.innerHTML = html;
		online.scrollTop = online.scrollHeight;
	}
}

function postChat(mid)
{
	var f = document.getElementsByTagName('FORM');
	var cf = null;
	for (var i = 0; i < f.length; i++)
	{
		if (f[i].comment !== undefined) {
			cf = f[i].comment;
		}
	}
	
	if (!cf) {return false;}
	
	var comment = cf.value;
	var ws = (el('viewer') || el('webshow')) ? '&ws=1' : '';
	cf.value = '';
	http = new httpReq();
	http.req.open("POST", '/meeting.php?id=' + mid + '&p=1' + ws, false);
	http.req.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	http.req.send('comment=' + encodeURIComponent(comment));
	updateChatWindow(http, mid);
}

function httpReq()
{
  	if (window.XMLHttpRequest) {                                   
    	this.req = new XMLHttpRequest();                                  
  	} else {                                                       
    	this.req = new ActiveXObject("Microsoft.XMLHTTP");                
  	}
}

function flagReview(id)
{
	http = new httpReq();
	http.req.open("GET", '/flag_review.php?id=' + id, true);
	http.req.send(null);
	el('review' + id).innerHTML = '(You have flagged this review)';
}

function bumpPost(id)
{
	http = new httpReq();
	http.req.open("GET", '/bump_post.php?id=' + id, false);
	http.req.send(null);
	el('bumps' + id).innerHTML = http.req.responseText;
}

function getOffset(el) 
{
    var left, top;
    left = top = 0;
    if (el.offsetParent) {
        do {
            left += el.offsetLeft;
            top  += el.offsetTop;
        } while (el = el.offsetParent);
    }
    return {
        x : left,
        y : top
    };
}

// Avatar
var startX = 20;
var startY = 20;
var offX = 300;
var offY = 300;

function moveMouse(e)
{
    scrollY = document.documentElement.scrollTop;
    if (scrollY > 0) {
    	document.documentElement.scrollTop = 0;
    	return;
    }
    
    e = e || window.event;
    x = e.clientX - offX;
    y = e.clientY - offY + scrollY;
    
    sq = el('square');

    // Keep it a square
    newX = startX;
    newY = startY;
    if (x < newX) {newX = x;}
    if (y < newY) {newY = y;}
    
    hw = Math.abs(x - startX) > Math.abs(y - startY) ? Math.abs(x - startX) : Math.abs(y - startY);
    if (newX < 0) {newX = 0;}
    if (newY < 0) {newY = 0;}
    if (hw + newX > 360) {hw = 360 - newX;}
    if (hw + newY > 450) {hw = 450 - newY;}
    
    sq.style.left = newX + 'px';
    sq.style.top = newY + 'px';
    sq.style.width = hw + 'px';
    sq.style.height = hw + 'px';
    
    el('x').value = newX;   
    el('y').value = newY;   
    el('hw').value = hw;
}

function startDrag(e)
{
    e = e || window.event;
    
    el('square').style.display = 'block';
    
    offset = getOffset(el('squarifier'));
    offX = offset['x'];
    offY = offset['y'];
    
    startX = e.clientX - offX;
    startY = e.clientY - offY;

    document.onmousemove = moveMouse;
    el('squarifier').onmouseup = endDrag;
    el('squarifier').onmousedown = null;
}

function endDrag(e)
{
    document.onmousemove = null;
    el('squarifier').onmousedown = startDrag;
    el('squarifier').onmouseup = null;
}

var person_number = 0;
function addPicturePerson()
{
	person_number++;
	if (person_number < 8) {
		el('person' + person_number).style.display = 'block';
	}
	if (person_number == 7) {
		el('add_person_button').style.display = 'none';
	}
}
function removePerson(n)
{
	el('person' + n).innerHTML = '';
	el('person' + n).style.display = 'none';
}

function openTraining(vid)
{
	if (!vid) {return;}
	/*jwplayer('playerwindow').setup({
		flashplayer: "/jwplayer/player.swf", 
		file: "/site_video/training" + vid + ".flv",
		height: 360,
		width: 640,
		autostart: true
	});*/
	el('playerwindow').innerHTML = '<iframe width="650" height="380" src="https://www.youtube.com/embed/'+vid+'" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
	el('player_container').style.display = 'block';
}
function closeTraining()
{
	//jwplayer('playerwindow').stop();
	el('playerwindow').innerHTML = '';
	el('player_container').style.display = 'none';
}

function monitorChat(e, mid)
{
	var keycode=null;
	if (e!=null){
		if (window.event!=undefined){
			if (window.event.keyCode) keycode = window.event.keyCode;
			else if (window.event.charCode) keycode = window.event.charCode;
		}else{
			keycode = e.keyCode;
		}
	}
	if (keycode == 13) {postChat(mid);}
}

var show_ended = false;
function monitorNotifications()
{
	var show = (el('viewer') !== null) ? '?show=1' : '';

	s_http = new httpReq();
	s_http.req.open("GET", '/notification_monitor.php' + show, true);
	s_http.req.onreadystatechange = function() {
		if (s_http.req.readyState == 4) {
			param = '';
			cmds = s_http.req.responseText.split('\n');
			var watchers = '';
			for (var i = 0; i < cmds.length; i++)
			{
				param = '';
				res = cmds[i].split('::');
				cmd = res[0];
				if (res.length > 1) {param = res[1];}
				if (cmd == 'doorbell') {
					openNotificationWindow();
					addNotification(param);
					playDoorbellSound();
				}
				if (cmd == 'webshow') {
					var sub = param.split('|');
					var notice = sub[0];
					var webshow_id = sub[1];
					var join_button = ' <a class="button" href="?mode=Purchase&job=purchase&type=WebShow&id=' + webshow_id + '">Join Show</a>';
					var join_link = ' <a href="?mode=Purchase&job=purchase&type=WebShow&id=' + webshow_id + '">Join it now!</a>';
					el('doorbell').innerHTML = join_button;
					openNotificationWindow();
					addNotification(notice + join_link);
				}
				if (cmd == 'watcher') {
					var sub = param.split('|');
					var block_link = ' <a onclick="if (confirm(\'Block this fan for 12 hours?\')) {block(' + sub[1] + ');}">[Block]</a><br/>';
					watchers += sub[0] + block_link;
				}
				if (cmd == 'showstatus' && el('viewer') !== null) {
					if (param == 'expired' && !show_ended) {
						var m = '<br/><br/><br/><br/><p>Your time has expired.  You may purchase additional time for as long as the show is in progress by clicking the button below.</p>';
						m += '<a href="?mode=WebShowModule&job=purchase_show" class="button">Buy Additional Time</a>';
						el('viewer').innerHTML = m;
						var msg = 'Your time has expired!';
						el('timer').innerHTML = msg;
						show_ended = true;
					} else if (param == 'noshow') {
						if (!show_ended) {el('timer').innerHTML = 'The Entertainer is not broadcasting.';}
					} else if (param == 'ended') {
						if (!show_ended) {
							el('timer').innerHTML = 'The Entertainer has left the show!';
							show_ended = true;
						}
					} else {
						if (!show_ended) {el('timer').innerHTML = 'Time Remaining ' + param;}
					}
				}
				if (cmd == 'online') {
					var sub = param.split('|');
					var eid = sub[0];
					var online = sub[1];
					if (online == 'yes') {
						if (el('onlinetxt_' + eid)) {
							var txt = ' - <strong style="color: #6d6;">Online</strong>';
							el('onlinetxt_' + eid).innerHTML = txt;
						}
						if (el('online_' + eid)) {
							el('online_' + eid).className = 'fan_select_link ' + 'online_now';
						}
					} else {
						if (el('onlinetxt_' + eid)) {
							el('onlinetxt_' + eid).innerHTML = '';
						}
						if (el('online_' + eid)) {
							el('online_' + eid).className = 'fan_select_link ';
						}
					}
				}

				if (cmd == 'activity') {
					var sub = param.split('|');
					var area = 'dyn_' + sub[0];
					var count = sub[1];
					if (el(area) !== null) {
						el(area).innerHTML = count;
						el('dyn_' + sub[0]).style.display = (count > 0) ? '' : 'none';
					}
				}


			}
			if (watchers) {
				el('watchers').innerHTML = watchers;
			}
			setTimeout('monitorNotifications()', 5000);
		}
	};
	s_http.req.send();
}

function block(fid)
{
	http = new httpReq();
	http.req.open("GET", '/block.php?fid=' + fid, false);
	http.req.send(null);
}

function ringDoorbell(priv)
{
	if (priv === undefined) {priv = 0;}
	http = new httpReq();
	http.req.open("GET", '/doorbell.php?p=' + priv, false);
	http.req.send(null);
	el('doorbell').innerHTML = 'Your request has been delivered.';
}

function playDoorbellSound()
{
    $("<embed src='/site_audio/doorbell-1.mp3' hidden='true' autostart='true' loop='false' class='playSound'></embed>").appendTo('body');
}

function openNotificationWindow()
{
	el('notification_window').style.display = 'block';
}

function addNotification(msg)
{
	content = el('notification_content');
	html = content.innerHTML;
	html += msg;
	content.innerHTML = html;
	content.scrollTop = content.scrollHeight;
}

var last_state = '';
function unhideStateSelector(st_ab)
{
	if (last_state) {
		el('StateSel_' + last_state).style.display = 'none';
	}
	el('StateSel_' + st_ab).style.display = 'block';
	last_state = st_ab;
}

function addSignatureSlashes(e)
{
	v = e.value;
	if (!v.match(/^\/.+\/$/)) {
		v = '/' + v + '/';
		e.value = v;
	}
}

function setChatSize(s)
{
	$('#online DIV').css({'font-size' : s + 'px'});	
}