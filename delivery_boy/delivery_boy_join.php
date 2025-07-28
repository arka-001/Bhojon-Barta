<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

$success_message = isset($_GET['success']) && $_GET['success'] == 1 
    ? "Your application has been submitted successfully! Await admin approval." 
    : "";
$error_message = "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join as Delivery Boy</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-container {
            max-width: 700px;
            margin: 20px auto;
            padding: 30px;
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.5s ease-in;
        }
        .form-container h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
            display: block;
        }
        .form-control, .form-control-file {
            border-radius: 8px;
            border: 1px solid #dfe6e9;
            padding: 12px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
            outline: none;
        }
        .form-control-file {
            padding: 8px;
        }
        .btn-primary {
            background: #3498db;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #7f8c8d;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9em;
        }
        .btn-secondary:hover {
            background: #6c757d;
        }
        .password-container {
            position: relative;
        }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
        }
        #address-verification-result {
            margin-top: 10px;
            font-size: 0.9em;
        }
        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000 !important;
            border: 1px solid #dfe6e9;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .ui-menu-item {
            padding: 10px;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .ui-menu-item:hover {
            background: #f0f3f5;
        }
        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.85em;
            margin-top: 5px;
            display: none;
        }
        .form-control:invalid + .invalid-feedback {
            display: block;
        }
        input, button, textarea, select {
            pointer-events: auto !important;
            z-index: 20 !important;
        }
        #submit-btn {
            cursor: pointer !important;
            opacity: 1 !important;
            width: 100%;
            font-size: 1.1em;
        }
        #submit-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }
        .form-section-title {
            color: #3498db;
            font-size: 1.4em;
            margin: 30px 0 15px;
            font-weight: 600;
            border-bottom: 2px solid #dfe6e9;
            padding-bottom: 5px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #3498db;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .back-link a:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        #email-verification-result {
            margin-top: 5px;
            font-size: 0.85em;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 576px) {
            .form-container {
                margin: 10px;
                padding: 20px;
            }
            .form-control, .btn-primary {
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Join Our Delivery Team</h1>
        <?php if ($success_message): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?php echo htmlspecialchars($success_message); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            </script>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo htmlspecialchars($error_message); ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            </script>
        <?php endif; ?>
        <form id="deliveryBoyForm" action="./delivery_boy_register.php" method="POST" enctype="multipart/form-data" novalidate>
            <div class="form-group">
                <label for="db_name">Full Name</label>
                <input type="text" class="form-control" id="db_name" name="db_name" value="Arka Maitra" required>
                <div class="invalid-feedback">Please enter your full name.</div>
            </div>
            <div class="form-group">
                <label for="db_phone">Phone Number</label>
                <input type="tel" class="form-control" id="db_phone" name="db_phone" value="7896698569" required pattern="[0-9]{10}" placeholder="10-digit phone number">
                <div class="invalid-feedback">Please enter a valid 10-digit phone number.</div>
            </div>
            <div class="form-group">
                <label for="db_email">Email</label>
                <input type="email" class="form-control" id="db_email" name="db_email" required placeholder="Enter your email">
                <div id="email-verification-result"></div>
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>
            <div class="form-group">
                <label for="db_address">Address</label>
                <textarea class="form-control" id="db_address" name="db_address" required>Kolkata, Kolkata, West Bengal, India</textarea>
                <button type="button" id="verify-address-btn" class="btn btn-secondary mt-2">Verify Address</button>
                <div id="address-verification-result"><span class="text-success">Address verified: Kolkata, West Bengal, India</span></div>
                <div class="invalid-feedback">Please enter your address.</div>
            </div>
            <div class="form-group">
                <label for="city">City</label>
                <input type="text" class="form-control" id="city" name="city" readonly required placeholder="City will be auto-detected">
                <div class="invalid-feedback">City is required.</div>
            </div>
            <div class="form-group">
                <label for="db_photo">Profile Photo</label>
                <input type="file" class="form-control-file" id="db_photo" name="db_photo" accept="image/*">
            </div>
            <div class="form-group">
                <label for="db_password">Password</label>
                <div class="password-container">
                    <input type="password" class="form-control" id="db_password" name="db_password" required>
                    <span class="toggle-password">
                        <i class="fa fa-eye"></i>
                    </span>
                </div>
                <div class="invalid-feedback">Please enter a password.</div>
            </div>
            <div class="form-group">
                <label for="latitude">Latitude</label>
                <input type="number" step="any" class="form-control" id="latitude" name="latitude" value="22.5726459" required readonly placeholder="Set after address verification">
                <div class="invalid-feedback">Latitude is required.</div>
            </div>
            <div class="form-group">
                <label for="longitude">Longitude</label>
                <input type="number" step="any" class="form-control" id="longitude" name="longitude" value="88.3638953" required readonly placeholder="Set after address verification">
                <div class="invalid-feedback">Longitude is required.</div>
            </div>
            <h4 class="form-section-title">Bank Details</h4>
            <div class="form-group">
                <label for="bank_account_number">Bank Account Number</label>
                <input type="text" class="form-control" id="bank_account_number" name="db_account_number" value="456985696" required pattern="[0-9]{9,18}" placeholder="9-18 digit account number">
                <div class="invalid-feedback">Please enter a valid 9-18 digit account number.</div>
            </div>
            <div class="form-group">
                <label for="ifsc_code">IFSC Code</label>
                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" value="SBIN0000691" required pattern="[A-Z]{4}0[A-Z0-9]{6}" placeholder="e.g., SBIN0000691">
                <div class="invalid-feedback">Please enter a valid IFSC code (e.g., SBIN0000691).</div>
            </div>
            <div class="form-group">
                <label for="account_holder_name">Account Holder Name</label>
                <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" value="arka maitra" required>
                <div class="invalid-feedback">Please enter the account holder's name.</div>
            </div>
            <h4 class="form-section-title">Driving License Details</h4>
            <div class="form-group">
                <label for="driving_license_number">Driving License Number</label>
                <input type="text" class="form-control" id="driving_license_number" name="driving_license_number" value="WB-20-2022-004567" required pattern="[A-Z0-9\-]{5,20}" placeholder="e.g., WB-20-2022-004567">
                <div class="invalid-feedback">Please enter a valid driving license number (e.g., WB-20-2022-004567).</div>
            </div>
            <div class="form-group">
                <label for="driving_license_expiry">Driving License Expiry Date</label>
                <input type="date" class="form-control" id="driving_license_expiry" name="driving_license_expiry" value="2026-12-10" required min="<?php echo date('Y-m-d'); ?>">
                <div class="invalid-feedback">Please select a valid expiry date.</div>
            </div>
            <div class="form-group">
                <label for="driving_license_photo">Driving License Photo</label>
                <input type="file" class="form-control-file" id="driving_license_photo" name="driving_license_photo" accept="image/*" required>
                <div class="invalid-feedback">Please upload a driving license photo.</div>
            </div>
            <h4 class="form-section-title">Identity Verification</h4>
            <div class="form-group">
                <label for="aadhaar_pdf">Aadhaar Card (PDF)</label>
                <input type="file" class="form-control-file" id="aadhaar_pdf" name="aadhaar_pdf" accept="application/pdf" required>
                <div class="invalid-feedback">Please upload an Aadhaar card PDF.</div>
            </div>
            <button type="submit" class="btn btn-primary" id="submit-btn" disabled>Submit Application</button>
        </form>
        <div class="back-link">
            <a href="index.php">Back to Login</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            console.log('Document ready executed');

            // Force submit button initially disabled
            $('#submit-btn').prop('disabled', true);
            console.log('Submit button initially disabled');
            let isEmailValid = false;

            // City normalization function
            function normalizeCity(city) {
                if (!city) return '';
                city = city.trim().toLowerCase();
                const cityMap = {
                    'calcutta': 'Kolkata',
                    'kolkata city': 'Kolkata',
                    'kolkata urban': 'Kolkata'
                };
                return cityMap[city] || city.charAt(0).toUpperCase() + city.slice(1);
            }

            // Email validation
            $('#db_email').on('input change', function() {
                const email = $(this).val().trim();
                console.log('Email input: ' + email);
                if (email) {
                    $.ajax({
                        url: './check_email.php',
                        type: 'POST',
                        data: { email: email },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Email check response: ', response);
                            if (response.exists) {
                                isEmailValid = false;
                                $('#email-verification-result').html(
                                    '<span class="text-danger">This email is already in use.</span>'
                                );
                                $('#submit-btn').prop('disabled', true);
                            } else {
                                isEmailValid = true;
                                $('#email-verification-result').html(
                                    '<span class="text-success">Email is available.</span>'
                                );
                                // Check if city is also valid to enable submit button
                                if ($('#city').val().trim()) {
                                    $('#submit-btn').prop('disabled', false);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Email check error: ', status, error, xhr.responseText);
                            isEmailValid = false;
                            $('#email-verification-result').html(
                                '<span class="text-danger">Error checking email availability.</span>'
                            );
                            $('#submit-btn').prop('disabled', true);
                        }
                    });
                } else {
                    isEmailValid = false;
                    $('#email-verification-result').html(
                        '<span class="text-danger">Please enter an email address.</span>'
                    );
                    $('#submit-btn').prop('disabled', true);
                }
            });

            // Password toggle
            $('.toggle-password').on('click', function() {
                console.log('Toggle password clicked');
                const passwordField = $('#db_password');
                const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
                passwordField.attr('type', type);
                $(this).find('i').toggleClass('fa-eye fa-eye-slash');
            });

            // Address autocomplete
            $('#db_address').autocomplete({
                source: function(request, response) {
                    console.log('Autocomplete request: ' + request.term);
                    $.ajax({
                        url: './autocomplete_address.php',
                        type: 'POST',
                        data: { query: request.term },
                        dataType: 'json',
                        success: function(data) {
                            console.log('Autocomplete response: ', data);
                            if (data.success && data.suggestions) {
                                response($.map(data.suggestions, function(item) {
                                    return {
                                        label: item.display_name,
                                        value: item.display_name,
                                        lat: item.lat,
                                        lon: item.lon
                                    };
                                }));
                            } else {
                                response([]);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Autocomplete Error',
                                    text: 'Cannot fetch address suggestions.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Autocomplete error: ', status, error, xhr.responseText);
                            response([]);
                            Swal.fire({
                                icon: 'error',
                                title: 'Autocomplete Error',
                                text: 'Cannot fetch address suggestions.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    });
                },
                minLength: 3,
                select: function(event, ui) {
                    console.log('Autocomplete selected: ', ui.item);
                    $('#db_address').val(ui.item.value);
                    $('#latitude').val(ui.item.lat);
                    $('#longitude').val(ui.item.lon);
                    $('#address-verification-result').html(
                        '<span class="text-success">Address selected: ' + ui.item.value + '</span>'
                    );

                    // Fallback city extraction
                    let fallbackCity = ui.item.value.split(',')[0].trim();
                    console.log('Fallback city from address: ' + fallbackCity);

                    $.ajax({
                        url: './reverse_geocode.php',
                        type: 'POST',
                        data: { lat: ui.item.lat, lon: ui.item.lon },
                        dataType: 'json',
                        success: function(response) {
                            console.log('Reverse geocode response: ', response);
                            let detectedCity = response.success && response.city ? response.city : fallbackCity;
                            detectedCity = normalizeCity(detectedCity);
                            console.log('Normalized city: ' + detectedCity);

                            if (detectedCity) {
                                $.ajax({
                                    url: './check_city.php',
                                    type: 'POST',
                                    data: { city: detectedCity },
                                    dataType: 'json',
                                    success: function(cityCheck) {
                                        console.log('City check response: ', cityCheck);
                                        if (cityCheck.is_available) {
                                            $('#city').val(detectedCity);
                                            if (isEmailValid) {
                                                $('#submit-btn').prop('disabled', false);
                                            }
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'City Detected',
                                                text: 'Service available in ' + detectedCity,
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        } else {
                                            console.log('City not available: ' + detectedCity);
                                            $('#city').val('');
                                            $('#submit-btn').prop('disabled', true);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Service Unavailable',
                                                text: 'Sorry, our service is not available in this area.',
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('City check error: ', status, error, xhr.responseText);
                                        $('#city').val('');
                                        $('#submit-btn').prop('disabled', true);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Service Unavailable',
                                            text: 'Sorry, our service is not available in this area.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    }
                                });
                            } else {
                                console.log('No city detected: ', response);
                                $('#city').val('');
                                $('#submit-btn').prop('disabled', true);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'City Detection Failed',
                                    text: 'Sorry, our service is not available in this area.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Reverse geocode error: ', status, error, xhr.responseText);
                            detectedCity = normalizeCity(fallbackCity);
                            console.log('Using fallback city: ' + detectedCity);

                            if (detectedCity) {
                                $.ajax({
                                    url: './check_city.php',
                                    type: 'POST',
                                    data: { city: detectedCity },
                                    dataType: 'json',
                                    success: function(cityCheck) {
                                        console.log('City check response (fallback): ', cityCheck);
                                        if (cityCheck.is_available) {
                                            $('#city').val(detectedCity);
                                            if (isEmailValid) {
                                                $('#submit-btn').prop('disabled', false);
                                            }
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'City Detected',
                                                text: 'Service available in ' + detectedCity,
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        } else {
                                            console.log('Fallback city not available: ' + detectedCity);
                                            $('#city').val('');
                                            $('#submit-btn').prop('disabled', true);
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Service Unavailable',
                                                text: 'Sorry, our service is not available in this area.',
                                                timer: 2000,
                                                showConfirmButton: false
                                            });
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('City check error (fallback): ', status, error, xhr.responseText);
                                        $('#city').val('');
                                        $('#submit-btn').prop('disabled', true);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Service Unavailable',
                                            text: 'Sorry, our service is not available in this area.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    }
                                });
                            } else {
                                $('#city').val('');
                                $('#submit-btn').prop('disabled', true);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'City Detection Failed',
                                    text: 'Sorry, our service is not available in this area.',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        }
                    });
                    return false;
                }
            });

            // Address verification
            $('#verify-address-btn').on('click', function() {
                console.log('Verify Address button clicked');
                const address = $('#db_address').val().trim();

                if (!address) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Address',
                        text: 'Please enter an address.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }

                // Fallback city extraction
                let fallbackCity = address.split(',')[0].trim();
                console.log('Fallback city from address: ' + fallbackCity);

                $.ajax({
                    url: './geocode_address.php',
                    type: 'POST',
                    data: { address: address },
                    dataType: 'json',
                    beforeSend: function() {
                        $('#verify-address-btn').prop('disabled', true).text('Verifying...');
                    },
                    success: function(response) {
                        $('#verify-address-btn').prop('disabled', false).text('Verify Address');
                        console.log('Geocode response: ', response);
                        if (response.success) {
                            $('#latitude').val(response.data.lat);
                            $('#longitude').val(response.data.lon);
                            $('#address-verification-result').html(
                                '<span class="text-success">Address verified: ' + response.data.display_name + '</span>'
                            );

                            $.ajax({
                                url: './reverse_geocode.php',
                                type: 'POST',
                                data: { lat: response.data.lat, lon: response.data.lon },
                                dataType: 'json',
                                success: function(geoResponse) {
                                    console.log('Reverse geocode response: ', geoResponse);
                                    let detectedCity = geoResponse.success && geoResponse.city ? geoResponse.city : fallbackCity;
                                    detectedCity = normalizeCity(detectedCity);
                                    console.log('Normalized city: ' + detectedCity);

                                    if (detectedCity) {
                                        $.ajax({
                                            url: './check_city.php',
                                            type: 'POST',
                                            data: { city: detectedCity },
                                            dataType: 'json',
                                            success: function(cityCheck) {
                                                console.log('City check response: ', cityCheck);
                                                if (cityCheck.is_available) {
                                                    $('#city').val(detectedCity);
                                                    if (isEmailValid) {
                                                        $('#submit-btn').prop('disabled', false);
                                                    }
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Address Verified',
                                                        text: 'Service available in ' + detectedCity,
                                                        timer: 2000,
                                                        showConfirmButton: false
                                                    });
                                                } else {
                                                    console.log('City not available: ' + detectedCity);
                                                    $('#city').val('');
                                                    $('#submit-btn').prop('disabled', true);
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Service Unavailable',
                                                        text: 'Sorry, our service is not available in this area.',
                                                        timer: 2000,
                                                        showConfirmButton: false
                                                    });
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.log('City check error: ', status, error, xhr.responseText);
                                                $('#city').val('');
                                                $('#submit-btn').prop('disabled', true);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Service Unavailable',
                                                    text: 'Sorry, our service is not available in this area.',
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });
                                            }
                                        });
                                    } else {
                                        console.log('No city detected: ', geoResponse);
                                        $('#city').val('');
                                        $('#submit-btn').prop('disabled', true);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'City Detection Failed',
                                            text: 'Sorry, our service is not available in this area.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.log('Reverse geocode error: ', status, error, xhr.responseText);
                                    detectedCity = normalizeCity(fallbackCity);
                                    console.log('Using fallback city: ' + detectedCity);

                                    if (detectedCity) {
                                        $.ajax({
                                            url: './check_city.php',
                                            type: 'POST',
                                            data: { city: detectedCity },
                                            dataType: 'json',
                                            success: function(cityCheck) {
                                                console.log('City check response (fallback): ', cityCheck);
                                                if (cityCheck.is_available) {
                                                    $('#city').val(detectedCity);
                                                    if (isEmailValid) {
                                                        $('#submit-btn').prop('disabled', false);
                                                    }
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Address Verified',
                                                        text: 'Service available in ' + detectedCity,
                                                        timer: 2000,
                                                        showConfirmButton: false
                                                    });
                                                } else {
                                                    console.log('Fallback city not available: ' + detectedCity);
                                                    $('#city').val('');
                                                    $('#submit-btn').prop('disabled', true);
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Service Unavailable',
                                                        text: 'Sorry, our service is not available in this area.',
                                                        timer: 2000,
                                                        showConfirmButton: false
                                                    });
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.log('City check error (fallback): ', status, error, xhr.responseText);
                                                $('#city').val('');
                                                $('#submit-btn').prop('disabled', true);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Service Unavailable',
                                                    text: 'Sorry, our service is not available in this area.',
                                                    timer: 2000,
                                                    showConfirmButton: false
                                                });
                                            }
                                        });
                                    } else {
                                        $('#city').val('');
                                        $('#submit-btn').prop('disabled', true);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'City Detection Failed',
                                            text: 'Sorry, our service is not available in this area.',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    }
                                }
                            });
                        } else {
                            $('#latitude').val('');
                            $('#longitude').val('');
                            $('#city').val('');
                            $('#address-verification-result').html(
                                '<span class="text-danger">' + response.message + '</span>'
                            );
                            $('#submit-btn').prop('disabled', true);
                            Swal.fire({
                                icon: 'error',
                                title: 'Verification Failed',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#verify-address-btn').prop('disabled', false).text('Verify Address');
                        console.log('Geocode error: ', status, error, xhr.responseText);
                        $('#latitude').val('');
                        $('#longitude').val('');
                        $('#city').val('');
                        $('#address-verification-result').html(
                            '<span class="text-danger">Cannot connect to geocoding service.</span>'
                        );
                        $('#submit-btn').prop('disabled', true);
                        Swal.fire({
                            icon: 'error',
                            title: 'Geocoding Error',
                            text: 'Cannot connect to geocoding service.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            });

            // Form validation and submission
            $('#deliveryBoyForm').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submit triggered');

                let isValid = true;
                const requiredFields = [
                    'db_name', 'db_phone', 'db_email', 'db_address', 'city', 'db_password',
                    'latitude', 'longitude', 'bank_account_number', 'ifsc_code',
                    'account_holder_name', 'driving_license_number', 'driving_license_expiry',
                    'driving_license_photo', 'aadhaar_pdf'
                ];
                requiredFields.forEach(function(field) {
                    const $field = $('#' + field);
                    if ($field.is(':invalid') || !$field.val() || ($field.attr('type') === 'file' && !$field[0].files.length)) {
                        console.log('Validation failed for: ' + field + ', Value: ' + $field.val());
                        isValid = false;
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Form',
                        text: 'Please fill all required fields correctly, including file uploads.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                if (!isEmailValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Email',
                        text: 'The email is already in use. Please use a different email.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                    return;
                }

                const formData = new FormData(this);
                $.ajax({
                    url: './delivery_boy_register.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    beforeSend: function() {
                        $('#submit-btn').prop('disabled', true).text('Submitting...');
                    },
                    success: function(response) {
                        $('#submit-btn').prop('disabled', false).text('Submit Application');
                        console.log('Form submission response: ', response);
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Application Submitted',
                                text: 'Your application has been sent for admin approval.',
                                timer: 3000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.href = 'index.php';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Submission Failed',
                                text: response.message || 'An error occurred.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#submit-btn').prop('disabled', false).text('Submit Application');
                        console.log('Form submission error: ', status, error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Submission Error',
                            text: 'Failed to connect to delivery_boy_register.php.',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                });
            });

            // Debug click and input events
            $('input, button, textarea, select').on('click', function(e) {
                console.log('Element clicked: ' + $(this).attr('id') + ', Event: ', e);
            });
            $('input, textarea, select').on('input change', function() {
                const $field = $(this);
                console.log('Field changed: ' + $field.attr('id') + ', Valid: ' + $field[0].checkValidity() + ', Value: ' + $field.val());
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($db); ?>