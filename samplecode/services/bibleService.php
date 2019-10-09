<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
require($_SERVER['DOCUMENT_ROOT'] . '/samplecode/controllers/bibleController.php');
$logos = new bibleController();

//Initialize inputs
$rcvd=$logos->parseData();
$bref = array_key_exists('bRef', $rcvd) ? $rcvd['bRef']:'default';

$output=$logos->callYouVersion($bref);
error_log(print_r($output['message'],true));
$encodedOutput = json_encode(urlencode($output['content']));
echo $encodedOutput;
