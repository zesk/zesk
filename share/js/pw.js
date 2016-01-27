/**
 * Password Strength
 */
function Password_Strength(pw)
{
	var x = Password_Statistics(pw);
	return x.strength;
}

/**
 * Password_Combinations
 */
function Password_Combinations(pw)
{
	var x = Password_Statistics(pw);
	return x.combinations;
}

var Password_Max_Combinations8 = 7213895789838336;
var Password_Max_Combinations8_Log = Math.log(Password_Max_Combinations8);

function Bits_Count(e)
{
	var n = 0;
	var e = parseInt(e);
	while (e != 0) {
		if (e & 1) n++;
		e >>= 1;
	}
	return n;
}

function Password_Entropy(pw)
{
	if (pw.length <= 1) return 0;
	var e = pw.charCodeAt(0);
	var changed_bits = 0;
	for (var i = 1; i < pw.length; i++) {
		var e0 = pw.charCodeAt(i-1);
		var e1 = pw.charCodeAt(i);
		changed_bits += Bits_Count(e0 ^ e1);
	}
	return changed_bits / (pw.length - 1);
}

function Password_UniqueCharacters(pw)
{
	var n = 0;
	while (pw.length > 0) {
		pw = pw.split(pw.charAt(0)).join("");
		++n;
	}
	return n;
}

/**
 * Password_Statistics
 */
function Password_Statistics(pw)
{
	var x = new Object();
	x.length 			= pw.length;
	x.upper 			= pw.replace(/[^A-Z]/g,"").length;
	x.lower				= pw.replace(/[^a-z]/g,"").length;
	x.number			= pw.replace(/[^0-9]/g,"").length;
	x.symbols			= pw.replace(/[^\W]/g,"").length;
	x.entropy			= Password_Entropy(pw);
	x.unique_characters	= Password_UniqueCharacters(pw);

	var n_avail = 0;
	if (x.upper > 0) n_avail += 26;
	if (x.lower > 0) n_avail += 26;
	if (x.number > 0) n_avail += 10;
	if (x.symbols > 0) n_avail += 34;
	x.available_combinations = n_avail;

	x.combinations		= Math.pow(n_avail,Math.max(parseInt(x.length/8),x.unique_characters));
	x.strength			= parseInt(Math.min((Math.log(x.combinations) / Password_Max_Combinations8_Log) * 1000,1000)) / 10;
	return x;
}