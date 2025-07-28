<?php
include("../connection/connect.php");
$order_id = $_GET['order_id'] ?? null;
if (!$order_id) die('Invalid order ID');

$order = $db->query("SELECT uo.*, u.latitude as user_lat, u.longitude as user_lon 
                    FROM users_orders uo 
                    LEFT JOIN users u ON uo.u_id = u.u_id 
                    WHERE uo.o_id = $order_id")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://maps.gomaps.pro/maps/api/js?key=AlzaSy_ZZIUzey6h4dUkRVDxLYlH5iUYF__W5qi&libraries=directions"></script>
    <style>
        body {
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .tracking-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        #map {
            height: 500px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .status-info {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .distance-info {
            font-size: 0.9em;
            color: #555;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="tracking-container py-5">
        <div class="card">
            <div class="card-body">
                <h1 class="text-center mb-4">Track Order #<?php echo $order_id; ?></h1>
                <div class="status-info text-center">
                    <h5>Order Details</h5>
                    <p>
                        <strong>Item:</strong> <?php echo htmlspecialchars($order['title']); ?> (x<?php echo $order['quantity']; ?>)<br>
                        <strong>Price:</strong> ₹<?php echo number_format($order['price'], 2); ?><br>
                        <strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span><br>
                        <span class="distance-info" id="distance"></span>
                    </p>
                </div>
                <div id="map"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let map, deliveryMarker, directionsRenderer;
    const ws = new WebSocket('ws://localhost:8080');
    const apiKey = "AlzaSybluA0kb6IWWhrKW5hm36p_o9yLsi7VN9B";
    const directionsService = new google.maps.DirectionsService();

    function initMap() {
        const customerLat = <?php echo $order['customer_lat'] ?? $order['user_lat'] ?: 0; ?>;
        const customerLon = <?php echo $order['customer_lon'] ?? $order['user_lon'] ?: 0; ?>;
        
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 13,
            center: {lat: customerLat, lng: customerLon}
        });
        
        deliveryMarker = new google.maps.Marker({
            map: map,
            title: 'Delivery Agent',
            icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
        });
        
        new google.maps.Marker({
            position: {lat: customerLat, lng: customerLon},
            map: map,
            title: 'Your Location',
            icon: 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
        });

        directionsRenderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            polylineOptions: {
                strokeColor: '#FF0000',
                strokeWeight: 4
            }
        });
    }

    function updateRoute(origin, destination) {
        directionsService.route({
            origin: origin,
            destination: destination,
            travelMode: google.maps.TravelMode.DRIVING
        }, (result, status) => {
            if (status === google.maps.DirectionsStatus.OK) {
                directionsRenderer.setDirections(result);
                const distance = result.routes[0].legs[0].distance.text;
                const duration = result.routes[0].legs[0].duration.text;
                document.getElementById('distance').textContent = `Distance: ${distance} (${duration})`;
                map.fitBounds(result.routes[0].bounds); // Auto-zoom to fit route
            } else {
                console.error('Directions request failed due to ' + status);
                document.getElementById('distance').textContent = 'Route unavailable';
            }
        });
    }

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        if (data.order_id == <?php echo $order_id; ?> && data.type === 'location_update') {
            const newPos = {lat: parseFloat(data.latitude), lng: parseFloat(data.longitude)};
            deliveryMarker.setPosition(newPos);
            updateRoute(newPos, {
                lat: <?php echo $order['customer_lat'] ?? $order['user_lat'] ?: 0; ?>,
                lng: <?php echo $order['customer_lon'] ?? $order['user_lon'] ?: 0; ?>
            });
            fetch(`https://maps.gomaps.pro/maps/api/geocode/json?latlng=${data.latitude},${data.longitude}&key=${apiKey}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'OK') {
                        console.log('Delivery Agent Location:', data.results[0].formatted_address);
                    }
                });
        }
    };

    window.onload = initMap;
    </script>
</body>
</html>