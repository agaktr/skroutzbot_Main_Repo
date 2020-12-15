<?php

namespace App\Service;

class CurlHandler
{

    //The curl array
    private $curly;

    //The curl multi handler
    private $multiHandler;

    //The urls array
    private $urls;

    //The chunked urls array
    private $chunkedUrlsArray;

    //The input options array
    private $inputOptions;

    //The merged handler options array
    private $handlerOptions;

    //The check for multi curl handler result
    private $curlIsRunning;

    //The multicurl status
    private $curlStatus;

    //The returned result
    private $result;

    const CURL_DEFAULTS = [
        'CURLOPT_USERAGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36',
        'CURLOPT_HEADER' => 0,
        'CURLOPT_RETURNTRANSFER' => 1,
        'CURLOPT_FRESH_CONNECT' => true,
        'CURLOPT_FOLLOWLOCATION' => true,
        'CURLOPT_VERBOSE' => true,
        'CURLOPT_COOKIE' => '',
        'CURLOPT_HTTPHEADER' => [],
        'CURLOPT_POST' => 0,
        'CURLOPT_POSTFIELDS' => [],
        'ExtraOptions' => [],
        'ArrayChunkSize' => 100,
        'SPINNER_IPS' => [
//            '62.77.152.96',
//            '80.209.229.50',
        ],
        'SleepCounter' => 0,
        'SleepCounterMax' => 200,
        'SleepCounterSleepSeconds' => 2,
        'SetSleepSeconds' => 2,
        'Replacements' => [
//            'gamatotv.to' => 'gamatotv.me',
//            'http://teniesonline.ucoz.com' => 'http://tenies-online.club',
//            'xrysoi.se' => 'xrysoi.online',
        ],
        'SlowMode' => false,
        'SlowModeArrayChunkSize' => 5,
        'SlowModeProviders' => [
            'tainies.online',
            'online.ucoz',
            'online-filmer',
        ],
    ];

    public function multiRequest($urls, &$inputOptions = array()) {

        //Set variables
        $this->urls = $urls;
        $this->inputOptions = $inputOptions;

        //Merge Options with Defaults
        $this->handlerOptions = array_replace_recursive(self::CURL_DEFAULTS,$this->inputOptions);

        //Check if a UserAgent is available
        /**
         * TODO I THINK THIS IS ALWAYS TRIGGERING
         * TODO DEFAULT USER AGENT AND IT NEVER
         * TODO SET THE INJECTED ONE
         */
        $this->handlerOptions['CURLOPT_USERAGENT'] = (isset($_SERVER) && isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : $this->handlerOptions['CURLOPT_USERAGENT'];
//var_dump($this->handlerOptions['CURLOPT_USERAGENT']);
        //Make any URL replacements
        if ($this->handlerOptions['Replacements']){
            foreach ($this->handlerOptions['Replacements'] as $search=>$replace){
                $this->urls= str_replace($search,$replace,$this->urls);
            }
        }

        //Determine if we need to enable SlowMode
        foreach ($this->urls as $url){
            foreach ($this->handlerOptions['SlowModeProviders'] as $provider) {
                if (strpos($url, $provider)) {
                    $this->handlerOptions['SlowMode'] = true;
                }
            }
        }

        //If SlowMode is enabled we break the urls in smaller chunks
        // so we wont be noticed by the victim
        $chunkSize = $this->handlerOptions['ArrayChunkSize'];
        if ($this->handlerOptions['SlowMode']){
            $chunkSize = $this->handlerOptions['SlowModeArrayChunkSize'];
        }
        $this->chunkedUrlsArray = array_chunk($this->urls,$chunkSize,true);
//var_dump($chunkSize);
        //We start the chunked array iteration
        foreach ($this->chunkedUrlsArray as $setOfUrls){

            //we check how many links we are going to fetch
            $this->handlerOptions['SleepCounter'] += count($setOfUrls);

            //if we abuse, we stop for a bit
            if ($this->handlerOptions['SleepCounter'] >= $this->handlerOptions['SleepCounterMax']){

                sleep($this->handlerOptions['SleepCounterSleepSeconds']);

                //after sleep we refhresh counter with the current urls count
                $this->handlerOptions['SleepCounter'] = count($setOfUrls);
            }

            //open multi handler
            $this->multiHandler = curl_multi_init();

            foreach ($setOfUrls as $id => $url) {

                //setup curl array
                $this->curly[$id] = curl_init();

                //check for array type of url
                $url = (is_array($url) && !empty($url['url'])) ? $url['url'] : $url;

                //setup curl options
                curl_setopt($this->curly[$id], CURLOPT_URL, $url);
                curl_setopt($this->curly[$id], CURLOPT_USERAGENT, $this->handlerOptions['CURLOPT_USERAGENT']);
                curl_setopt($this->curly[$id], CURLOPT_HEADER, $this->handlerOptions['CURLOPT_HEADER']);
                curl_setopt($this->curly[$id], CURLOPT_RETURNTRANSFER, $this->handlerOptions['CURLOPT_RETURNTRANSFER']);
                curl_setopt($this->curly[$id], CURLOPT_FRESH_CONNECT, $this->handlerOptions['CURLOPT_FRESH_CONNECT']);
                curl_setopt($this->curly[$id], CURLOPT_FOLLOWLOCATION, $this->handlerOptions['CURLOPT_FOLLOWLOCATION']);
                curl_setopt($this->curly[$id], CURLOPT_VERBOSE, $this->handlerOptions['CURLOPT_VERBOSE']);
                //TODO This to be commented out on PRODUCTION

                if (count($this->handlerOptions['SPINNER_IPS']) > 1){

                    $curlIp = $this->handlerOptions['SPINNER_IPS'][rand(0,(count($this->handlerOptions['SPINNER_IPS'])-1))];
                    curl_setopt($this->curly[$id], CURLOPT_INTERFACE, $curlIp);
                }
//                curl_setopt($this->curly[$id], CURLOPT_INTERFACE,  '62.77.152.96');


                //TODO set cookie only on specific provider
                if (!empty($this->handlerOptions['CURLOPT_COOKIE'])){
                    curl_setopt($this->curly[$id], CURLOPT_COOKIE, $this->handlerOptions['CURLOPT_COOKIE'].';');
                }

                if (!empty($this->handlerOptions['CURLOPT_HTTPHEADER'])){
                    curl_setopt($this->curly[$id], CURLOPT_HTTPHEADER, $this->handlerOptions['CURLOPT_HTTPHEADER']);
                }

                if (!empty($this->handlerOptions['CURLOPT_POST'])){
                    curl_setopt($this->curly[$id], CURLOPT_POST, $this->handlerOptions['CURLOPT_POST']);
                    curl_setopt($this->curly[$id], CURLOPT_POSTFIELDS, $this->handlerOptions['CURLOPT_POSTFIELDS']);
                }
//var_dump($this->handlerOptions['CURLOPT_POST']);
                if (!empty($this->handlerOptions['ExtraOptions'])){
                    curl_setopt_array($this->curly[$id], $this->handlerOptions['ExtraOptions']);
                }

                if (!empty($this->handlerOptions['CURLOPT_HTTPHEADER'])){
                    curl_setopt($this->curly[$id], CURLOPT_HTTPHEADER, $this->handlerOptions['CURLOPT_HTTPHEADER']);
                }
                curl_setopt($this->curly[$id], CURLINFO_HEADER_OUT, true);

                //add to multihandler
                curl_multi_add_handle($this->multiHandler, $this->curly[$id]);


            }

            //Run the requests
            $this->curlIsRunning = null;
            do {
                $this->curlStatus = curl_multi_exec($this->multiHandler, $this->curlIsRunning);
            } while($this->curlStatus === CURLM_CALL_MULTI_PERFORM || $this->curlIsRunning);

            // get content and remove handles
            foreach($this->curly as $id => $curlResponse) {

                $this->result[$id] = curl_multi_getcontent($curlResponse);
                curl_multi_remove_handle($this->multiHandler, $curlResponse);

                if ($this->handlerOptions['CURLOPT_HEADER'] == 1){
                    $header_size = curl_getinfo($this->curly[$id], CURLINFO_HEADER_SIZE);
                    $headers[] = substr($this->result[$id], 0, $header_size);
                }
            }

            // close the handler we are done
            curl_multi_close($this->multiHandler);

            //If SlowMode is enabled, sleep again for some seconds so we are not get noticed
            if ($this->handlerOptions['SlowMode']){

                sleep($this->handlerOptions['SetSleepSeconds']);
            }


        }

//        return $headers;
        //return the result
        return $this->result;
    }
}