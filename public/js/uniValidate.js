// uniValidate: Universal form validator -- 16May2007, 5Mar2009, Jason Justian
//     EXAMPLES:
//     <form onsubmit="return uniValidate(this)">
//         <input type="text" name="your_name" class="required" />                    (a required field)
//         <input type="text" name="phone" class="required-if-not:email" />           (either phone OR email is required)
//         <input type="text" name="email" class="required-if-not:phone" />
//         <input type="text" name="best_time_to_call" class="required-if:phone" />   (required if phone is filled)
//         <input type="text" name="confirm_email" class="require-match:email" />     (value must match another field)
//         <input type="text" name="years_in_home" class="required-nonzero" />        (a nonzero value is required)
//         <input type="text" name="prev_addr" class="required-if:years_in_home<2" /> (required if years_in_home field is less than 2)
//
//     CHECKBOX EXAMPLES
//     (1) Require the college_name field if the college checkbox is selected, and a year if the "elementary" checkbox is not selected
//         <input type="checkbox" name="education[]" value="elementary" />
//         <input type="checkbox" name="education[]" value="high_school" />
//         <input type="checkbox" name="education[]" value="college" />
//         <input type="text" name="college_name" class="required-if-checked:education[]=college" />
//         <input type="text" name="grad_year" class="required-if-not-checked:education[]=elementary" />
//
//     (2) Require at least one color to be selected
//         <input type="checkbox" name="color[]" value="maize" class="require-choice" />
//         <input type="checkbox" name="color[]" value="blue" class="require-choice" />
//
//     (3) Require a type to be chosen if the phone field is filled
//         <input type="radio" name="type" value="Home" class="require-choice-if:phone" />
//         <input type="radio" name="type" value="Work" class="require-choice-if:phone" />
//
//     MULTIPLE CONDITION EXAMPLES:
//         <input type="text" name="age" class="nonzero required-if:username" />
//         <input type="text" name="last_completed_grade" class="required-if:age>17 required-if-checked:education[]=none" />

function uniValidate(f)
{
    var highlight_color = 'yellow';
    var shaded_color = '#ccc';
	var validate_err = 0;
	var mismatch_err = 0;
	for (var i=0; i < f.elements.length; i++)
	{
		var e = f.elements[i]; // current element
		var c = e.className.split(' '); // array of class names
		var req = false;
		var invalid = false;
		var nomatch = false;
		for (var j=0; j<c.length; j++)
		{
		    v = e.value;
		    
		    if (c[j].indexOf('-if') > -1) {
		        sc = c[j].split(':'); // sc[0] is the operation; sc[1] is the field name or expression
		        
		        // Checkbox dependency
		        if (sc[0].indexOf('-checked') > -1) {
		            ck = true;
		            if (sc[0].indexOf('-not-checked') > -1) {ck = false;}
		        	ex = sc[1].split('='); // ex[0] is the field name; ex[1] is the value
		        	b = document.getElementsByName(ex[0]);
		        	for (var k = 0; k < b.length; k++)
		        	{
    		            if (b[k].checked == ck && (b[k].value == ex[1] || b[k].value == ex[1].replace(/_/, ' '))) {req = true;}
		        	}
    		    }
		            	        
		        // Evaluate expressions
		        else if (sc[1].indexOf('!=') > -1) {
		            ex = sc[1].split('!='); // ex[0] is the field name, ex[1] is the value
		            if (parseFloat(f[ex[0]].value) != ex[1]) {req = true;}
                } else if (sc[1].indexOf('=') > -1) {
    		        ex = sc[1].split('='); 
		            if (parseFloat(f[ex[0]].value) == ex[1]) {req = true;}
		        } else if (sc[1].indexOf('>') > -1) {
			        ex = sc[1].split('>');
			        if (parseFloat(f[ex[0]].value) > ex[1]) {req = true;}
			    } else if (sc[1].indexOf('<') > -1) {
			        ex = sc[1].split('<');
			        if (parseFloat(f[ex[0]].value) < ex[1]) {req = true;}
			    } else if (sc[0].indexOf('-not') > -1) {
    			    if (f[sc[1]].value == '') {req = true;}
    			} else {
    			    if (f[sc[1]].value != '') {req = true;}
    			}   
    		}

		    if (c[j] == 'required') {req = true;}
		    if (c[j] == 'nonzero') {v = parseFloat(v);}
		    if (c[j] == 'required-nonzero') {
		        v = parseFloat(v);
		        req = true;
		    }
		    if (c[j] == 'password') {
		    	if (v.length < 8 || !v.match('[A-Z]') || !v.match('[a-z]') || !v.match('[0-9]')) {
		    		alert('Your password must meet all of the following conditions:\n\n* It must be at least 8 characters long\n* It must contain at least one uppercase letter\n* It must contain at least one lowercase letter\n* It must contain at least one number');
		    		return false;
		    	}
		    }
		    if (c[j] == 'email') {
		    	if (!v.match('.+@.+\..+')) {
		    		alert('Please provide a valid email address.');
		    		return false;
		    	}
		    }
		    
		    if (c[j] == 'ccn') {
		    	if (!mod10(v) || v.length < 15) {
		    		alert('The supplied credit card number is not valid');
		    		return false;
		    	}
		    }

		    if (c[j].indexOf('require-choice') > -1) {
                if (c[j] == 'require-choice') {req = true;}
		        var n = e.name;
		        siblings = document.getElementsByName(n);
		        v = false;
		        for (k = 0; k < siblings.length; k++)
		        {
		            if (siblings[k].checked) {v = true;}
		        }
		    }
		    
		    if (c[j].indexOf('require-match') > -1) {
		        sc = c[j].split(':');
		        if (f[sc[1]].value != e.value) {
                    nomatch = true;
                    f[sc[1]].style.backgroundColor = shaded_color;
                }
            }
		}
        if (req && !v) {invalid = true;}
		if (e.nextSibling && e.nextSibling.nodeName == 'LABEL') {
			e.nextSibling.style.backgroundColor = invalid ? highlight_color : '';
		}
		// console.log(e.parentNode.parentNode);
		e.style.backgroundColor = (invalid + nomatch) ? (invalid ? highlight_color : shaded_color) : '';

		if (e.name == 'tos' || e.name == 'age_verify') {
			if (e.checked) {
				e.parentNode.parentNode.style.backgroundColor = ' д д';
			} else {
				e.parentNode.parentNode.style.backgroundColor = highlight_color;
			}
		}

		validate_err += invalid;
		mismatch_err += nomatch;
	}
	if (mismatch_err + validate_err) {
    	if (mismatch_err) {
    	    alert('Please make sure the shaded fields match');
    	}
    	if (validate_err) {
    		alert('Please complete the highlighted field' + (validate_err > 1 ? 's' : ''));
    	}
    	return false;
    }
	return true;
}

function mod10(ccn)
{
	var doubled = [];
	for (var i = ccn.length - 2; i >= 0; i = i - 2)
	{
		doubled.push(2 * ccn[i]);
	}

	var total = 0;
	for (var i = ((ccn.length % 2) == 0 ? 1 : 0); i < ccn.length; i = i + 2)
	{
		total += parseInt(ccn[i]);
	}

	for (var i = 0; i < doubled.length; i++) 
	{
		var num = doubled[i];
		var digit;
		while (num != 0) 
		{
			digit = num % 10;
			num = parseInt(num / 10);
			total += digit;
		}
	}

	return !(total % 10)
}