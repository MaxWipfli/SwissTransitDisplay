<?php
$api_url = "https://api.opentransportdata.swiss/trias2020";
$config = json_decode(file_get_contents('config.json'), true);
date_default_timezone_set('Europe/Zurich');

$api_key = $config['api_key'];
$stop_id = $config['stop_id'];
$request_time = substr(date('c'), 0, 19);
$departure_count = $config['departure_count'];

$request = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<Trias version=\"1.1\">
    <ServiceRequest>
        <RequestPayload>
            <StopEventRequest>
                <Location>
                    <LocationRef>
                        <StopPointRef>$stop_id</StopPointRef>
                    </LocationRef>
                    <DepArrTime>$request_time</DepArrTime>
                </Location>
                <Params>
                    <NumberOfResults>$departure_count</NumberOfResults>
                    <StopEventType>departure</StopEventType>
                    <IncludePreviousCalls>false</IncludePreviousCalls>
                    <IncludeOnwardCalls>false</IncludeOnwardCalls>
                    <IncludeRealtimeData>true</IncludeRealtimeData>
                </Params>
            </StopEventRequest>
        </RequestPayload>
    </ServiceRequest>
</Trias>
";

$curl = curl_init($api_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/xml", "Authorization: " . $api_key));
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($curl);

if (curl_errno($curl) || empty($result)) {
    http_response_code(500);
    echo 'An error occured.';
    die();
}

curl_close($curl);

if (isset($_GET['raw'])) {
    header('Content-Type: application/xml');
    echo $result;
    die();
}

$xml = simplexml_load_string($result);
$data = array('stop' => array('id' => $config['stop_id'], 'name' => $config['stop_name']), 'departures' => array());
$id = 0;

foreach ($xml->xpath('//trias:StopEvent') as $stop) {
    $line = strval($stop->xpath('.//trias:PublishedLineName//trias:Text')[0]);
    $destination = strval($stop->xpath('.//trias:DestinationText//trias:Text')[0]);
    $time_estimated_array = $stop->xpath('.//trias:ServiceDeparture//trias:EstimatedTime');

    if (empty($time_estimated_array)) {
        $time = $stop->xpath('.//trias:ServiceDeparture//trias:TimetabledTime')[0];
    } else {
        $time = $time_estimated_array[0];
    }

    $time = date('c', strtotime($time[0]));

    if (isset($config['name_transforms'][$destination])) {
        $destination = $config['name_transforms'][$destination];
    }
    
    $departure = array(
        'id' => $id,
        'line' => $line,
        'destination' => $destination,
        'time' => $time
    );

    array_push($data['departures'], $departure);
    $id++;
}

usort($data['departures'], function ($a, $b) {
    return $a['time'] <=> $b['time'];
});

for ($i = 0; $i < count($data['departures']); $i++) {
    $data['departures'][$i]['time'] = substr($data['departures'][$i]['time'], 11, 5);
}

header('Content-Type: application/json; charset=utf8');
echo json_encode($data, \JSON_UNESCAPED_UNICODE);

?>