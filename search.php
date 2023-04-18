<!DOCTYPE html>
<!-- TODO USE BOOTSTRAP OR SOMETHING FOR STYLING -->
<html>
    <head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script> <!-- JQuery -->
    <link rel="stylesheet" href="style.css" type="text/css">
    </head>
    <body>
	
	<div id="header">
		
		<div id="search">
			<h1>Search all Ebay Postings</h1>
			<h4 style="margin-bottom: 0;">Enter item to scrape for</h4>
			
			<div style="margin-bottom: 10px;">
				<input id="searchInput" type="text" placeholder="Enter Item">
				<button id="searchSubmitButton">Search</button>
			</div>
			
		  <br>
		 </div>
		 
	</div>
	
	<div id="map-container">
		<div id="map-key">
			<h3>Key by Price</h3>
			<span class="key"> Not Listed <img src="http://labs.google.com/ridefinder/images/mm_20_white.png"></img></span>
			<span class="key"> Low <img src="http://maps.google.com/mapfiles/ms/icons/green-dot.png"></img></span>
			<span class="key"> Mid <img src="http://maps.google.com/mapfiles/ms/icons/yellow-dot.png"></img></span>
			<span class="key"> High <img src="http://maps.google.com/mapfiles/ms/icons/red-dot.png"></img></span>
			<span class="key"> High High <img src="http://maps.google.com/mapfiles/ms/icons/dollar.png"></img></span>
		</div>
		<div id="map"></div>
	</div>
	
        <!--JS goes here-->
        <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBCvhnrxylCh53y2oopThVv5Srl7MFAhwo&callback=initMap"></script>
		<script>
		
		let global_map;
		let markersArray = [];

		
		$("#searchSubmitButton").click(submitSearch);
		
		function submitSearch(e) {
			e.preventDefault();
			clearPins();
			const searchText = $("#searchInput").val();
			console.log("Submitted search:", searchText);
			$.post("scraper.php", {q: searchText}, function(data, status) {
				console.log("Result:", data, status);
				processData(data);
			}).fail(function(xhr, status, error) {
				console.log("Error:", xhr, status, error);
			}) 
		}
		
		
		async function processData(data) {
			
			console.log(Object.keys(data).length);
			
			const sortedItems = Object.entries(data).sort(([, a], [, b]) => {
				const priceA = Number(a.price.replace(/[^0-9.-]+/g, ""));
				const priceB = Number(b.price.replace(/[^0-9.-]+/g, ""));
				return priceA - priceB;
			});
			
			const sortedObject = Object.fromEntries(sortedItems);
			var i = 0;
			for(let obj in data){
				try { 
					var coords = await adrsToCrds(data[obj].location);
					if(i <= Object.keys(data).length/3) addMarker(coords, data[obj].price, data[obj].title, data[obj].url, 'http://maps.google.com/mapfiles/ms/icons/green-dot.png', i , data[obj].img_url);
					else if((i > Object.keys(data).length/3) && (i < (Object.keys(data).length/3)*2)) addMarker(coords, data[obj].price, data[obj].title, data[obj].url, 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png', i , data[obj].img_url);
					else if(i  > (Object.keys(data).length/3)*2) addMarker(coords, data[obj].price, data[obj].title, data[obj].url, 'http://maps.google.com/mapfiles/ms/icons/red-dot.png', i , data[obj].img_url);
				} catch (error) {
					console.log("failed location: ", data[obj].location)
					console.error(error);
			  }
			  i++;
			}
		}
		
		function adrsToCrds(address){
			var geocoder = new google.maps.Geocoder();
			
			return new Promise(function(resolve, reject) {
				geocoder.geocode( { 'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						console.log(results);
						var lati = results[0].geometry.location.lat();
						var lon = results[0].geometry.location.lng();
						resolve({lat: lati, lng: lon});
					} else {
						reject("Geocode was not successful for the following reason: " + status);
					}
				}); 
			});
		}
			
		function clearPins() {
			for (var i = 0; i < markersArray.length; i++ ) {
				if(markersArray[i].pinned == false) markersArray[i].pin.setMap(null);
			}
			markersArray.length = 0;
		}
		
		function initMap() {
			global_map = new google.maps.Map(
				document.getElementById('map'), {zoom: 4, center: {lat: 41.36444, lng: -98.31665}}
			);
			//addMarker({lat: 41.36444, lng: -98.31665}, 100, "placeholder", "placeholder", 'http://labs.google.com/ridefinder/images/mm_20_white.png', 0, "https://i.ebayimg.com/thumbs/images/g/d30AAOSwoSFbQ8ZW/s-l225.jpg");
		}	
		
		let infoWindow;
		let currentId;
		function setPin(color){
			for (var i = 0; i < markersArray.length; i++ ) {
				if(markersArray[i].id == currentId){
					markersArray[i].pin.setIcon(color);
					markersArray[i].pinned = true;
				}
			}
		}
		
		function showPins(){
			let content = `<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/red-pushpin.png')" id="red-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/red-pushpin.png"></img>
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/ylw-pushpin.png')" id="yellow-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/ylw-pushpin.png"></img>
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/grn-pushpin.png')" id="green-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/grn-pushpin.png"></img>
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/ltblu-pushpin.png')"id="ltblu-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/ltblu-pushpin.png"></img>
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/blue-pushpin.png')" id="blue-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/blue-pushpin.png"></img>
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/purple-pushpin.png')" id="purple-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/purple-pushpin.png"></img>              
				<img onClick="setPin('http://maps.google.com/mapfiles/ms/icons/pink-pushpin.png')" id="pink-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/pink-pushpin.png"></img>`
			infoWindow.setContent(content);
		}
		
		function addMarker(coords, price, title, url, color, num, img_url){
			let marker = new google.maps.Marker({position: coords, map: global_map, icon: {url: color} });
			markersArray.push({pin: marker, id: num, pinned: false});
			
			let content = `<h3>${title}: ${price}$</h3> \n <img src=${img_url}> <a href=${url}>view listing<a> <img onClick="showPins()" id="init-pin" class="pin-option" src="http://maps.google.com/mapfiles/ms/icons/red-pushpin.png">`;
			infoWindow = new google.maps.InfoWindow({ content: content });

			marker.addListener('click', function(){
				currentId = num;
				infoWindow.setContent(content);
				infoWindow.open(map, marker);
		  });

		}
		
		</script>
		
    </body>
</html>