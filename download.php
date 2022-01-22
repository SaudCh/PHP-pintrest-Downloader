<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://kit.fontawesome.com/92810295e0.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/css/index.css">
    <link rel="shortcut icon" href="./assets/images/logo.png" type="image/x-icon">

    <title>iTrust</title>

    <style>
        .result-div {
            display: flex;
            justify-content: center;
            flex-direction: column;
            align-items: center;
            margin: 100px 0;
        }

        .result-div button {
            margin-top: 50px;
        }

        .result-div button a {
            color: white;
        }

        .result-div button a:hover {
            text-decoration: none;
        }
    </style>
</head>

<body class="bg-light">
    <?php include './assets/includes/header.php';
    ?>
    <?php include './assets/includes/search.php'
    ?>

    <?php

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
    //$pinId = substr($url, 30, -1);

    $parsed_url = parse_url($url);
    //print_r($parsed_url);
    if (isset($parsed_url['host']) && ($parsed_url["host"] == "pin.it" || $parsed_url["host"] == "www.pinterest.com" || $parsed_url["host"] == "www.pinterest.co.uk" || $parsed_url["host"] == "in.pinterest.com")) {

        if ($parsed_url['host'] == 'pin.it') {
            $original_url = unshorten($url, $enable_proxies);
            if (isset($original_url) != "") {
                $url = strtok($original_url, '?');
                $pinId = substr($url, 30, -6);
            }
        }

        $parsed_url = parse_url($url);
        //print_r($parsed_url);
        $temp1 = substr($parsed_url["path"], -5, -1);
        $temp2 = substr($parsed_url["path"], -1);


        $pinId = substr($parsed_url["path"], 5);

        //echo $temp2;
        if ($temp1 == "sent") {
            $pinId = substr($parsed_url["path"], 5, -6);
        } elseif ($temp2 == '/') {
            $pinId = substr($parsed_url["path"], 5, -1);
        }
        //echo $pinId;



        $page_source = url_get_contents($url, false);
        //echo $page_source;


        $video["title"] = get_string_between($page_source, "<title>", "</title>");

        $video["source"] = "pinterest";
        $video["thumbnail"] = get_string_between($page_source, '"image_cover_url":"', '"');
        //$video["thumbnail"];
        //print_r($video);

        $video_data = get_string_between($page_source, '<script id="initial-state" type="application/json">', '</script>');


        $video_data = json_decode($video_data, true);

        //print_r($video_data);
        if (isset($video_data["pins"][$pinId]["videos"]["video_list"]["V_720P"]["url"])) {

            $link = $video_data["pins"][$pinId]["videos"]["video_list"]["V_720P"]["url"];

            echo '<section id="result" class="result-div">';
            echo "<video width='320' height='240' controls src='" . $link . "' alt='img'></video>";
            echo "<button class='btn btn-primary'><a href='" . $link . "'>Click here to download</a></button>";
            echo '</section>';
        } else {
            echo '<section style="margin:20px">';
            echo '<div class="alert alert-danger" role="alert">Video Not Avaiable Please try again later</div>';
            echo '</section>';
        }
    } else {
        echo '<section style="margin:20px">';
        echo '<div class="alert alert-danger" role="alert">This Link not Valid</div>';
        echo '</section>';
    }






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

    ?>

    <?php include "./assets/includes/section.php"
    ?>

    <?php include "./assets/includes/footer.php"
    ?>

</body>

</html>