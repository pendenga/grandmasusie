var GMapperMarker;
GMapperApplication = Class.create();
GMapperApplication.prototype = {
	initialized: false,
	initialize: function(mapDiv) {
		this.map = new GMap2(mapDiv);
		this.map.addControl(new GSmallMapControl());
		this.map.addControl(new GMapperTypeControl(), new GControlPosition(G_ANCHOR_TOP_RIGHT, new GSize(7, 7)));

		// bind events
		//GEvent.bind(this.map, "click", this, this.onMapClick);
		GEvent.addListener(this.map, 'click', this.onMapClick);

		// hide copyrights and google logo (too small)
		mapDiv.childNodes[1].style.display = 'none';
		mapDiv.childNodes[2].style.display = 'none';	
	},
	setCenter: function(lat, lon, zoom) {
		this.posn = new GLatLng(37.4419, -122.1419);
		this.map.setCenter(this.posn, 13);
		this.initialized = true;

		GMapperMarker = new GMarker(this.posn, { draggable: true }); 
		this.map.addOverlay(GMapperMarker);
		GMapperMarker.hide();
	},
	onMapClick: function(overlay, clickedPoint) {
		GMapperMarker.setPoint(clickedPoint);
		GMapperMarker.show();

		//alert(clickedPoint);
		/*
		if (this.marker == false) {

		}
		this.counter++;
		alert("You have clicked " + this.counter + " times on the map... cut it out already");
		*/
	}
}

// START GMapperTypeControl Definition
function GMapperTypeControl() {}
GMapperTypeControl.prototype = new GControl();
GMapperTypeControl.printable = function() { return false; }
GMapperTypeControl.selecteable = function() { return false; }
GMapperTypeControl.prototype.initialize = function(map) {
	var container = document.createElement("div");
	var mapperTypeDiv = document.createElement("div");
	mapperTypeDiv.id = 'mapperTypeDiv';
	container.appendChild(mapperTypeDiv);
	mapperTypeDiv.appendChild(document.createTextNode("Switch to Hybrid"));

	// click handler for landmarks button
	GEvent.addDomListener(mapperTypeDiv, "click", function() {
		if (this.innerHTML == 'Switch to Map') {
			map.setMapType(G_NORMAL_MAP);
			this.innerHTML = 'Switch to Hybrid';
		} else if (this.innerHTML == 'Switch to Hybrid') {
			map.setMapType(G_HYBRID_MAP);
			this.innerHTML = 'Switch to Satellite';			
		} else {
			map.setMapType(G_SATELLITE_MAP);
			this.innerHTML = 'Switch to Map';
		}
	});
	map.getContainer().appendChild(container);
	return container;
}
// END GMapperTypeControl Definition