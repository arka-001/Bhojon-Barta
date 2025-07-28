<?php
session_start();
include("../connection/connect.php"); // Adjusted path assuming form is in root

// Fetch restaurant categories for the dropdown
$sql_categories = "SELECT c_id, c_name FROM res_category WHERE c_id > 0 ORDER BY c_name"; // Exclude potential 0 ID
$query_categories = mysqli_query($db, $sql_categories);
$categories = []; // Initialize as empty array
if ($query_categories) {
    $categories = mysqli_fetch_all($query_categories, MYSQLI_ASSOC);
} else {
    error_log("Error fetching categories: " . mysqli_error($db));
    // Optionally display an error to the user or use a default list
}


// Retrieve error/success messages from session if redirected
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join as Restaurant Owner - Online Food Ordering</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7f6; /* Lighter, slightly greenish gray */
            font-family: 'Inter', sans-serif; /* A more modern font, add Google Font link if needed */
        }
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        .container.main-container {
            max-width: 850px; /* Slightly wider for better spacing */
            margin-top: 40px;
            margin-bottom: 60px;
            background: white;
            padding: 25px 40px; /* More horizontal padding */
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .form-section {
            background-color: #ffffff;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef; /* Subtle border for sections */
        }

        .section-heading {
            font-size: 1.5rem; /* Larger heading for sections */
            font-weight: 600;
            color: #343a40; /* Darker heading color */
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0d6efd; /* Primary color accent */
            display: flex;
            align-items: center;
        }
        .section-heading i {
            margin-right: 12px;
            color: #0d6efd; /* Primary color for icons */
        }

        .form-label {
            font-weight: 500;
            color: #495057;
        }

        /* Floating labels customization */
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label,
        .form-floating > .form-select ~ label {
            color: #0d6efd;
        }
        .form-floating > .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }


        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            font-size: 100px;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-button {
            border: 1px solid #ced4da;
            padding: .375rem .75rem;
            border-radius: .25rem;
            background-color: #e9ecef;
            display: inline-flex; /* For icon alignment */
            align-items: center;
        }
        .file-input-button i {
            margin-right: 8px;
        }
        .file-name-display {
            margin-top: 5px;
            font-size: 0.85em;
            color: #6c757d;
        }
        #imagePreview {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
            display: none; /* Hidden by default */
        }


        .spinner-border-sm { vertical-align: middle; }
        #city-status { margin-top: 5px; font-size: 0.9em; height: 20px; }
        .is-valid + #city-status { color: green; }
        .is-invalid + #city-status { color: red; }

        .password-strength-meter {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            margin-top: 5px;
            overflow: hidden; /* To make the inner bar rounded */
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease-in-out, background-color 0.3s ease-in-out;
        }
        .password-strength-text {
            font-size: 0.8em;
            margin-top: 3px;
        }
        .input-group .form-control { /* Fix for password toggle button border radius */
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .btn-primary {
            background-color: #0d6efd; /* Consistent primary color */
            border-color: #0d6efd;
            padding: 0.75rem 1.25rem; /* Larger padding for main button */
            font-size: 1.1rem;
        }
         .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }

        .logo-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-header img {
            max-height: 60px; /* Adjust as needed */
        }
        .platform-title {
            font-size: 2rem;
            font-weight: 700;
            color: #343a40;
        }
         .platform-subtitle {
            font-size: 1rem;
            color: #6c757d;
        }

    </style>
</head>
<body>

    <div class="container main-container">
        <div class="logo-header">
            <!-- Optional: Add your logo here -->
            <!-- <img src="path/to/your/logo.png" alt="Platform Logo"> -->
            <h1 class="platform-title">Partner with Us</h1>
            <p class="platform-subtitle">Join our network of successful restaurants and reach more customers.</p>
        </div>

        <!-- Display Feedback Messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form id="joinForm" action="process_join_request.php" method="post" enctype="multipart/form-data" novalidate>

            <div class="form-section">
                <h3 class="section-heading"><i class="fas fa-user-circle"></i>Personal Details</h3>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="name" name="name" placeholder="Your Full Name" required value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>">
                    <label for="name">Full Name <span class="text-danger">*</span></label>
                    <div class="invalid-feedback">Please enter your full name.</div>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="email" class="form-control" id="email" name="email" placeholder="your@email.com" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
                            <label for="email">Email <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="9876543210" required pattern="[0-9]{10,15}" value="<?php echo htmlspecialchars($form_data['phone'] ?? ''); ?>">
                            <label for="phone">Phone <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Please enter a valid phone number (10-15 digits).</div>
                        </div>
                    </div>
                </div>
                 <div class="form-floating mb-3">
                    <div class="input-group">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Create a Password" required minlength="8">
                            <label for="password">Password <span class="text-danger">*</span></label>
                        </div>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="password-feedback">Password must be at least 8 characters.</div>
                    <div class="password-strength-meter mt-2">
                        <div class="password-strength-bar" id="passwordStrengthBar"></div>
                    </div>
                    <div class="password-strength-text" id="passwordStrengthText"></div>
                </div>
            </div>


            <div class="form-section">
                <h3 class="section-heading"><i class="fas fa-file-alt"></i>Required Documents</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="restaurant_photo" class="form-label">Restaurant Photo <small class="text-muted">(JPG/PNG, max 2MB)</small></label>
                        <div class="file-input-wrapper">
                            <button class="btn file-input-button w-100" type="button"><i class="fas fa-upload"></i> Choose file</button>
                            <input type="file" class="form-control" id="restaurant_photo" name="restaurant_photo" accept=".jpg,.jpeg,.png" onchange="previewImage(event); displayFileName(this, 'restaurant_photo_name');">
                        </div>
                        <div class="file-name-display" id="restaurant_photo_name">No file chosen</div>
                        <img id="imagePreview" src="#" alt="Image Preview"/>
                        <div class="invalid-feedback">Please select a valid image file (jpg, png).</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fssai_license" class="form-label">FSSAI License <small class="text-muted">(PDF, max 5MB)</small></label>
                        <div class="file-input-wrapper">
                             <button class="btn file-input-button w-100" type="button"><i class="fas fa-upload"></i> Choose file</button>
                            <input type="file" class="form-control" id="fssai_license" name="fssai_license" accept=".pdf" onchange="displayFileName(this, 'fssai_license_name');">
                        </div>
                        <div class="file-name-display" id="fssai_license_name">No file chosen</div>
                        <div class="invalid-feedback">Please select a valid PDF file.</div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="aadhar_card" class="form-label">Aadhar Card <small class="text-muted">(PDF, max 5MB)</small></label>
                         <div class="file-input-wrapper">
                             <button class="btn file-input-button w-100" type="button"><i class="fas fa-upload"></i> Choose file</button>
                            <input type="file" class="form-control" id="aadhar_card" name="aadhar_card" accept=".pdf" onchange="displayFileName(this, 'aadhar_card_name');">
                        </div>
                        <div class="file-name-display" id="aadhar_card_name">No file chosen</div>
                        <div class="invalid-feedback">Please select a valid PDF file.</div>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3 class="section-heading"><i class="fas fa-university"></i>Bank Details (for Payouts)</h3>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="account_holder_name" name="account_holder_name" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($form_data['account_holder_name'] ?? ''); ?>">
                            <label for="account_holder_name">Account Holder Name <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Please enter the account holder's name.</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="bank_account_number" name="bank_account_number" placeholder="Enter Account Number" required pattern="[0-9]{5,20}" value="<?php echo htmlspecialchars($form_data['bank_account_number'] ?? ''); ?>">
                            <label for="bank_account_number">Bank Account Number <span class="text-danger">*</span></label>
                            <div class="invalid-feedback">Please enter a valid bank account number (5-20 digits).</div>
                        </div>
                    </div>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control text-uppercase" id="ifsc_code" name="ifsc_code" placeholder="e.g. SBIN0001234" required pattern="^[A-Z]{4}0[A-Z0-9]{6}$" value="<?php echo htmlspecialchars(strtoupper($form_data['ifsc_code'] ?? '')); ?>">
                    <label for="ifsc_code">IFSC Code <span class="text-danger">*</span></label>
                    <div class="invalid-feedback">Please enter a valid 11-character IFSC code.</div>
                    <small class="form-text text-muted">Must be 11 characters, format: XXXX0XXXXXX (fifth character is zero).</small>
                </div>
            </div>


            <div class="form-section">
                <h3 class="section-heading"><i class="fas fa-utensils"></i>Restaurant Information</h3>
                <div class="form-floating mb-3">
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="" selected disabled>Select a category</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['c_id']; ?>" <?php echo (isset($form_data['category_id']) && $form_data['category_id'] == $category['c_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['c_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>Error loading categories</option>
                        <?php endif; ?>
                    </select>
                    <label for="category_id">Restaurant Category <span class="text-danger">*</span></label>
                    <div class="invalid-feedback">Please select a restaurant category.</div>
                </div>
                <div class="form-floating mb-3">
                    <textarea class="form-control" id="address" name="address" placeholder="Full Restaurant Address" rows="3" style="height: 100px;" required><?php echo htmlspecialchars($form_data['address'] ?? ''); ?></textarea>
                    <label for="address">Full Address <span class="text-danger">*</span></label>
                    <div class="invalid-feedback">Please enter the full restaurant address.</div>
                </div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-1 mb-3" id="geocodeBtn">
                    <span id="geocodeSpinner" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="display: none;"></span>
                    <i class="fas fa-map-marker-alt me-1"></i> Verify Address & Check Serviceability
                </button>

                <div class="row">
                    <div class="col-md-6 mb-3">
                         <div class="form-floating">
                            <input type="text" class="form-control" id="city" name="city" placeholder="City (auto-filled)" readonly required value="<?php echo htmlspecialchars($form_data['city'] ?? ''); ?>">
                            <label for="city">City <span class="text-danger">*</span></label>
                            <div id="city-status"></div>
                            <div class="invalid-feedback">City is required and must be serviceable. Please verify the address.</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Latitude (auto-filled)" readonly value="<?php echo htmlspecialchars($form_data['latitude'] ?? ''); ?>">
                            <label for="latitude">Latitude</label>
                            <div class="invalid-feedback">Latitude is required. Please verify address.</div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Longitude (auto-filled)" readonly value="<?php echo htmlspecialchars($form_data['longitude'] ?? ''); ?>">
                            <label for="longitude">Longitude</label>
                            <div class="invalid-feedback">Longitude is required. Please verify address.</div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" id="submitBtn" class="btn btn-primary w-100 btn-lg mt-3" disabled>
                <i class="fas fa-paper-plane me-2"></i>Submit Request
            </button>
        </form>
    </div>

    <!-- Service Area Modal (No change from original) -->
     <div class="modal fade" id="serviceAreaModal" tabindex="-1" aria-labelledby="serviceAreaModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="serviceAreaModalLabel"><i class="fas fa-map-marker-alt me-2 text-warning"></i>Service Area Notice</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            We're sorry, but our service is currently not available in the detected city (<span id="modalCityName"></span>). We are expanding constantly, please check back later!
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // --- Geocoding and Form Logic (largely same as original, with minor tweaks for new UI) ---
        const geocodeBtn = document.getElementById('geocodeBtn');
        const geocodeSpinner = document.getElementById('geocodeSpinner');
        const addressInput = document.getElementById('address');
        const cityInput = document.getElementById('city');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');
        const cityStatus = document.getElementById('city-status');
        const submitBtn = document.getElementById('submitBtn');
        const joinForm = document.getElementById('joinForm');
        const serviceAreaModal = new bootstrap.Modal(document.getElementById('serviceAreaModal'));
        const modalCityName = document.getElementById('modalCityName');

        // IMPORTANT: Replace with your actual LocationIQ API Key
        const apiKey = 'pk.5cf0dbea44f742c63a0aa40f0d8a5c3a'; // Keep your key
        let isCityServiceable = false;

        function setCityStatus(message, isError = false, isServiceableStatus = null) {
            cityStatus.textContent = message;
            cityInput.classList.remove('is-valid', 'is-invalid');
            latitudeInput.classList.remove('is-valid', 'is-invalid');
            longitudeInput.classList.remove('is-valid', 'is-invalid');

            if (isServiceableStatus === true) {
                 cityInput.classList.add('is-valid');
                 latitudeInput.classList.add('is-valid');
                 longitudeInput.classList.add('is-valid');
                 isCityServiceable = true;
            } else if (isServiceableStatus === false || isError) {
                 cityInput.classList.add('is-invalid');
                 latitudeInput.classList.add('is-invalid');
                 longitudeInput.classList.add('is-invalid');
                 isCityServiceable = false;
            } else {
                 isCityServiceable = false;
            }
            submitBtn.disabled = !isCityServiceable;
            if(isCityServiceable && joinForm.checkValidity()){ // Check overall form validity too if city is serviceable
                 submitBtn.disabled = false;
            } else {
                 submitBtn.disabled = true;
            }
        }

        async function checkCityServiceability(cityName) {
            if (!cityName) {
                setCityStatus('City could not be determined from address.', true, false);
                return false;
            }
            setCityStatus('Checking serviceability...', false, null);
            try {
                const response = await fetch('check_city.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', },
                    body: `city=${encodeURIComponent(cityName)}`
                });
                if (!response.ok) throw new Error(`Server error: ${response.status}`);
                const result = await response.json();
                if (result.serviceable) {
                    setCityStatus('Service available in this city.', false, true);
                    return true;
                } else {
                    setCityStatus('Service not available in this city.', true, false);
                    modalCityName.textContent = cityName;
                    serviceAreaModal.show();
                    return false;
                }
            } catch (error) {
                console.error('Error checking city serviceability:', error);
                setCityStatus(`Error checking serviceability: ${error.message}.`, true, false);
                return false;
            }
        }

        geocodeBtn.addEventListener('click', async () => {
            const address = addressInput.value.trim();
            if (!address) {
                addressInput.classList.add('is-invalid');
                addressInput.focus();
                return;
            } else {
                 addressInput.classList.remove('is-invalid');
            }

            cityInput.value = ''; latitudeInput.value = ''; longitudeInput.value = '';
            setCityStatus('', false, null);
            geocodeSpinner.style.display = 'inline-block';
            geocodeBtn.disabled = true;

            const url = `https://us1.locationiq.com/v1/search.php?key=${apiKey}&q=${encodeURIComponent(address)}&format=json&addressdetails=1&limit=1`;
            try {
                const response = await fetch(url);
                let locationData = await response.json();
                if (!response.ok) throw new Error(`Geocoding failed: ${locationData?.error || response.statusText}`);

                if (locationData && locationData.length > 0) {
                    const location = locationData[0];
                    const lat = location.lat;
                    const lon = location.lon;
                    const city = location.address?.city || location.address?.town || location.address?.village || location.address?.county || '';
                    latitudeInput.value = parseFloat(lat).toFixed(8);
                    longitudeInput.value = parseFloat(lon).toFixed(8);
                    if (city) {
                       cityInput.value = city;
                       await checkCityServiceability(city);
                    } else {
                        setCityStatus('Could not determine city from address.', true, false);
                    }
                } else {
                    setCityStatus('Address not found or invalid.', true, false);
                }
            } catch (error) {
                console.error('Geocoding error:', error);
                setCityStatus(`Error verifying address: ${error.message}.`, true, false);
            } finally {
                geocodeSpinner.style.display = 'none';
                geocodeBtn.disabled = false;
            }
        });

        joinForm.addEventListener('submit', event => {
            joinForm.classList.add('was-validated');
            if (!joinForm.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                const firstInvalid = joinForm.querySelector(':invalid');
                if (firstInvalid) firstInvalid.focus();
                return;
            }
            if (!isCityServiceable) {
                 event.preventDefault();
                 event.stopPropagation();
                 setCityStatus('Please verify the address and ensure the city is serviceable.', true, false);
                 cityInput.focus();
                 alert('Address verification and serviceability check is required before submitting.');
                 return;
            }
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        }, false);

        addressInput.addEventListener('input', () => {
            if (isCityServiceable || cityInput.classList.contains('is-invalid') || cityInput.classList.contains('is-valid')) {
                 cityInput.value = ''; latitudeInput.value = ''; longitudeInput.value = '';
                 setCityStatus('Address changed. Please re-verify.', false, null); // Changed to not an error state
                 cityInput.classList.remove('is-valid', 'is-invalid');
                 latitudeInput.classList.remove('is-valid', 'is-invalid');
                 longitudeInput.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        // Enable submit button only if form is valid AND city is serviceable
        function checkFormValidityAndServiceability() {
            const isFormValid = joinForm.checkValidity();
            submitBtn.disabled = !(isFormValid && isCityServiceable);
        }

        // Listen to changes on form elements to re-evaluate submit button state
        Array.from(joinForm.elements).forEach(element => {
            element.addEventListener('input', checkFormValidityAndServiceability);
            element.addEventListener('change', checkFormValidityAndServiceability); // For selects, files
        });


        // --- New UI Enhancement Scripts ---

        // File input name display & image preview
        function displayFileName(input, displayNameId) {
            const fileNameDisplay = document.getElementById(displayNameId);
            if (input.files && input.files.length > 0) {
                fileNameDisplay.textContent = input.files[0].name;
            } else {
                fileNameDisplay.textContent = "No file chosen";
            }
        }

        function previewImage(event) {
            const reader = new FileReader();
            const imagePreview = document.getElementById('imagePreview');
            reader.onload = function(){
                imagePreview.src = reader.result;
                imagePreview.style.display = 'block';
            }
            if (event.target.files[0]) {
                reader.readAsDataURL(event.target.files[0]);
            } else {
                imagePreview.src = "#";
                imagePreview.style.display = 'none';
            }
        }

        // Password strength and toggle visibility
        const passwordInput = document.getElementById('password');
        const passwordStrengthBar = document.getElementById('passwordStrengthBar');
        const passwordStrengthText = document.getElementById('passwordStrengthText');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordFeedback = document.getElementById('password-feedback');


        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedbackText = "Password must be at least 8 characters."; // Default message

                if (password.length >= 8) strength += 1;
                if (password.match(/[a-z]/)) strength += 1;
                if (password.match(/[A-Z]/)) strength += 1;
                if (password.match(/[0-9]/)) strength += 1;
                if (password.match(/[^a-zA-Z0-9]/)) strength += 1; // Special character

                let barColor = 'bg-danger';
                let strengthLabel = 'Weak';

                switch (strength) {
                    case 0:
                    case 1:
                    case 2:
                        strength = Math.max(1, strength); // Ensure at least some bar for <8 chars
                        barColor = 'bg-danger';
                        strengthLabel = 'Weak';
                        feedbackText = "Password is too weak. Add uppercase, lowercase, numbers, and symbols.";
                        break;
                    case 3:
                        barColor = 'bg-warning';
                        strengthLabel = 'Medium';
                        feedbackText = "Password strength is medium. Consider adding more variety.";
                        break;
                    case 4:
                        barColor = 'bg-info'; // Or a different shade of green
                        strengthLabel = 'Strong';
                        feedbackText = "Password strength is strong.";
                        break;
                    case 5:
                        barColor = 'bg-success';
                        strengthLabel = 'Very Strong';
                        feedbackText = "Password strength is very strong.";
                        break;
                }

                if (password.length < 8 && password.length > 0) {
                    strength = 1; // Minimum visibility for short passwords
                    barColor = 'bg-danger';
                    strengthLabel = 'Too short';
                    feedbackText = "Password must be at least 8 characters.";
                } else if (password.length === 0) {
                    strength = 0;
                    strengthLabel = '';
                    feedbackText = "Password must be at least 8 characters.";
                }


                passwordStrengthBar.style.width = (strength * 20) + '%';
                passwordStrengthBar.className = `password-strength-bar ${barColor}`;
                passwordStrengthText.textContent = strengthLabel;
                passwordFeedback.textContent = feedbackText; // Update feedback text dynamically

                // Bootstrap validation for minlength
                if (password.length >= 8) {
                    passwordInput.setCustomValidity(''); // Valid
                } else if (password.length > 0) {
                    passwordInput.setCustomValidity(feedbackText); // Invalid
                } else {
                    passwordInput.setCustomValidity('Password is required.'); // Invalid (if empty and required)
                }
                 checkFormValidityAndServiceability(); // Re-check button state
            });
        }

        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Auto-uppercase IFSC code input
        const ifscInput = document.getElementById('ifsc_code');
        if(ifscInput) {
            ifscInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }


        // Initial page load state
        //submitBtn.disabled = true; // Already handled by checkFormValidityAndServiceability
        const wasReloadedWithData = <?php echo !empty($form_data) ? 'true' : 'false'; ?>;
        if (wasReloadedWithData && cityInput.value && latitudeInput.value && longitudeInput.value) {
            setTimeout(async () => {
                if (cityInput.value) {
                   await checkCityServiceability(cityInput.value);
                   checkFormValidityAndServiceability(); // Check after serviceability check
                }
            }, 300);
        } else if (cityInput.value) { // Should not happen if lat/lon are empty
            setCityStatus('Please verify address and serviceability.', false, null);
        }
        // Initial check for submit button state
        checkFormValidityAndServiceability();

    </script>
</body>
</html>
<?php
if (isset($db) && $db) { mysqli_close($db); }
?>