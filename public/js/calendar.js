// Popup Calendar
// (c)2010, Jason Justian

calendars = 0;
function addCalendarField(cid, def, cla)
{
	if (def == '0000-00-00') {def = '';}
	document.write('<input onchange="reformatDate(this);" onblur="hideCalendar(\'' + cid + '\');" type="text" size="10" maxlength="10" class="' + cla + '" name="' + cid + '" id="' + cid + '" value="' + def + '" />');
	document.write('<a href="#" onclick="showCalendar(\'' + cid + '\'); return false;">');
	document.write('<img src="/site_img/icons/calendar.png" title="Select Date" alt="Select Date" border="0" /></a>');
	if (!calendars) {
		document.write('<div id="popup_cal" style="display:none; position: absolute;"></div>');
		calendars++;
	}
}

function calendarHTML(cid, y, m)
{
	var h = '';

    dpm = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
    if (y % 4 == 0 && ((y % 100 > 0) || (y % 400 == 0))) {dpm[1] = 29;}
    mn = new Array('Jan', 'Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');
    m = m - 0;

	// Info about today
    d = new Date();
    this_y = d.getFullYear();
    this_m = d.getMonth() + 1;
    this_d = d.getDate();

	// Info about the current field value
	def_y = def_year(cid);
	def_m = def_month(cid);
	def_d = def_day(cid);
        	
	h += '<table cellspacing="0" cellpadding="0" border="0">\n';
	if (y != this_y || m != this_m) {
		h += '<td><a href="#" onclick="drawCalendar(\'' + cid + '\',' + this_y + ',' + this_m + '); return false;")><img src="/site_img/icons/home.png" border="0" title="Go to ' + mn[this_m - 1] + ' ' + this_y + '" alt="Go to Today" /></a></td>';
	} else {
		h += '<td>&nbsp;</td>';
	}
	h += '<td><a href="#" onclick="drawCalendar(\'' + cid + '\',' + y + ',' + (m - 1) + '); return false;")><img src="/site_img/icons/left.png" border="0" title="Previous Month" alt="Previous Month" /></a></td>';
	h += '<td colspan="3" align="center">' + mn[m-1] + ' ' + y + '</td>';
	h += '<td><a href="#" onclick="drawCalendar(\'' + cid + '\',' + y + ',' + (m + 1) + '); return false;")><img src="/site_img/icons/right.png" border="0" title="Next Month" alt="Next Month" /></a></td>';
	h += '<td><a href="#" onclick="hideCalendar(\'' + cid + '\'); return false;"><img src="/site_img/icons/cancel.png" border="0" title="Close" alt="Close" /></a></td></tr>\n';
    h += '<tr><th>Su</th><th>M</th><th>Tu</th><th>W</th><th>Th</th><th>F</th><th>Sa</th></tr>\n';


	first_of_month = new Date(y, m - 1, 1);
	d1 = first_of_month.getDay();

    st = false;
    d = 0;
    for(i = 0; i < 42; i++)
    {
    	weekday = (i % 7);
    	if (weekday == d1 && d == 0) {st = true;}
    	if (weekday == 0) {h += '<tr>';}
    	if (st) {
    		d++;
    		cl = (y == def_y && m == def_m && d == def_d) 
    	        ? 'there' 
    			: ((y == this_y && m == this_m && d == this_d) ? 'today' : '');

    		h += '<td class="' + cl + ' date"><a href="#" onclick="updateField(\'' + cid + '\',' + y +',' +  m + ',' + d + '); return false;">' + d + '</a></td>';
    	} else {h += '<td>&nbsp;</td>';}
    	if (d == dpm[m-1]) {st = false;}
    	if (weekday == 6) {
    		if (!st) {break;}
    		h += '</tr>\n';
    	}
    }
    
    return h;
}

function drawCalendar(cid, y, m)
{
	if (m == 0) {
		m = 12;
		y--;
	}
	if (m == 13) {
		m = 1;
		y++;
	}
	document.getElementById('popup_cal').innerHTML = calendarHTML(cid, y, m);
}

function def_year(cid)  
{
	yyyy = document.getElementById(cid).value.substring(0, 4);
	yyyy = parseInt(yyyy);
	if (yyyy < 1900 || !yyyy) {
		d = new Date();
		yyyy = d.getFullYear();
	}
	return yyyy;
}

function def_month(cid) 
{
	mm = document.getElementById(cid).value.substring(5, 7);
	mm = parseInt(mm);
	if (mm > 12 || mm < 1 || !mm) {
		d = new Date();
		mm = d.getMonth() + 1;
	}
	return mm;
}

function def_day(cid)
{
	dd = document.getElementById(cid).value.substring(7, 9);
	dd = parseInt(dd);
	if (dd > 31 || dd < 1) {
		d = new Date();
		dd = d.getDate();
	}
	return dd;
}

function hideCalendar(cid)
{
	document.getElementById('popup_cal').style.display = 'none';
}

function showCalendar(cid)
{
	drawCalendar(cid, def_year(cid), def_month(cid));
	f = document.getElementById(cid);
	var x = y = 0;
	if (f.offsetParent) {
		x = f.offsetLeft
		y = f.offsetTop
		while (f = f.offsetParent) {
			x += f.offsetLeft
			y += f.offsetTop
		}
	}
	cal = document.getElementById('popup_cal');
	cal.style.left = (x - 120) + 'px';
	cal.style.top  = (y - 100) + 'px';
	cal.style.display = '';
}

function updateField(cid, y, m, d)
{
	document.getElementById(cid).value = zpad(m) + '/' + zpad(d) + '/' + y;
	hideCalendar(cid);
}

function zpad(n)
{
   n = '00' + n;
   return n.substring(n.length - 2);
}

function reformatDate(el)
{
    d = el.value;
    if (d.match(/(\d{1,2})[\/-](\d{1,2})[\/-](\d{1,4})/)) {
        mm = zpad(RegExp.$1);
        dd = zpad(RegExp.$2);
        yyyy =RegExp.$3 - 0;
        if (yyyy < 1000) {yyyy = parseInt(yyyy) + 2000;}
        el.value = mm + '/' + dd + '/' + yyyy;
    }
    if (el.value.match(/\d{2}\/\d{2}\/\d{4}/)) {
        el.style.backgroundColor = '';
    } else {
        el.style.backgroundColor = 'yellow';
    }
}