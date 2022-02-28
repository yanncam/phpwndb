<?php
/*
██████╗ ██╗  ██╗██████╗ ██╗    ██╗███╗   ██╗██████╗ ██████╗
██╔══██╗██║  ██║██╔══██╗██║    ██║████╗  ██║██╔══██╗██╔══██╗
██████╔╝███████║██████╔╝██║ █╗ ██║██╔██╗ ██║██║  ██║██████╔╝
██╔═══╝ ██╔══██║██╔═══╝ ██║███╗██║██║╚██╗██║██║  ██║██╔══██╗
██║     ██║  ██║██║     ╚███╔███╔╝██║ ╚████║██████╔╝██████╔╝
╚═╝     ╚═╝  ╚═╝╚═╝      ╚══╝╚══╝ ╚═╝  ╚═══╝╚═════╝ ╚═════╝

PHPwnDB v1.0
Search credentials leaked on pwndb database with variation.

PHPwnDB is an adaptation of tools like pwndb.py in PHP for using it simply via a web browser.
PHPwnDB permits search based on domain.tld, username, firstname lastname permutations and the use of wildcard.
Results can be filtered to produce instant wordlists read-to-use through hashs cracker tools or Burp Intruder.

PHPwnDB can be very usefull during security assessments or Bug Bounty, when firstname lastname or email of the
web-developer / maintainer / webmaster / author of the web application is discovered in footer, CSS file or HTML comment.
By providing this information to PHPwnDB, a list of potential credentials already leaked by the past can be quickly tested against the web-app.

@Author : Yann CAM (@ycam / asafety.fr) - https://github.com/yanncam/phpwndb/
*/

echo "<h1>PHPwnDB</h1>";

echo "<table width='100%'><tr>";

echo "<td>";
echo "	<form action='?method=firstlastname' method='post'>
		<textarea cols='60' rows='10' name='search' placeholder='firstname lastname OR lastname firstname (like bill gates to check billgates, gatesbill, bgates, gbill, bill.gates, bill-gates, bill_gates, gates.bill, gates-bill, gates_bill and with wildcard-suffix on 2 chars like gatesbill13 or bgates37) '>" . ((isset($_GET["method"], $_POST["search"]) && $_GET["method"]==="firstlastname") ? trim(strval($_POST["search"])) : "") . "</textarea><br />
		<input type='submit' value='Search variation first/last name (take ~5min)' />
	</form>";
echo "</td>";
echo "<td>";
echo "  <form action='?method=username' method='post'>
                <textarea cols='60' rows='10' name='search' placeholder='username OR username@target.com (use _ as wildcard like bgates____ for bgates1337)'>" . ((isset($_GET["method"], $_POST["search"]) && $_GET["method"]==="username") ? trim(strval($_POST["search"])) : "") . "</textarea><br />
                <input type='submit' value='Search user/mail list' />
        </form>";
echo "</td>";
echo "<td>";
echo "  <form action='?method=domain' method='post'>
                <textarea cols='60' rows='10' name='search' placeholder='Target.com'>" . ((isset($_GET["method"], $_POST["search"]) && $_GET["method"]==="domain") ? trim(strval($_POST["search"])) : "") . "</textarea><br />
                <input type='submit' value='Search domains leaks' />
        </form>";
echo "</td>";

echo "</tr></table>";

function searchOnTorPwndb($luser, $domain = ""){
	$url = 'http://pwndb2am4tzkvold.onion';
	$postRequest = array(
        	"submitform" => "em",
        	"luser" => $luser,
        	"domain" => $domain,
        	"luseropr" => "1",
        	"domainopr" => "0",
	);
	$ch = curl_init($url);
	// curl_setopt($ch, CURLOPT_PROXYTYPE, 7);
	curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
	curl_setopt($ch, CURLOPT_PROXY, 'socks5h://127.0.0.1:9050');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $postRequest);
	$output = curl_exec($ch);
	if(curl_errno($ch)){
    		throw new Exception(curl_error($ch));
	}
	return extractPwnDbEntries($output);
}

function extractPwnDbEntries($output){
	$entries = array();
	$pattern = "'<pre>(.*?)</pre>'si";
	if(preg_match($pattern, $output, $match)){
        	$data = explode("Array", $match[1]);
        	foreach($data as $entry){
                	if(strpos($entry, "id")){
                        	$luser = trim(str_replace("[luser] => ", "", explode("\n", $entry)[3]));
                        	$domain = trim(str_replace("[domain] => ", "", explode("\n", $entry)[4]));
                        	$passwd = trim(str_replace("[password] => ", "", explode("\n", $entry)[5]));
                        	$entries[] = array("username" => $luser, "domain" => $domain, "password" => $passwd);
                	}
        	}
	}
	return $entries;
}

function generateVariationFirstLastName($input){
	$variations = array();
	$separators = array("_");
	$padding = array("_", "__");
	$parts = explode(" ", $input);
	foreach($parts AS &$part)
		$part = strtolower(trim($part));
	$variations[] = $parts[0] . $parts[1];
        $variations[] = $parts[1] . $parts[0];
	$variations[] = $parts[0][0] . $parts[1];
        $variations[] = $parts[1][0] . $parts[0];
	foreach($separators AS $separator){
		$variations[] = $parts[0] . $separator . $parts[1];
		$variations[] = $parts[1] . $separator . $parts[0];
		$variations[] = $parts[0][0] . $separator . $parts[1];
		$variations[] = $parts[1][0] . $separator . $parts[0];
	}
	$variationsBase = $variations;
	foreach($variationsBase AS $variationBase){
		foreach($padding AS $pad){
			$variations[] = $variationBase . $pad;
		}
	}
	return array_unique($variations);
}

function clearDiacritics($input){
    $replace = array(
        'ъ'=>'-', 'Ь'=>'-', 'Ъ'=>'-', 'ь'=>'-',
        'Ă'=>'A', 'Ą'=>'A', 'À'=>'A', 'Ã'=>'A', 'Á'=>'A', 'Æ'=>'A', 'Â'=>'A', 'Å'=>'A', 'Ä'=>'Ae',
        'Þ'=>'B',
        'Ć'=>'C', 'ץ'=>'C', 'Ç'=>'C',
        'È'=>'E', 'Ę'=>'E', 'É'=>'E', 'Ë'=>'E', 'Ê'=>'E',
        'Ğ'=>'G',
        'İ'=>'I', 'Ï'=>'I', 'Î'=>'I', 'Í'=>'I', 'Ì'=>'I',
        'Ł'=>'L',
        'Ñ'=>'N', 'Ń'=>'N',
        'Ø'=>'O', 'Ó'=>'O', 'Ò'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'Oe',
        'Ş'=>'S', 'Ś'=>'S', 'Ș'=>'S', 'Š'=>'S',
        'Ț'=>'T',
        'Ù'=>'U', 'Û'=>'U', 'Ú'=>'U', 'Ü'=>'Ue',
        'Ý'=>'Y',
        'Ź'=>'Z', 'Ž'=>'Z', 'Ż'=>'Z',
        'â'=>'a', 'ǎ'=>'a', 'ą'=>'a', 'á'=>'a', 'ă'=>'a', 'ã'=>'a', 'Ǎ'=>'a', 'а'=>'a', 'А'=>'a', 'å'=>'a', 'à'=>'a', 'א'=>'a', 'Ǻ'=>'a', 'Ā'=>'a', 'ǻ'=>'a', 'ā'=>'a', 'ä'=>'ae', 'æ'=>'ae', 'Ǽ'=>'ae', 'ǽ'=>'ae',
        'б'=>'b', 'ב'=>'b', 'Б'=>'b', 'þ'=>'b',
        'ĉ'=>'c', 'Ĉ'=>'c', 'Ċ'=>'c', 'ć'=>'c', 'ç'=>'c', 'ц'=>'c', 'צ'=>'c', 'ċ'=>'c', 'Ц'=>'c', 'Č'=>'c', 'č'=>'c', 'Ч'=>'ch', 'ч'=>'ch',
        'ד'=>'d', 'ď'=>'d', 'Đ'=>'d', 'Ď'=>'d', 'đ'=>'d', 'д'=>'d', 'Д'=>'D', 'ð'=>'d',
        'є'=>'e', 'ע'=>'e', 'е'=>'e', 'Е'=>'e', 'Ə'=>'e', 'ę'=>'e', 'ĕ'=>'e', 'ē'=>'e', 'Ē'=>'e', 'Ė'=>'e', 'ė'=>'e', 'ě'=>'e', 'Ě'=>'e', 'Є'=>'e', 'Ĕ'=>'e', 'ê'=>'e', 'ə'=>'e', 'è'=>'e', 'ë'=>'e', 'é'=>'e',
        'ф'=>'f', 'ƒ'=>'f', 'Ф'=>'f',
        'ġ'=>'g', 'Ģ'=>'g', 'Ġ'=>'g', 'Ĝ'=>'g', 'Г'=>'g', 'г'=>'g', 'ĝ'=>'g', 'ğ'=>'g', 'ג'=>'g', 'Ґ'=>'g', 'ґ'=>'g', 'ģ'=>'g',
        'ח'=>'h', 'ħ'=>'h', 'Х'=>'h', 'Ħ'=>'h', 'Ĥ'=>'h', 'ĥ'=>'h', 'х'=>'h', 'ה'=>'h',
        'î'=>'i', 'ï'=>'i', 'í'=>'i', 'ì'=>'i', 'į'=>'i', 'ĭ'=>'i', 'ı'=>'i', 'Ĭ'=>'i', 'И'=>'i', 'ĩ'=>'i', 'ǐ'=>'i', 'Ĩ'=>'i', 'Ǐ'=>'i', 'и'=>'i', 'Į'=>'i', 'י'=>'i', 'Ї'=>'i', 'Ī'=>'i', 'І'=>'i', 'ї'=>'i', 'і'=>'i', 'ī'=>'i', 'ĳ'=>'ij', 'Ĳ'=>'ij',
        'й'=>'j', 'Й'=>'j', 'Ĵ'=>'j', 'ĵ'=>'j', 'я'=>'ja', 'Я'=>'ja', 'Э'=>'je', 'э'=>'je', 'ё'=>'jo', 'Ё'=>'jo', 'ю'=>'ju', 'Ю'=>'ju',
        'ĸ'=>'k', 'כ'=>'k', 'Ķ'=>'k', 'К'=>'k', 'к'=>'k', 'ķ'=>'k', 'ך'=>'k',
        'Ŀ'=>'l', 'ŀ'=>'l', 'Л'=>'l', 'ł'=>'l', 'ļ'=>'l', 'ĺ'=>'l', 'Ĺ'=>'l', 'Ļ'=>'l', 'л'=>'l', 'Ľ'=>'l', 'ľ'=>'l', 'ל'=>'l',
        'מ'=>'m', 'М'=>'m', 'ם'=>'m', 'м'=>'m',
        'ñ'=>'n', 'н'=>'n', 'Ņ'=>'n', 'ן'=>'n', 'ŋ'=>'n', 'נ'=>'n', 'Н'=>'n', 'ń'=>'n', 'Ŋ'=>'n', 'ņ'=>'n', 'ŉ'=>'n', 'Ň'=>'n', 'ň'=>'n',
        'о'=>'o', 'О'=>'o', 'ő'=>'o', 'õ'=>'o', 'ô'=>'o', 'Ő'=>'o', 'ŏ'=>'o', 'Ŏ'=>'o', 'Ō'=>'o', 'ō'=>'o', 'ø'=>'o', 'ǿ'=>'o', 'ǒ'=>'o', 'ò'=>'o', 'Ǿ'=>'o', 'Ǒ'=>'o', 'ơ'=>'o', 'ó'=>'o', 'Ơ'=>'o', 'œ'=>'oe', 'Œ'=>'oe', 'ö'=>'oe',
        'פ'=>'p', 'ף'=>'p', 'п'=>'p', 'П'=>'p',
        'ק'=>'q',
        'ŕ'=>'r', 'ř'=>'r', 'Ř'=>'r', 'ŗ'=>'r', 'Ŗ'=>'r', 'ר'=>'r', 'Ŕ'=>'r', 'Р'=>'r', 'р'=>'r',
        'ș'=>'s', 'с'=>'s', 'Ŝ'=>'s', 'š'=>'s', 'ś'=>'s', 'ס'=>'s', 'ş'=>'s', 'С'=>'s', 'ŝ'=>'s', 'Щ'=>'sch', 'щ'=>'sch', 'ш'=>'sh', 'Ш'=>'sh', 'ß'=>'ss',
        'т'=>'t', 'ט'=>'t', 'ŧ'=>'t', 'ת'=>'t', 'ť'=>'t', 'ţ'=>'t', 'Ţ'=>'t', 'Т'=>'t', 'ț'=>'t', 'Ŧ'=>'t', 'Ť'=>'t', '™'=>'tm',
        'ū'=>'u', 'у'=>'u', 'Ũ'=>'u', 'ũ'=>'u', 'Ư'=>'u', 'ư'=>'u', 'Ū'=>'u', 'Ǔ'=>'u', 'ų'=>'u', 'Ų'=>'u', 'ŭ'=>'u', 'Ŭ'=>'u', 'Ů'=>'u', 'ů'=>'u', 'ű'=>'u', 'Ű'=>'u', 'Ǖ'=>'u', 'ǔ'=>'u', 'Ǜ'=>'u', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'У'=>'u', 'ǚ'=>'u', 'ǜ'=>'u', 'Ǚ'=>'u', 'Ǘ'=>'u', 'ǖ'=>'u', 'ǘ'=>'u', 'ü'=>'ue',
        'в'=>'v', 'ו'=>'v', 'В'=>'v',
        'ש'=>'w', 'ŵ'=>'w', 'Ŵ'=>'w',
        'ы'=>'y', 'ŷ'=>'y', 'ý'=>'y', 'ÿ'=>'y', 'Ÿ'=>'y', 'Ŷ'=>'y',
        'Ы'=>'y', 'ž'=>'z', 'З'=>'z', 'з'=>'z', 'ź'=>'z', 'ז'=>'z', 'ż'=>'z', 'ſ'=>'z', 'Ж'=>'zh', 'ж'=>'zh'
    );
    return strtr($input, $replace);
}



$entries = array();

if(isset($_GET["method"], $_POST["search"])){
	$begin = time();
	$inputs = explode("\n", strval($_POST["search"]));
	foreach($inputs AS $input){
		switch(strval($_GET["method"])){
                	case "firstlastname":
				$input = clearDiacritics($input);
				$variations = generateVariationFirstLastName(trim($input));
				//print_r($variations);
				foreach($variations AS $variation){
					$entries = array_merge($entries, searchOnTorPwndb(trim($variation)));
				}
                        	break;
                	case "username":
				$entries = array_merge($entries, searchOnTorPwndb(trim($input)));
                	        break;
        	        case "domain":
				$entries = array_merge($entries, searchOnTorPwndb("", trim($input)));
	                        break;
		}
	}
	$end = time();

	echo "<h2>" . (count($entries)-1). " leaks found in " . ($end-$begin) . " seconds</h2>";

	echo "<script>	var results = [";
	foreach($entries AS $entry){
		if($entry["username"] === "donate" && $entry["domain"] === "btc.thx"){
			// ignore donation entry in results, but don't hesitate to donate ! ;)
		} else {
			echo '{"username": "'.$entry["username"].'", "domain": "'.$entry["domain"].'", "password": "'.base64_encode($entry["password"]).'"},';
		}
	}
	echo "];</script>";

	echo "<script>";
?>
		function displayResults(){
			var cbUsername = document.getElementById('cbUsername').checked == 1;
			var cbDomain = document.getElementById('cbDomain').checked == 1;
			var cbPassword = document.getElementById('cbPassword').checked == 1;
			var sepUsernameDomain = document.getElementById('sepUsernameDomain').value;
			var sepIdentityPassword = document.getElementById('sepIdentityPassword').value;
			document.getElementById('results').value = '';
			if(cbUsername && !cbDomain && !cbPassword)
				results.forEach(entry => document.getElementById('results').value += entry['username']+"\n");
			if(!cbUsername && cbDomain && !cbPassword)
                                results.forEach(entry => document.getElementById('results').value += entry['domain']+"\n");
			if(!cbUsername && !cbDomain && cbPassword)
                                results.forEach(entry => document.getElementById('results').value += atob(entry['password'])+"\n");
			if(cbUsername && cbDomain && !cbPassword)
                                results.forEach(entry => document.getElementById('results').value += entry['username']+sepUsernameDomain+entry['domain']+"\n");
                        if(cbUsername && !cbDomain && cbPassword)
                                results.forEach(entry => document.getElementById('results').value += entry['username']+sepIdentityPassword+atob(entry['password'])+"\n");
			if(!cbUsername && cbDomain && cbPassword)
                                results.forEach(entry => document.getElementById('results').value += entry['domain']+sepIdentityPassword+atob(entry['password'])+"\n");
                        if(cbUsername && cbDomain && cbPassword)
                                results.forEach(entry => document.getElementById('results').value += entry['username']+sepUsernameDomain+entry['domain']+sepIdentityPassword+atob(entry['password'])+"\n");
		}
<?php
	echo "</script>";

	echo "<input id='cbUsername' type='checkbox' onclick='displayResults();' checked='checked' />Username";
	echo "<select id='sepUsernameDomain' onchange='displayResults();'><option value='@'>@</option><option value=';'>;</option><option value=','>,</option></select>";
        echo "<input id='cbDomain' type='checkbox' onclick='displayResults();' checked='checked' />Domain";
	echo "<select id='sepIdentityPassword' onchange='displayResults();'><option value=':'>:</option><option value=';'>;</option><option value='='>=</option><option value=','>,</option></select>";
        echo "<input id='cbPassword' type='checkbox' onclick='displayResults();' checked='checked' />Password";
	echo "<input type='button' value='Copy results' onclick='document.getElementById(\"results\").select();document.execCommand(\"copy\");' />";
	echo "<br />";

	echo "<textarea id='results' readonly cols='150' rows='30'>";
	foreach($entries AS $entry){
        	if($entry["username"] === "donate" && $entry["domain"] === "btc.thx"){
                	// ignore donation entry in results, but don't hesitate to donate ! ;)
        	} else {
                	echo $entry["username"]."@".$entry["domain"] . ":" . $entry["password"] . "\n";
        	}
	}
	echo "</textarea>";
}
?>
