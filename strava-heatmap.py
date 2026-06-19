from os import fsencode, fsdecode, listdir, path
from re import findall

INTENSITY = 10
RADIUS = 5

def parseWaypoints(waypointList):
    waypoints = []
    for waypoint in waypointList:
        waypoints.append({
            "lat": waypoint[0],
            "lon": waypoint[1],
            "elevation": waypoint[2],
            "time": waypoint[3]
        })
    return waypoints

def parseActivity(rawActivity):
    activityDetails = findall(r"<metadata>[\S\s].*?<time>(.*?)</time>[\S\s]*<name>(.*?)</name>", rawActivity)[0]
    waypoints = parseWaypoints(findall(r"<trkpt.lat=\"(.*?)\".lon=\"(.*?)\"[\S\s]*?<ele>(.*?)</ele>[\S\s]*?<time>(.*?)</time>", rawActivity))
    return {
        "name": activityDetails[1],
        "date": activityDetails[0],
        "waypoints": waypoints
    }

def generateHeatmapJS(activityList):
    js = 'var heat = L.heatLayer(['
    for activity in activityList:
        for waypoint in activity['waypoints']:
            js += f'[{waypoint["lat"]}, {waypoint["lon"]}, {INTENSITY}],'
    js = js[:-1]
    js += f'], \u007bradius: {RADIUS}\u007d).addTo(map)';
    return js

if __name__ == "__main__":
    with open('index.html', 'w') as outputFile:
        activitiesDirectory = '/Applications/XAMPP/xamppfiles/htdocs/strava-heatmap/export/activities'
        allActivities = [];
        for potentialActivity in listdir(fsencode(activitiesDirectory)):
            filename = fsdecode(potentialActivity)
            if filename.endswith('.gpx'):
                with open(f'{activitiesDirectory}/{filename}') as activity:
                    allActivities.append(parseActivity(activity.read()))
                    
        outputFile.write('<!DOCTYPE html><html><head><link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/></head><style> #map { height: 100vh; } </style><body><div id="map"></div></body><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script><script src="leaflet-heat.js"></script><script>var map = L.map("map").setView([51.505, -0.09], 13); L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",{maxZoom:19,attribution:"&copy;<a href=\'http://www.openstreetmap.org/copyright\'>OpenStreetMap</a>"}).addTo(map);'+generateHeatmapJS(allActivities)+'</script></html>')
