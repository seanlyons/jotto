function anagramify() {
	var text = document.getElementById("guess_textbox").value;
	split = text.split("");
	rearranged = shuffle(split);
	back = rearranged.join("");
	document.getElementById("guess_textbox").value = back;
}

function shuffle(o){ //v1.0
	for(var j, x, i = o.length; i; j = Math.floor(Math.random() * i), x = o[--i], o[i] = o[j], o[j] = x);
	return o;
};

