<?php

use GuzzleHttp\Client;

class Crawl_Helper{
    private $dom;
    private $http;

    function __construct($dom)
    {
        $this->dom = $dom;
        $this->http = new Client();
    }

    public function getPage($url): \Psr\Http\Message\ResponseInterface
    {
        return $this->http->get($url);
    }

    public function getUrls($page_url): array
    {
        if(empty($this->dom))
            return [];

        if(!empty($this->dom)){
            $urls = $this->dom->getElementsByTagName('a');
            foreach ($urls as $url){
                $href = $url->getAttribute('href');
                if(strlen($href)<= strlen("http://"))
                    continue;

                if (0 !== strpos($href, 'http')) {
                    $path =  ltrim($href, '/');
                    if (extension_loaded('http')) {
                        $href = http_build_url($page_url, array('path' => $path));
                    } else {
                        $parts = parse_url($page_url);
                        $href = $parts['scheme'] . '://';
                        if (isset($parts['user']) && isset($parts['pass'])) {
                            $href .= $parts['user'] . ':' . $parts['pass'] . '@';
                        }
                        $href .= $parts['host'];
                        if (isset($parts['port'])) {
                            $href .= ':' . $parts['port'];
                        }
                        $href .= dirname($parts['path'], 1).$path;
                    }

                }
                $num_of_urls[] = $href;
            }
            return $num_of_urls;
        }

        return [];
    }

    /**
     * average word count from div, p, span tag
     */
    public function avgWordCount(): array
    {
        $title_array = [];
        $avg_array = [];

        $title = ['p', 'span', 'a'];
        if(!empty($this->dom)){
            foreach($title as $h){
                $xpath = new DOMXPath($this->dom);
                foreach ($xpath->query('//'.$h.'/text()') as $textNode) {
                    $title_array[$h][] = strlen($textNode->textContent);
                }
            }
            if(!empty($title_array)){
                foreach ($title as $item) {
                    if(empty($title_array[$item]))
                        continue;
                    $avg_array[$item] = $this->getAvgFromArray($title_array[$item]);
                }
            }

        }
        return $avg_array;
    }

    public function avgTitleLength(): array
    {
        $title_array = [];
        $length_array = [];
        $avg_array = [];
        $title = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        if(!empty($this->dom)){
            foreach($title as $h){

                $xpath = new DOMXPath($this->dom);

                foreach ($xpath->query('//'.$h.'/text()') as $textNode) {
                    $title_array[$h][] = ['length'=>strlen($textNode->nodeValue), 'string'=>$textNode->nodeValue];
                }
            }
            foreach ($title as $h){
                if(!empty($title_array)){
                    if(empty($title_array[$h]))
                        continue;
                    $length_array[$h] = array_column($title_array[$h],'length',1);
                }
            }
            if(!empty($length_array)){
                foreach($length_array as $k=>$v){
                    $avg_array[$k] = $this->getAvgFromArray($length_array[$k]);
                }
            }

        }
        return $avg_array;
    }

    public function getAvgFromArray($array = []){
        if(count($array) < 1)
            return 0;
        $a = array_filter($array);
        return ceil(array_sum($a)/count($a));
    }

    public function getImages($page_url): array
    {
        if(empty($this->dom))
            return [];

        $uniqueImages = [];
        $uniqueImageURL = [];
        if(!empty($this->dom)){
            $urls = $this->dom->getElementsByTagName('img');
            foreach ($urls as $url){
                try{
                    $href = $url->getAttribute('src');
                    if(strpos($href, 'http') !== false){
                        if(strlen($href)<= strlen("http://"))
                            continue;

                        $resp = $this->getPage($href);
                        if($resp->getStatusCode() == 200)
                            $uniqueImages[] = ['url'=>$href, 'hash'=>md5_file($href)];

                    }else{
                        $complete_href = rtrim($page_url,'/').$href;
                        $resp = $this->getPage($complete_href);
                        if($resp->getStatusCode() == 200)
                            $uniqueImages[] = ['url'=>$complete_href, 'hash'=>md5_file($complete_href)];

                    }
                }catch (Exception $e){
                    $uniqueImages[] = ['url'=>"", 'hash'=>"bad_url"];
                } finally
                {
                    $uniqueImages[] = ['url'=>"", 'hash'=>"bad_url"];
                }

            }
            if(!empty($uniqueImages)){
                $uniqueImages = array_unique($uniqueImages,SORT_REGULAR);
//                foreach ($uniqueImages as $u){
//
//                }
                $uniqueImageURL = array_column($uniqueImages,'url');
            }



        }

        return $uniqueImages;
    }

    function getIntExtURLs($url) : array{

        $parse = parse_url($url);
        $num_of_urls = $this->getUrls($url);
        $urls = [];
        if(!empty($num_of_urls)){
            foreach ($num_of_urls as $u){
                if (strpos($u, $parse['host']) !== false) {
                    $urls['internal'][] = $u;
                }else{
                    $urls['external'][] = $u;
                }
            }
        }
        ;
        return $urls;
    }
}

