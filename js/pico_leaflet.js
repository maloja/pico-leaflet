var osmlayer =  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        });
var ortholayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: 'Tiles &copy; Esri | Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, GIS User Community',
                        });

var hikelayer =  L.tileLayer('https://tiles.wmflabs.org/hikebike/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        });

var map_<<$id>> = new L.map('map_<<$id>>' , {
    center: [ <<$this->argp[$id]['center']>> ],
    zoom: <<$this->argp[$id]['zoom']>>,
    maxZoom: 18,
	gestureHandling: true,
    fullscreenControl: true,
    fullscreenControlOptions: {
        position: 'topleft'
    },
    layers: [ osmlayer ],
});

var baseLayers = {
    'Strasse': osmlayer,
    'Wanderwege': hikelayer,
    'Luftbild': ortholayer,
};

var control = L.control.layers(baseLayers).addTo(map_<<$id>>);
L.control.scale({imperial: false, position: 'bottomright' }).addTo(map_<<$id>>);

map_<<$id>>.on('layeradd', function(e) {
    var bounds = new L.LatLngBounds();
    map_<<$id>>.eachLayer(function (layer) {
        if (layer instanceof L.FeatureGroup) {
            bounds.extend(layer.getBounds());
        }
    });
    if (e.layer instanceof L.FeatureGroup) {
        	if (bounds.isValid() ) {
            	map_<<$id>>.fitBounds(bounds);
				if (<<$this->argp[$id]['zoom']>> > 0 ) {
					map_<<$id>>.setZoom(<<$this->argp[$id]['zoom']>>);
				}
        	} else {
            	map_<<$id>>.fitWorld();
        	}
    }
});

var leaflet_isfullscreen = false;

map_<<$id>>.on('enterFullscreen', function(){
    map_<<$id>>.dragging.enable();
    map_<<$id>>.scrollWheelZoom.enable();
    leaflet_isfullscreen = true;
});

map_<<$id>>.on('exitFullscreen', function(){
    map_<<$id>>.dragging.disable();
    map_<<$id>>.scrollWheelZoom.disable();
    leaflet_isfullscreen = false;
});

//dynamic datas for map_<<$id>>
