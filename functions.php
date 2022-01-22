    <?php

    function get_proxy()
    {
        $proxy = database::find_random_proxy();
        if (!empty($_SESSION["proxy"]["ip"] ?? null)) {
            return $_SESSION["proxy"];
        } else if (!empty($proxy["ip"])) {
            $_SESSION["proxy"] = $proxy;
            return $proxy;
        } else {
            return false;
        }
    }

    function get_proxy_type($id)
    {
        switch ($id ?? 0) {
            case 1:
                $type = CURLPROTO_HTTPS;
                break;
            case 2:
                $type = CURLPROXY_SOCKS4;
                break;
            case 3:
                $type = CURLPROXY_SOCKS5;
                break;
            default:
                $type = CURLPROXY_HTTP;
                break;
        }
        return $type;
    }

    ?>