<? 

function generate_csrf_token()
{
if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION > 5) {
return bin2hex(random_bytes(32));
} else {
if (function_exists('mcrypt_create_iv')) {
return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
} else {
return bin2hex(openssl_random_pseudo_bytes(32));
}
}
}

function unshorten($url, $enable_proxies = false, $max_redirs = 3)
{
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirs);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
curl_setopt($ch, CURLOPT_URL, $url);
if ($enable_proxies) {
if (!empty($_SESSION["proxy"] ?? null)) {
$proxy = $_SESSION["proxy"];
} else {
$proxy = get_proxy();
$_SESSION["proxy"] = $proxy;
}
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ":" . $proxy['port']);
curl_setopt($ch, CURLOPT_PROXYTYPE, get_proxy_type($proxy['type']));
if (!empty($proxy['username']) && !empty($proxy['password'])) {
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
}
$chunkSize = 1000000;
curl_setopt($ch, CURLOPT_TIMEOUT, (int)ceil(3 * (round($chunkSize / 1048576, 2) / (1 / 8))));
}
curl_exec($ch);
$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
return $url;
}


$enable_proxies = false;
const _REQUEST_USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.105 Safari/537.36";
$_SESSION['token'] = generate_csrf_token();

function url_get_contents($url, $enable_proxies = false)
{
$enable_proxies = false;

$cookie_file_name = $_SESSION["token"] . ".txt";
$cookie_file = join(DIRECTORY_SEPARATOR, [sys_get_temp_dir(), $cookie_file_name]);
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
if ($enable_proxies) {
if (!empty($_SESSION["proxy"] ?? null)) {
$proxy = $_SESSION["proxy"];
} else {
$proxy = get_proxy();
$_SESSION["proxy"] = $proxy;
}
curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ":" . $proxy['port']);
curl_setopt($ch, CURLOPT_PROXYTYPE, get_proxy_type($proxy['type']));
if (!empty($proxy['username']) && !empty($proxy['password'])) {
curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
}
$chunkSize = 1000000;
curl_setopt($ch, CURLOPT_TIMEOUT, (int)ceil(3 * (round($chunkSize / 1048576, 2) / (1 / 8))));
}
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
if (file_exists($cookie_file)) {
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
}
$data = curl_exec($ch);
curl_close($ch);
return $data;
}

function get_string_between($string, $start, $end)
{
$string = ' ' . $string;
$ini = strpos($string, $start);
if ($ini == 0) return '';
$ini += strlen($start);
$len = strpos($string, $end, $ini) - $ini;
return substr($string, $ini, $len);
}



/****************************************** */


$url = $_POST["query"];
$pinId = substr($url, 30, -1);

$parsed_url = parse_url($url);
if ($parsed_url['host'] == 'pin.it') {
$original_url = unshorten($url, $enable_proxies);
if (isset($original_url) != "") {
$url = strtok($original_url, '?');
$pinId = substr($url, 30, -6);
}
}
//echo $url;

//echo $pinId;

$page_source = url_get_contents($url, false);
//echo $page_source;


$video["title"] = get_string_between($page_source, "<title>", "</title>");

$video["source"] = "pinterest";
$video["thumbnail"] = get_string_between($page_source, '"image_cover_url":"', '"');
$video["thumbnail"];

$video_data = get_string_between($page_source, '<script id="initial-state" type="application/json">
    ', '
</script>');
//echo $video_data;


$video_data = json_decode($video_data, true);

//print_r($video_data);

$link = $video_data["pins"][$pinId]["videos"]["video_list"]["V_720P"]["url"];


echo "<video width='320' height='240' controls src='" . $link . "' alt='img'></video>";
echo "<button class='btn btn-primary'><a href='" . $link . "'>Click here to download</a></button>"






/*
if (isset($video_data["resourceResponses"][0]["response"]["data"]["videos"]["video_list"])) {
$streams = $video_data["resourceResponses"][0]["response"]["data"]["videos"]["video_list"];
} elseif (isset(reset($video_data["resources"]["data"]["PinResource"])["data"]["videos"]["video_list"])) {
$streams = reset($video_data["resources"]["data"]["PinResource"])["data"]["videos"]["video_list"];
$video["title"] = reset($video_data["resources"]["data"]["PinResource"])["data"]["title"];
} else {
echo false;
}
if (count($streams) > 0) {
$i = 0;
foreach ($streams as $stream) {
$ext = pathinfo(parse_url($stream["url"])["path"], PATHINFO_EXTENSION);
if ($ext != "m3u8") {
$video["links"][$i]["url"] = $stream["url"];
$video["links"][$i]["type"] = $ext;
$video["links"][$i]["bytes"] = get_file_size($stream["url"], $this->enable_proxies, false);
$video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
$video["links"][$i]["quality"] = min($stream["height"], $stream["width"]) . "p";
$video["links"][$i]["mute"] = "no";
$i++;
}
}
echo $video;
} else {
echo false;
}

*/