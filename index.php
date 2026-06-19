<!DOCTYPE html>

<?php
$INTENSITY = 10;
$RADIUS = 5;

function parseWaypoints($waypointList){
    $waypoints = [];
    foreach ($waypointList as $waypoint) {
        $waypoints[] = {
            "lat": $waypoint[0],
                "lon": $waypoint[1],
                "elevation": $waypoint[2],
                "time": $waypoint[3]
        };
    }
    return $waypoints;
}

function parseActivity($rawActivity){
    $activityDetails = findall(r"<metadata>[\S\s].*?<time>(.*?)</time>[\S\s]*<name>(.*?)</name>", $rawActivity)[0];
    $waypoints = parseWaypoints(findall(r"<trkpt.lat=\"(.*?)\".lon=\"(.*?)\"[\S\s]*?<ele>(.*?)</ele>[\S\s]*?<time>(.*?)</time>", $rawActivity));
    return {
        "name": $activityDetails[1],
            "date": $activityDetails[0],
            "waypoints": $waypoints
            };
}

function generateHeatmapJS($activityList){
    $js = 'var heat = L.heatLayer([';
    foreach ($activityList as $activity){
        foreach ($activity['waypoints'] as $waypoint){
            $js .= f'[{waypoint["lat"]}, {waypoint["lon"]}, {INTENSITY}],';
        }
    }
    $js = $js[:-1];
    $js .= f'], \u007bradius: {RADIUS}\u007d).addTo(map)';
    return $js;
}

$exportDirectory = 'export';
$exportDirectoryIterator = new DirectoryIterator($exportDirectory);
$allActivities = [];
foreach ($exportDirectoryIterator as $potentialActivity){
    if (pathinfo($potentialActivity, PATHINFO_EXTENSION) == 'gpx'){
        allActivities[] = parseActivity(file_get_contents($potentialActivity));
    }
}
?>

<html>
    <head>
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    </head>
    
    <style>
     #map { height: 100vh; }
    </style>
    
    <body>
	<div id="map"></div>
    </body>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="leaflet-heat.js"></script>

    <script>
     var map = L.map("map").setView([51.505, -0.09], 13);
     L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
	 maxZoom: 19,
	 attribution: "&copy;<a href=\'http://www.openstreetmap.org/copyright\'>OpenStreetMap</a>"
     }).addTo(map);

     '+generateHeatmapJS(allActivities)+'
    </script>
</html>
