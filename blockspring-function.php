<?php
require('blockspring.php');

function webhook($team_domain, $service_id, $token, $user_name, $team_id, $user_id, $channel_id, $timestamp, $channel_name, $text, $trigger_word, $raw_text) {

    //Get Capital Bikeshare bike availability for stations near Chinatown
    $terminal_names = array(
    	31228, //8th & H St NW
        31232, //7th & F St NW, Galleries
        //31620, //5th & F St NW -- this one is a little far, so we'll keep it out for now
        31274 //9th & G St NW, MLK
    	);
	$request_url = 'https://www.capitalbikeshare.com/data/stations/bikeStations.xml';
    
    $stations = file_get_contents($request_url);
    $station_data = simplexml_load_string($stations);
	$attachments = array();
    
    foreach($station_data->station as $station) {
        if(in_array($station->terminalName, $terminal_names)){
            
            //This is a station near Chinatown. Grab its status, and turn into an attachment.
                if($station->nbBikes != 1){ $bikes_label = 'bikes'; } else { $bikes_label = 'bike'; }
            	if($station->nbEmptyDocks != 1){ $empty_label = 'empty slots'; } else { $empty_label = 'empty slot'; }
                
                $bikes = $station->nbBikes.' '.$bikes_label.' - '.$station->nbEmptyDocks.' '.$empty_label;
            
            	$station_name = (string)$station->name;
            	
            	if($station->nbBikes == 0){
                    $color = '#BF1038'; //red
                } elseif($station->nbBikes < 4){
                    $color = '#DD8702'; //orange
                } elseif($station->nbBikes < 8){
                    $color = '#F6D514'; //yellow
                } else {
                 	$color = '#00AF52'; //green
                }
            
            	$attachments[] = array(
            		'title' => $station_name,
                	'color' => $color,
                	'text' => $bikes
            	);
            
        }
    }
    
    return array(
        "text" => 'Bike availability at stations near Chinatown:',  // send a text response (replies to channel if not blank)
        "attachments" => $attachments
    );
}

Blockspring::define(function ($request, $response) {
    $team_domain = isset($request->params['team_domain']) ? $request->params['team_domain'] : "";
    $service_id = isset($request->params['service_id']) ? $request->params['service_id'] : "";
    $token = isset($request->params['token']) ? $request->params['token'] : "";
    $user_name = isset($request->params['user_name']) ? $request->params['user_name'] : "";
    $team_id = isset($request->params['team_id']) ? $request->params['team_id'] : "";
    $user_id = isset($request->params['user_id']) ? $request->params['user_id'] : "";
    $channel_id = isset($request->params['channel_id']) ? $request->params['channel_id'] : "";
    $timestamp = isset($request->params['timestamp']) ? $request->params['timestamp'] : "";
    $channel_name = isset($request->params['channel_name']) ? $request->params['channel_name'] : "";
    $raw_text = $text = isset($request->params['text']) ? $request->params['text'] : "";
    $trigger_word = isset($request->params['trigger_word']) ? $request->params['trigger_word'] : "";
    
    // ignore all bot messages
    if($user_id == 'USLACKBOT') {
        return;
    }
    
    // Execute bot function
    $output = webhook($team_domain, $service_id, $token, $user_name, $team_id, $user_id, $channel_id, $timestamp, $channel_name, $text, $trigger_word, $raw_text);

    // set any keys that aren't blank
    foreach($output as $k => $v) {
        if($output[$k]) {
        	$response->addOutput($k, $output[$k]);
        }
    }

    $response->end();
});