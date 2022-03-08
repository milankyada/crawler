<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class CrawlController extends Controller
{
    //
    function crawl(Request $request){
        $regex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        $request->validate(['crwl_url'=>'required|regex:'.$regex]);
        try {
            $url = $request->input('crwl_url');
            $dom = new DOMDocument('1.0');
            $crawler = new \Crawl_Helper($dom);
            @$dom->loadHTMLFile($url);
            $num_of_urls = $crawler->getUrls($url);


            $page_data = [];
            for($i=0; $i<5; $i++){
                if(!empty($num_of_urls[$i])){
                    $page_data[$i]['URL'] = $num_of_urls[$i];
                    $t = microtime( TRUE );
                    $resp = $crawler->getPage($url);
                    $t = microtime( TRUE ) - $t;
                    $page_data[$i]['page_load_time'] = $t." s";
                    $page_data[$i]['status_code'] = $resp->getStatusCode();

                    @$dom->loadHTMLFile($num_of_urls[$i]);
                    $sub_page_crawler = new \Crawl_Helper($dom);
                    if($resp->getStatusCode() == 200){
                        $page_data[$i]['unique_images'] = $sub_page_crawler->getImages($num_of_urls[$i]);
                        $web_page_word = $sub_page_crawler->avgWordCount();
                        $page_data[$i]['web_page_word_count'] = $sub_page_crawler->getAvgFromArray($web_page_word);

                        $page_data[$i]['header_words_avg'] = $sub_page_crawler->avgTitleLength();
                        $page_data[$i]['external_internal_urls'] = $sub_page_crawler->getIntExtURLs($num_of_urls[$i]);
                    }
                }
            }

            return view('result')->with(compact(['num_of_urls','page_data']));
        }catch (\Exception $e){
            return Redirect::back()->withErrors(['msg'=>$e->getMessage()]);
        }
    }
}
