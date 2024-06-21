<?php

abstract class HTTPResource extends AbstractResource {
    public function httpget($url) {
        $s = curl_init();
        Timer::start(get_class($this));
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($s, CURLOPT_BINARYTRANSFER, true);
        $result = curl_exec($s);
        if ($result === false) {
            Logger::warn($url . ' returned faulty result ' . curl_error());
        }
        Timer::stop(get_class($this));
        return $result;
    }

    public function httppost($url, $postData) {
        $s = curl_init();
        curl_setopt($s, CURLOPT_URL, $url);
        curl_setopt($s, CURLOPT_POST, true);
        curl_setopt($s, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($s);
        if ($content === false) {
            echo curl_error();
        } else {
            echo $content;
        }
    }
}
?>
