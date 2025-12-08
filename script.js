const API_BASE = window.location.origin + '/ridesphere';

let currentUser = null;
let vehicles = [];
let messages = [];
let currentModalIndex = null;
let isOwnerView = true;
let currentChatOwnerId = null;
let allCars = [];
let filteredCars = [];
let currentSort = 'name';
// When an owner 'views as renter' we store the acting renter id here.
// This lets the UI and booking calls use that id when creating or listing renter bookings.
let actingRenterId = null;

// API Helper Functions
async function apiCall(endpoint, data = {}) {
    try {
        console.log(`Making API call to ${API_BASE}/${endpoint}`, data);
        const response = await fetch(`${API_BASE}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const text = await response.text();
        console.log(`Raw API response from ${endpoint}:`, text);
        
        let result;
        try {
            result = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON parse error:', parseError);
            throw new Error('Invalid JSON response from server');
        }
        
        console.log(`Parsed API response from ${endpoint}:`, result);
        return result;
        
    } catch (error) {
        console.error('API Call failed:', error);
        return { 
            success: false, 
            message: 'Network error: ' + error.message 
        };
    }
}

 

function showLogin(){
    console.log("Showing login screen");
    // Hide other screens and show login only
    const choice = document.getElementById("choiceScreen"); if (choice) choice.classList.add("hidden");
    const signup = document.getElementById("signupScreen"); if (signup) signup.classList.add("hidden");
    document.getElementById("loginScreen").classList.remove("hidden");
}

function showSignup(){
    console.log("Showing signup screen");
    // Hide other screens and show signup only
    const choice = document.getElementById("choiceScreen"); if (choice) choice.classList.add("hidden");
    const login = document.getElementById("loginScreen"); if (login) login.classList.add("hidden");
    const main = document.getElementById("mainApp"); if (main) main.classList.add("hidden");
    const signup = document.getElementById("signupScreen"); if (signup) signup.classList.remove("hidden");
}

function backToChoice(){
    console.log("Going back to choice screen");
    document.getElementById("signupScreen").classList.add("hidden");
    document.getElementById("loginScreen").classList.add("hidden");
    document.getElementById("mainApp").classList.add("hidden");
    document.getElementById("choiceScreen").classList.remove("hidden");
    
    // Clear form fields
    document.getElementById("firstName").value = '';
    document.getElementById("middleName").value = '';
    document.getElementById("lastName").value = '';
    document.getElementById("phoneNumber").value = '';
    document.getElementById("address").value = '';
    document.getElementById("newEmail").value = '';
    document.getElementById("newPassword").value = '';
    // Terms & Conditions checkbox removed from the form; nothing to clear
    document.getElementById("email").value = '';
    document.getElementById("password").value = '';
}

// ----- SIGNUP -----
async function signup(event){
    if (event) event.preventDefault();
    console.log("Starting signup process");
    // Prevent duplicate submissions: simple in-flight guard
    if (window.__signupOTPRequestInFlight) {
        console.warn('Signup already in progress - ignoring duplicate request');
        notify('Signup already in progress. Please wait...', 'info');
        return;
    }
    window.__signupOTPRequestInFlight = true;
    
    // Safe getter to avoid runtime errors when an expected DOM element is missing.
    function getInputValue(id) {
        const el = document.getElementById(id);
        if (!el) {
            console.warn(`signup: element #${id} not found`);
            return '';
        }
        return (el.value || '').trim();
    }

    const firstName = getInputValue("firstName");
    const middleName = getInputValue("middleName");
    const lastName = getInputValue("lastName");
    const phoneNumber = getInputValue("phoneNumber");
    const address = getInputValue("address");
    const email = getInputValue("newEmail");
    const password = getInputValue("newPassword");
    const role = (document.getElementById("newRole") && document.getElementById("newRole").value) ? document.getElementById("newRole").value : 'renter';

    console.log("Signup data:", { firstName, lastName, email, role });

    if(!firstName || !lastName || !email || !password){ 
        notify("Please fill all required fields (marked with *).", 'warning'); 
        return; 
    }

    // Store signup data for later account creation after OTP verification
    window.pendingSignup = { firstName, middleName, lastName, phoneNumber, address, email, password, role };

    // Show loading state
    const signupBtn = document.querySelector('#signupScreen .btn.primary');
    const originalText = signupBtn ? signupBtn.innerHTML : '';
    if (signupBtn) {
        signupBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Sending OTP...';
        signupBtn.disabled = true;
    }

    try {
        // Send OTP to email first (start request then show modal immediately)
        const callPromise = apiCall('auth.php', {
            action: 'send_otp_to_email',
            email
        });

        // Immediately show the OTP modal as a UX improvement so user can enter code
        // (it will also act as a fallback during delivery failures)
        try { showOTPModalForSignup(email); } catch (e) { console.warn('showOTPModalForSignup failed early:', e); }

        const result = await callPromise;

        console.log('send_otp_to_email response:', result);

        if(result && result.success) {
            const targetEmail = (result.email && result.email.length) ? result.email : email;
            notify("OTP sent to " + targetEmail, 'success');
            // Ensure modal shows the server-provided email
            try { document.getElementById('otpEmailDisplay').textContent = targetEmail; } catch (e) {}
        } else {
            notify("Error: " + (result && result.message ? result.message : "Failed to send OTP"), 'error');
            // show an inline banner inside the modal for clarity
            try {
                const modal = document.getElementById('otpModal');
                if (modal) {
                    let banner = document.getElementById('otpErrorBanner');
                    if (!banner) {
                        banner = document.createElement('div');
                        banner.id = 'otpErrorBanner';
                        banner.style.background = '#fff3f2';
                        banner.style.color = '#7f1d1d';
                        banner.style.padding = '8px 12px';
                        banner.style.borderRadius = '8px';
                        banner.style.marginTop = '12px';
                        banner.style.textAlign = 'center';
                        const content = modal.querySelector('.modal-content');
                        if (content) content.insertBefore(banner, content.querySelector('#otpBoxesContainer'));
                    }
                    banner.textContent = 'We could not send the OTP email. You can enter a test OTP here or try Resend.';
                }
            } catch (e) { console.error('Failed to show OTP error banner:', e); }
        }
    } catch (error) {
        console.error('Signup error:', error);
        notify("An unexpected error occurred: " + error.message, 'error');
    } finally {
        // Restore button state
        if (signupBtn) {
            signupBtn.innerHTML = originalText;
            signupBtn.disabled = false;
        }
        // Clear in-flight guard
        window.__signupOTPRequestInFlight = false;
    }
}

// ----- LOGIN -----
async function login(){
    console.log("Starting login process");
    
    // prefer email for login
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();

    console.log("Login attempt:", { email });

    if(!email || !password) {
        notify("Please enter both email and password.", 'warning');
        return;
    }

    // Show loading state
    const loginBtn = document.getElementById('loginButton');
    const originalText = loginBtn ? loginBtn.innerHTML : '';
    if (loginBtn) {
        loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Logging in...';
        loginBtn.disabled = true;
    }

    try {
        console.log("Making API call to auth.php");
        const result = await apiCall('auth.php', {
            action: 'login',
            email: email,
            password: password
        });

        console.log("Full API response:", result);

        if(result && result.success === true && result.user) {
            console.log("Login successful - setting current user");
            currentUser = result.user;
            updateHeaderUser();
            showDashboard();
            
        } else {
            const errorMsg = result?.message || "Invalid email or password";
            console.error("Login failed:", errorMsg);
            notify("Login failed: " + errorMsg, 'error');
        }
    } catch (error) {
        console.error('Login CATCH block error:', error);
        notify("Login error: " + error.message, 'error');
    } finally {
        // Restore button state
        if (loginBtn) {
            loginBtn.innerHTML = originalText;
            loginBtn.disabled = false;
        }
    }
}

// FIXED: Show dashboard function
function showDashboard(){
    try {
        console.log("Showing dashboard for user:", currentUser);
        
        document.getElementById("choiceScreen").classList.add("hidden");
        document.getElementById("loginScreen").classList.add("hidden");
        document.getElementById("signupScreen").classList.add("hidden");
        document.getElementById("mainApp").classList.remove("hidden");

        // Update user welcome message
        if (currentUser) {
            document.getElementById("currentUserName").textContent = `Welcome, ${currentUser.first_name} ${currentUser.last_name}`;
        }

        if(currentUser.role === "renter"){ 
            console.log("Showing renter dashboard");
            // Ensure toggle button isn't present for renters
            const toggleBtn = document.getElementById('toggleViewBtn');
            if (toggleBtn) toggleBtn.remove();
            isOwnerView = false;
            document.getElementById("renterDashboard").classList.remove("hidden");
            document.getElementById("ownerDashboard").classList.add("hidden");
            showMainInterface(); // Start with main interface
            // renter profile removed from UI
        } else {
            console.log("Showing owner dashboard");
            isOwnerView = true;
            document.getElementById("ownerDashboard").classList.remove("hidden");
            document.getElementById("renterDashboard").classList.add("hidden");
            loadOwnerVehicles();
            addToggleButton();
            // Initialize owner dashboard with proper view
            showVehicleTypes();
        }
    } catch (error) {
        console.error('Error in showDashboard:', error);
        notify("Error loading dashboard: " + error.message, 'error');
    }
}

// ----- LOGOUT -----
function logout(){
    console.log("Logging out user:", currentUser);
    currentUser = null;
    vehicles = [];
    messages = [];
    document.getElementById("mainApp").classList.add("hidden");
    // Hide renter profile on logout
    backToChoice();
    updateHeaderUser();
}

// ----- PROFILE MODAL -----
function openProfileModal(){
    if (!currentUser) {
        notify('No user information available.', 'warning');
        return;
    }
    const content = document.getElementById('profileContent');
    content.innerHTML = `
        <div style="display:flex; gap:1rem; align-items:center;">
            <div style="width:56px; height:56px; border-radius:50%; background:linear-gradient(180deg,var(--primary),var(--primary-dark)); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:700; font-size:1.1rem;">
                ${currentUser.first_name ? currentUser.first_name.charAt(0).toUpperCase() : 'U'}
            </div>
            <div>
                <div style="font-weight:700; font-size:1rem;">${currentUser.first_name || ''} ${currentUser.last_name || ''}</div>
                <div style="color:var(--muted); font-size:0.9rem;">@${currentUser.username || currentUser.email || ''}</div>
            </div>
        </div>
        <div style="margin-top:0.75rem; display:grid; gap:0.4rem; font-size:0.95rem;">
            <div><strong>Phone:</strong> ${currentUser.phone_number || 'N/A'}</div>
            <div><strong>Address:</strong> ${currentUser.address || 'N/A'}</div>
            <div><strong>Role:</strong> ${currentUser.role || 'N/A'}</div>
            <div><strong>Joined:</strong> ${currentUser.created_at ? new Date(currentUser.created_at).toLocaleDateString() : 'N/A'}</div>
        </div>
    `;
    animateShow(document.getElementById('profileModal'));
}

function closeProfileModal(){
    animateHide(document.getElementById('profileModal'));
}

// Update header profile display (name/avatar)
function updateHeaderUser(){
    const profileBtn = document.getElementById('profileBtn');
    const profileName = document.getElementById('profileName');
    if (!profileBtn || !profileName) return;

    if (currentUser) {
        const displayName = `${currentUser.first_name || ''} ${currentUser.last_name || ''}`.trim() || currentUser.username || 'Profile';
        profileName.textContent = displayName;
        profileBtn.style.display = 'inline-flex';
    } else {
        profileName.textContent = 'Profile';
        // keep button visible but generic; could hide if desired
        profileBtn.style.display = 'inline-flex';
    }
}

// ----- VEHICLES -----
// Image preview function
function previewImage(input) {
    const preview = document.getElementById('previewImg');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPlaceholder = document.getElementById('uploadPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            imagePreview.style.display = 'block';
            uploadPlaceholder.style.display = 'none';
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Preview function for edit vehicle form
function previewEditImage(input) {
    const preview = document.getElementById('editPreviewImg');
    const imagePreview = document.getElementById('editImagePreview');
    const uploadPlaceholder = document.getElementById('editUploadPlaceholder');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (preview) preview.src = e.target.result;
            if (imagePreview) imagePreview.style.display = 'block';
            if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Convert image to base64
function getBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

async function addVehicle(){
    console.log("Adding new vehicle with image upload");
    
    const name = document.getElementById("vehicleName").value.trim();
    const type = document.getElementById("vehicleType").value;
    const rate = document.getElementById("vehicleRate").value;
    const hours = document.getElementById("vehicleHours").value.trim();
    const location = document.getElementById("vehicleLocation").value.trim();
    const description = document.getElementById("vehicleDescription").value.trim();
    const imageInput = document.getElementById("vehicleImageInput");
    
    if(!name || !type || !rate || !hours || !location){ 
        notify("Please fill all required fields.", 'warning'); 
        return; 
    }

    // Show loading state
    const addBtn = document.querySelector('#vehicleForm .btn.accent');
    const originalText = addBtn.innerHTML;
    addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Adding Vehicle...';
    addBtn.disabled = true;

    try {
        const formData = new FormData();
        formData.append('action', 'add_vehicle_with_image');
        formData.append('name', name);
        formData.append('type', type);
        formData.append('rate', rate);
        formData.append('hours', hours);
        formData.append('location', location);
        formData.append('description', description);
        formData.append('owner_id', currentUser.id);
        
        // Add image file if selected
        if (imageInput && imageInput.files && imageInput.files[0]) {
            formData.append('vehicle_image', imageInput.files[0]);
        }

        const response = await fetch(API_BASE + '/vehicles.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if(result.success) {
            notify("Vehicle added successfully!", 'success');
            loadOwnerVehicles();
            hideVehicleForm();
            // Clear form
            document.getElementById("vehicleName").value = '';
            document.getElementById("vehicleType").value = '';
            document.getElementById("vehicleRate").value = '';
            document.getElementById("vehicleHours").value = '';
            document.getElementById("vehicleLocation").value = '';
            document.getElementById("vehicleDescription").value = '';
            if (document.getElementById("vehicleImageInput")) document.getElementById("vehicleImageInput").value = '';
            if (document.getElementById("imagePreview")) document.getElementById("imagePreview").style.display = 'none';
            if (document.getElementById("uploadPlaceholder")) document.getElementById("uploadPlaceholder").style.display = 'block';
        } else {
            notify("Error adding vehicle: " + result.message, 'error');
        }
    } catch (error) {
        console.error('Add vehicle error:', error);
        notify("An unexpected error occurred while adding the vehicle.", 'error');
    } finally {
        // Restore button state
        addBtn.innerHTML = originalText;
        addBtn.disabled = false;
    }
}

async function loadVehicles(){
    console.log("Loading vehicles for renter");
    
    try {
        const result = await apiCall('vehicles.php', {
            action: 'get_vehicles'
        });

        if(result.success) {
            vehicles = result.vehicles;
            allCars = formatCarData(vehicles);
            filteredCars = [...allCars];
            console.log("Loaded vehicles:", allCars);
            renderCarList();
            updateCarCount();
            initializeFilters();
        } else {
            console.error("Error loading vehicles:", result.message);
            document.getElementById("carList").innerHTML = '<p class="no-data">Error loading vehicles: ' + result.message + '</p>';
        }
    } catch (error) {
        console.error('Load vehicles error:', error);
        document.getElementById("carList").innerHTML = '<p class="no-data">Error loading vehicles. Please try again.</p>';
    }
}

async function loadOwnerVehicles(){
    console.log("Loading vehicles for owner:", currentUser.id);
    
    try {
        const result = await apiCall('vehicles.php', {
            action: 'get_owner_vehicles',
            owner_id: currentUser.id
        });

        if(result.success) {
            vehicles = result.vehicles;
            console.log("Loaded owner vehicles:", vehicles);
        } else {
            console.error("Error loading owner vehicles:", result.message);
        }
    } catch (error) {
        console.error('Load owner vehicles error:', error);
    }
}

// ----- ENHANCED UI FUNCTIONS -----
function showVehicleForm() {
    document.getElementById('vehicleForm').style.display = 'block';
    document.getElementById('vehiclesExpandedView').classList.remove('active');
    document.getElementById('ownerBookingsView').style.display = 'none';
    document.getElementById('editVehicleForm').classList.remove('active');
}

function hideVehicleForm() {
    document.getElementById('vehicleForm').style.display = 'none';
}

function updateDashboardStats() {
    if (currentUser && currentUser.role === 'renter') {
        document.getElementById('availableCars').textContent = filteredCars.length;
    }
}

function renderOwnerVehicles(){
    const container = document.getElementById("ownerVehicles");
    container.innerHTML = "";
    
    if (vehicles.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #94a3b8;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                <h3>No vehicles yet</h3>
                <p>Start by adding your first vehicle to rent out!</p>
                <button class="btn accent" onclick="showVehicleForm()" style="margin-top: 1rem;">
                    <i class="fas fa-plus"></i>Add Your First Vehicle
                </button>
            </div>
        `;
        return;
    }
    
    vehicles.forEach(vehicle => {
        const card = document.createElement("div");
        card.className = "vehicle-card";
        const imageSrc = vehicle.image_url || vehicle.image || vehicle.image_path || '';
        card.innerHTML = `
            <div class="vehicle-image">
                ${imageSrc ?
                    `<img src="${imageSrc}" alt="${vehicle.name}" style="width: 100%; height: 100%; object-fit: cover;">` :
                    `<div style="text-align: center; color: white; z-index: 1;">
                        <div style="font-size: 3rem;">🚗</div>
                        <div style="font-size: 0.9rem; opacity: 0.8;">Vehicle Image</div>
                    </div>`
                }
                <span class="vehicle-status ${vehicle.status === 'available' ? 'status-available' : 'status-unavailable'}">
                    ${vehicle.status}
                </span>
            </div>
            <div class="vehicle-content">
                <h3 class="vehicle-title">${vehicle.name}</h3>
                <div class="vehicle-meta">
                    <div class="vehicle-price">₱${vehicle.rate}/day</div>
                    <span class="vehicle-type">${vehicle.type}</span>
                </div>
                <div class="vehicle-specs">
                    <div class="vehicle-spec">
                        <div>🪑</div>
                        <div>${vehicle.seats || 4} Seats</div>
                    </div>
                    <div class="vehicle-spec">
                        <div>🚪</div>
                        <div>${vehicle.doors || 4} Doors</div>
                    </div>
                    <div class="vehicle-spec">
                        <div>🎒</div>
                        <div>${vehicle.baggage || 2} Bags</div>
                    </div>
                </div>
                <div class="vehicle-actions">
                    <button class="btn-small secondary" onclick="editVehicle(${vehicle.id})">
                        <i class="fas fa-edit"></i>Edit
                    </button>
                    <button class="btn-small ${vehicle.status === 'available' ? 'accent' : 'secondary'}" 
                            onclick="toggleVehicleStatus(${vehicle.id})">
                        <i class="fas ${vehicle.status === 'available' ? 'fa-pause' : 'fa-play'}"></i>
                        ${vehicle.status === 'available' ? 'Pause' : 'Activate'}
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

// Format vehicle data for the new interface
function formatCarData(vehicles) {
    return vehicles.map(vehicle => {
        // Generate car specifications based on type
        const specs = generateCarSpecs(vehicle.type);
        
        return {
            id: vehicle.id,
            name: vehicle.name,
            type: vehicle.type,
            class: getCarClass(vehicle.type),
            rate: parseFloat(vehicle.rate),
            hours: vehicle.hours,
            location: vehicle.location,
            owner: `${vehicle.first_name} ${vehicle.last_name}`,
            owner_id: vehicle.owner_id,
            status: vehicle.status,
            description: vehicle.description,
            // Car specifications
            seats: specs.seats,
            doors: specs.doors,
            baggage: specs.baggage,
            similar: Math.floor(Math.random() * 5) + 1 // Random similar count for demo
        };
    });
}

// Generate realistic car specifications
function generateCarSpecs(type) {
    const specs = {
        'Car': { seats: 4, doors: 4, baggage: 2 },
        'Motorcycle': { seats: 2, doors: 0, baggage: 0 },
        'SUV': { seats: 7, doors: 4, baggage: 4 },
        'Compact': { seats: 4, doors: 2, baggage: 1 },
        'Economy': { seats: 4, doors: 4, baggage: 2 },
        'Luxury': { seats: 5, doors: 4, baggage: 3 }
    };
    
    return specs[type] || specs['Car'];
}

// Get car class based on type
function getCarClass(type) {
    const classes = {
        'Car': 'STANDARD',
        'Motorcycle': 'BIKE',
        'SUV': 'SUV',
        'Compact': 'COMPACT',
        'Economy': 'ECONOMY',
        'Luxury': 'LUXURY'
    };
    
    return classes[type] || 'STANDARD';
}

// Render car list in the new format - THIS IS THE CORRECTED VERSION WITH BOOK NOW BUTTON
// Updated renderVehicles function to show images
function renderVehicles(vehicles) {
    const container = document.getElementById("vehiclesContainer");
    
    if (vehicles.length === 0) {
        container.innerHTML = `
            <div class="no-data">
                <div class="icon">🔍</div>
                <h3>No Vehicles Found</h3>
                <p>No vehicles match your current selection.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    vehicles.forEach((vehicle, idx) => {
        const specs = generateCarSpecs(vehicle.type);
        
        const vehicleCard = document.createElement("div");
        vehicleCard.className = "vehicle-item";
    // clicking the card opens the enhanced vehicle details modal
    vehicleCard.setAttribute('onclick', `openVehicleDetails(${vehicle.id})`);
        vehicleCard.innerHTML = `
            <div class="vehicle-image">
                ${vehicle.image_url ? 
                    `<img src="${vehicle.image_url}" alt="${vehicle.name}" style="width: 100%; height: 100%; object-fit: cover;">` :
                    `<div class="vehicle-image-content">
                        <div class="icon">🚗</div>
                        <small>No Image</small>
                    </div>`
                }
                <span class="vehicle-status ${vehicle.status === 'available' ? 'status-available' : vehicle.status === 'pending' ? 'status-pending' : 'status-booked'}">
                    ${vehicle.status.toUpperCase()}
                </span>
            </div>
            <div class="vehicle-info">
                <div class="vehicle-name">${vehicle.name}</div>
                <span class="vehicle-type">${vehicle.type}</span>
                <div class="vehicle-price">₱${vehicle.rate}/day</div>
                
                <div class="vehicle-specs">
                    <div class="vehicle-spec">
                        <div>🪑 ${specs.seats} Seats</div>
                    </div>
                    <div class="vehicle-spec">
                        <div>🚪 ${specs.doors} Doors</div>
                    </div>
                    <div class="vehicle-spec">
                        <div>🎒 ${specs.baggage} Bags</div>
                    </div>
                </div>
                
                <div class="vehicle-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${vehicle.location}
                </div>
                
                <button class="book-now-btn" onclick="event.stopPropagation(); bookVehicle(${vehicle.id})" ${vehicle.status !== 'available' ? 'disabled' : ''}>
                    <i class="fas fa-calendar-check"></i>
                    ${vehicle.status === 'available' ? 'Book Now' : 
                     vehicle.status === 'pending' ? 'Booking Pending' : 
                     'Not Available'}
                </button>
            </div>
        `;
        container.appendChild(vehicleCard);
    });
}

async function bookVehicle(vehicleId) {
    try {
        // Get vehicle details first
        const vehicleResult = await apiCall('vehicles.php', {
            action: 'get_vehicle_details',
            vehicle_id: vehicleId
        });

        if (!vehicleResult.success) {
            notify("Error getting vehicle details: " + vehicleResult.message, 'error');
            return;
        }

        const vehicle = vehicleResult.vehicle;
        
        // First confirmation
        const confirmMessage = `Are you sure you want to book "${vehicle.name}"?\n\nDaily Rate: ₱${vehicle.rate}\nType: ${vehicle.type}\nLocation: ${vehicle.location}\n\nClick OK to proceed with booking.`;
        const confirmBooking = await showConfirm('Confirm Booking', confirmMessage);
        if (!confirmBooking) return;

        // Calculate dates (book for tomorrow to 2 days from now as default)
        const startDate = new Date();
        startDate.setDate(startDate.getDate() + 1);
        const endDate = new Date();
        endDate.setDate(endDate.getDate() + 2);
        
        const startDateStr = startDate.toISOString().split('T')[0];
        const endDateStr = endDate.toISOString().split('T')[0];
        
        // Calculate total amount (2 days rental)
        const dailyRate = parseFloat(vehicle.rate);
        const timeDiff = endDate.getTime() - startDate.getTime();
        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
        const totalAmount = daysDiff * dailyRate;
        
        // Create booking
        const result = await apiCall('bookings.php', {
            action: 'create_booking',
            vehicle_id: vehicleId,
            // honor actingRenterId when owner is viewing as renter
            renter_id: actingRenterId || (currentUser ? currentUser.id : null),
            start_date: startDateStr,
            end_date: endDateStr,
            total_amount: totalAmount
        });

        if (result.success) {
            notify("Booking successful — your request is waiting for owner confirmation.", 'success');
            
            // Refresh the vehicles list to update status
            loadVehiclesForRenter();
        } else {
            notify("Booking failed: " + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Book vehicle error:', error);
        notify("An unexpected error occurred while booking the vehicle.", 'error');
    }
}

// Filter cars based on selected criteria
function filterCars() {
    const selectedDoors = getSelectedDoors();
    const selectedTypes = getSelectedTypes();
    const selectedSeats = getSelectedSeats();
    
    filteredCars = allCars.filter(car => {
        // Door filter
        if (selectedDoors.length > 0 && !selectedDoors.includes(car.doors)) {
            return false;
        }
        
        // Type filter
        if (selectedTypes.length > 0 && !selectedTypes.includes(car.class)) {
            return false;
        }
        
        // Seat filter
        if (selectedSeats.length > 0 && !selectedSeats.some(seat => car.seats >= seat)) {
            return false;
        }
        
        return true;
    });
    
    renderCarList();
    updateCarCount();
}

// Get selected door options
function getSelectedDoors() {
    const doors = [];
    if (document.getElementById('doors2')?.checked) doors.push(2);
    if (document.getElementById('doors4')?.checked) doors.push(4);
    return doors;
}

// Get selected car types
function getSelectedTypes() {
    const types = [];
    if (document.getElementById('typeCompact')?.checked) types.push('COMPACT');
    if (document.getElementById('typeEconomy')?.checked) types.push('ECONOMY');
    if (document.getElementById('typeSUV')?.checked) types.push('SUV');
    if (document.getElementById('typeLuxury')?.checked) types.push('LUXURY');
    return types;
}

// Get selected seating capacities
function getSelectedSeats() {
    const seats = [];
    if (document.getElementById('seats2')?.checked) seats.push(2);
    if (document.getElementById('seats4')?.checked) seats.push(4);
    if (document.getElementById('seats5')?.checked) seats.push(5);
    if (document.getElementById('seats7')?.checked) seats.push(7);
    return seats;
}

// Sort cars
function sortCars(sortBy, el) {
    currentSort = sortBy;
    
    // Update active sort button
    document.querySelectorAll('.sort-option').forEach(btn => {
        btn.classList.remove('active');
    });
    if (el && el.classList) {
        el.classList.add('active');
    }
    
    filteredCars.sort((a, b) => {
        if (sortBy === 'name') {
            return a.name.localeCompare(b.name);
        } else if (sortBy === 'price') {
            return a.rate - b.rate;
        } else if (sortBy === 'type') {
            return a.class.localeCompare(b.class);
        }
        return 0;
    });
    
    renderCarList();
}

// Update car count display
function updateCarCount() {
    const count = filteredCars.length;
    document.getElementById('carCount').textContent = `${count} Car${count !== 1 ? 's' : ''} found`;
}

// Select car function
function selectCar(carId) {
    const car = filteredCars.find(c => c.id === carId);
    if (car && car.status === 'available') {
        // Find the original vehicle index
        const vehicleIndex = vehicles.findIndex(v => v.id === carId);
        if (vehicleIndex !== -1) {
            openModal(vehicleIndex);
        }
    }
}

// Initialize filters on page load
function initializeFilters() {
    // Set some default filters for demo
    if (document.getElementById('doors4')) document.getElementById('doors4').checked = true;
    if (document.getElementById('typeEconomy')) document.getElementById('typeEconomy').checked = true;
    if (document.getElementById('typeCompact')) document.getElementById('typeCompact').checked = true;
    if (document.getElementById('seats4')) document.getElementById('seats4').checked = true;
}

// ----- MODAL -----
function openModal(index){
    console.log("Opening modal for vehicle index:", index);
    currentModalIndex = index;
    const vehicle = vehicles[index];
    
    document.getElementById("modalName").textContent = vehicle.name;
    
    const details = `
        <div style="display: grid; gap: 1rem;">
            ${vehicle.image_url ? `
            <div style="height: 200px; background: #0f172a; border-radius: 8px; overflow: hidden; display:flex; align-items:center; justify-content:center;">
                <img src="${vehicle.image_url}" alt="${vehicle.name}" style="width:100%; height:100%; object-fit:cover;">
            </div>
            ` : ''}

            <div style="display: flex; justify-content: space-between;">
                <span>Type:</span>
                <strong>${vehicle.type}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Daily Rate:</span>
                <strong style="color: var(--accent);">₱${vehicle.rate}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Available Hours:</span>
                <strong>${vehicle.hours}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Location:</span>
                <strong>${vehicle.location}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <div style="font-size:0.95rem; color:#94a3b8;">Owner:</div>
                    <strong style="display:block;">${vehicle.first_name} ${vehicle.last_name}</strong>
                </div>
                <div style="text-align: right;">
                    <div style="font-size:0.95rem; color:#94a3b8;">Contact:</div>
                    <strong style="display:block;">${vehicle.phone_number || 'N/A'}</strong>
                </div>
            </div>
            ${vehicle.description ? `
            <div>
                <div style="font-weight: 600; margin-bottom: 0.5rem;">Description:</div>
                <div style="color: #94a3b8;">${vehicle.description}</div>
            </div>
            ` : ''}

            <div style="display:flex; gap:0.5rem; margin-top:0.5rem;">
                <button class="btn secondary" onclick="openMessageModal(${vehicle.owner_id}, '${vehicle.first_name} ${vehicle.last_name}')">
                    <i class="fas fa-envelope"></i> Message Owner
                </button>
                ${vehicle.phone_number ? `<a class="btn accent" href="tel:${vehicle.phone_number}"><i class="fas fa-phone"></i> Call Owner</a>` : ''}
            </div>
        </div>
    `;
    
    document.getElementById("modalDetails").innerHTML = details;
    document.getElementById("vehicleModal").classList.add("open");
}

function closeModal(){ 
    console.log("Closing vehicle modal");
    document.getElementById("vehicleModal").classList.remove("open");
    currentModalIndex = null;
}

// ----- VEHICLE DETAILS (enhanced modal) -----
async function openVehicleDetails(vehicleId) {
    try {
        // vehicleId can be an index or an id. Try to resolve to an id first.
        let id = vehicleId;
        if (typeof vehicleId === 'number') {
            // try find by id in vehicles array
            const byId = vehicles.find(v => v.id === vehicleId);
            if (!byId) {
                // maybe it's an index
                const asIndex = vehicles[vehicleId];
                if (asIndex) id = asIndex.id;
            }
        }

        const result = await apiCall('vehicles.php', { action: 'get_vehicle_details', vehicle_id: id });
        if (!result.success) {
            notify('Failed to load vehicle details: ' + result.message, 'error');
            return;
        }

        const vehicle = result.vehicle;

        const container = document.getElementById('vehicleModalContent');
        container.innerHTML = `
            <div style="display: grid; gap: 1rem;">
                ${vehicle.image_url ? `
                    <div style="height: 300px; border-radius: 12px; overflow:hidden; background:#071023; display:flex; align-items:center; justify-content:center;">
                        <img src="${vehicle.image_url}" alt="${vehicle.name}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                ` : ''}
                <div style="display:flex; justify-content:space-between; gap:1rem; align-items:flex-start;">
                    <div style="flex:1">
                        <h3 style="margin:0 0 .25rem 0">${vehicle.name}</h3>
                        <div style="color:var(--muted); margin-bottom:.5rem">${vehicle.type} • ${vehicle.location}</div>
                        <div style="font-size:1.1rem; font-weight:700; color:var(--accent);">₱${vehicle.rate}/day</div>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:.85rem; color:var(--muted)">Owner</div>
                        <div style="font-weight:700">${vehicle.first_name} ${vehicle.last_name}</div>
                        <div style="margin-top:.5rem"><a class="btn accent" href="tel:${vehicle.phone_number}"><i class="fas fa-phone"></i> Call</a></div>
                    </div>
                </div>

                ${vehicle.description ? `<div style="color:var(--muted)">${vehicle.description}</div>` : ''}

                <div style="display:flex; gap:.5rem;">
                    <button class="btn secondary" onclick="openMessageModal(${vehicle.owner_id}, '${vehicle.first_name} ${vehicle.last_name}')"><i class="fas fa-envelope"></i> Message Owner</button>
                    <button class="btn accent" onclick="openBookingModalForVehicleId(${vehicle.id})"><i class="fas fa-calendar-check"></i> Book This Vehicle</button>
                </div>
            </div>
        `;

        const modal = document.getElementById('vehicleDetailsModal');
        modal.classList.remove('hidden');
        modal.classList.add('open');
        currentSelectedVehicle = vehicle;
    } catch (err) {
        console.error('openVehicleDetails error', err);
        notify('Unable to load vehicle details.', 'error');
    }
}

function closeVehicleDetails(){
    const modal = document.getElementById('vehicleDetailsModal');
    if (!modal) return;
    modal.classList.remove('open');
    modal.classList.add('hidden');
    currentSelectedVehicle = null;
}

function openBookingModalForVehicleId(vehicleId) {
    // Find vehicle index in current vehicles list
    const idx = vehicles.findIndex(v => v.id === vehicleId);
    if (idx !== -1) {
        openBookingModalFromVehicle(idx);
    } else {
        // Fallback: set bookingVehicleId manually and open modal
        bookingVehicleId = vehicleId;
        // Try to set display values
        const vehicle = vehicles.find(v => v.id === vehicleId) || currentSelectedVehicle;
        if (vehicle) {
            document.getElementById('bookingVehicleName').innerText = vehicle.name;
            document.getElementById('bookingDailyRate').innerText = `₱${vehicle.rate}/day`;
        }
        document.getElementById('bookingModal').classList.remove('hidden');
        document.getElementById('bookingModal').classList.add('open');
        // default dates
        const startDate = new Date(); startDate.setDate(startDate.getDate() + 1);
        const endDate = new Date(); endDate.setDate(endDate.getDate() + 2);
        document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
        document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
        calculateBookingAmount();
    }
}

// ----- Booking helpers -----
let bookingVehicleId = null;

function openBookingModalFromVehicle(index) {
    if (index === null || index === undefined) return;
    const vehicle = vehicles[index];
    if (!vehicle) return;

    bookingVehicleId = vehicle.id;
    document.getElementById('bookingVehicleName').innerText = vehicle.name;
    document.getElementById('bookingDailyRate').innerText = `₱${vehicle.rate}/day`;
    document.getElementById('bookingModal').classList.remove('hidden');
    document.getElementById('bookingModal').classList.add('open');
    // set default dates: tomorrow and day after
    const startDate = new Date(); startDate.setDate(startDate.getDate() + 1);
    const endDate = new Date(); endDate.setDate(endDate.getDate() + 2);
    document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
    document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
    calculateBookingAmount();
}

function closeBookingModal(){
    document.getElementById('bookingModal').classList.add('hidden');
    document.getElementById('bookingModal').classList.remove('open');
}

function calculateBookingAmount(){
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    if(!start || !end || !bookingVehicleId) return;

    const s = new Date(start);
    const e = new Date(end);
    let days = Math.ceil((e - s) / (1000*3600*24)) + 1;
    if (days <= 0) days = 1;

    const vehicle = vehicles.find(v => v.id === bookingVehicleId);
    const total = (vehicle ? parseFloat(vehicle.rate) : 0) * days;
    document.getElementById('calculatedAmount').innerText = `₱${total}`;
    document.getElementById('totalAmount').value = total;
}

async function submitBooking(){
    const start = document.getElementById('startDate').value;
    const end = document.getElementById('endDate').value;
    const total = document.getElementById('totalAmount').value;
    if(!bookingVehicleId || !start || !end || !total) {
        notify('Please fill in dates to proceed', 'warning');
        return;
    }

    try {
        const result = await apiCall('bookings.php', {
            action: 'create_booking',
            vehicle_id: bookingVehicleId,
            // honor actingRenterId when owner is viewing as renter
            renter_id: actingRenterId || (currentUser ? currentUser.id : null),
            start_date: start,
            end_date: end,
            total_amount: total
        });

        if(result.success) {
            notify('Booking created successfully!', 'success');
            closeBookingModal();
            closeModal();
            // refresh renter vehicles/bookings
            await loadVehiclesForRenter();
            await loadRenterBookings();
        } else {
            notify('Booking failed: ' + result.message, 'error');
        }
    } catch (err) {
        console.error('Submit booking error', err);
        notify('An unexpected error occurred', 'error');
    }
}

function bookNow(){
    console.log("Book now clicked for vehicle index:", currentModalIndex);
    if(currentModalIndex !== null){
        const vehicle = vehicles[currentModalIndex];
        openMessageModal(vehicle.owner_id, vehicle.first_name + " " + vehicle.last_name);
        closeModal();
    }
}

// ----- MESSAGES -----
async function openMessageModal(ownerId, ownerName){
    console.log("Opening message modal with owner:", ownerId, ownerName);
    
    currentChatOwnerId = ownerId;
    document.getElementById("messageHeader").innerText = "Conversation with " + ownerName;
    document.getElementById("messageModal").classList.remove("hidden");
    document.getElementById("messageModal").classList.add("open");
    
    await loadMessages(ownerId);
    
    // Set up send message handler
    const sendBtn = document.getElementById("sendMessageBtn");
    const messageInput = document.getElementById("messageInput");
    
    const sendHandler = async () => {
        const content = messageInput.value.trim();
        if(!content) return;
        
        console.log("Sending message:", content);
        await sendMessage(ownerId, content);
        messageInput.value = "";
        await loadMessages(ownerId);
    };
    
    sendBtn.onclick = sendHandler;
    
    // Also allow Enter key to send
    messageInput.onkeypress = (e) => {
        if(e.key === 'Enter') {
            sendHandler();
        }
    };
    
    // Focus on input
    messageInput.focus();
}

async function sendMessage(toUserId, content){
    console.log("Sending message to user:", toUserId, "Content:", content);
    
    try {
        const result = await apiCall('messages.php', {
            action: 'send_message',
            from_user_id: currentUser.id,
            to_user_id: toUserId,
            content: content
        });
        
        return result.success;
    } catch (error) {
        console.error('Send message error:', error);
        return false;
    }
}

async function loadMessages(otherUserId){
    console.log("Loading messages for user:", otherUserId);
    
    try {
        const result = await apiCall('messages.php', {
            action: 'get_messages',
            user_id1: currentUser.id,
            user_id2: otherUserId
        });

        if(result.success) {
            renderMessageThread(result.messages);
        } else {
            console.error("Error loading messages:", result.message);
        }
    } catch (error) {
        console.error('Load messages error:', error);
    }
}

function renderMessageThread(messages){
    const thread = document.getElementById("messageThread"); 
    thread.innerHTML = "";
    
    if (messages.length === 0) {
        thread.innerHTML = '<p style="text-align: center; color: #666; padding: 2rem;">No messages yet. Start the conversation!</p>';
        return;
    }
    
    messages.forEach(m => {
        const div = document.createElement("div");
        div.className = "message " + (m.from_user_id == currentUser.id ? "renter" : "owner");
        div.innerText = m.content;
        thread.appendChild(div);
    });
    
    thread.scrollTop = thread.scrollHeight;
}

function closeMessageModal(){ 
    console.log("Closing message modal");
    document.getElementById("messageModal").classList.add("hidden");
    document.getElementById("messageModal").classList.remove("open");
    currentChatOwnerId = null;
}

// ----- TOGGLE VIEW -----
function addToggleButton(){
    const toggleContainer = document.getElementById("toggleViewContainer");
    if(!document.getElementById("toggleViewBtn")){
        const toggleBtn = document.createElement("button");
        toggleBtn.className = "btn secondary"; 
        toggleBtn.id = "toggleViewBtn";
        toggleBtn.innerHTML = '<i class="fas fa-sync"></i>View as Renter';
        toggleBtn.onclick = toggleOwnerRenterView;
        toggleContainer.appendChild(toggleBtn);
    }
}

// ---- PROFILE EDIT FLOW ----
// profile edit flow removed

function toggleOwnerRenterView(){
    console.log("Toggling view. Current view:", isOwnerView ? "Owner" : "Renter");
    
    const btn = document.getElementById("toggleViewBtn");
    if(isOwnerView){
        document.getElementById("ownerDashboard").classList.add("hidden");
        document.getElementById("renterDashboard").classList.remove("hidden");
        // When owner switches to renter view, set actingRenterId so bookings/listings use this id.
        actingRenterId = currentUser ? currentUser.id : null;
        loadVehicles();
        btn.innerHTML = '<i class="fas fa-sync"></i>View as Owner';
    } else {
        document.getElementById("renterDashboard").classList.add("hidden");
        document.getElementById("ownerDashboard").classList.remove("hidden");
        loadOwnerVehicles();
        // Clear acting renter when returning to owner view
        actingRenterId = null;
        btn.innerHTML = '<i class="fas fa-sync"></i>View as Renter';
    }
    isOwnerView = !isOwnerView;
}

// ----- PASSWORD TOGGLE -----
document.addEventListener('DOMContentLoaded', function() {
    // Debug: ensure signup button clicks are observed
    try {
        const createBtn = document.getElementById('createAccountBtn');
        if (createBtn) {
            createBtn.addEventListener('click', function(e){
                try {
                    // Prevent any default form behaviors and start signup flow
                    e.preventDefault();
                    console.log('Create Account button clicked - invoking signup()');
                    signup(e);
                } catch (err) {
                    console.error('Error in create account click handler:', err);
                }
            });
        }
    } catch (e) {
        console.error('Error attaching createAccountBtn listener', e);
    }
    // Password toggle functionality
    const showSignupPassword = document.getElementById("showSignupPassword");
    const showLoginPassword = document.getElementById("showLoginPassword");
    
    if (showSignupPassword) {
        showSignupPassword.addEventListener("change", function(){
            document.getElementById("newPassword").type = this.checked ? "text" : "password";
        });
    }
    
    if (showLoginPassword) {
        showLoginPassword.addEventListener("change", function(){
            document.getElementById("password").type = this.checked ? "text" : "password";
        });
    }

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        const vehicleModal = document.getElementById('vehicleModal');
        const messageModal = document.getElementById('messageModal');
        const vehicleDetailsModal = document.getElementById('vehicleDetailsModal');
        
        if (e.target === vehicleModal) {
            closeModal();
        }
        if (e.target === messageModal) {
            closeMessageModal();
        }
        if (e.target === vehicleDetailsModal) {
            closeVehicleDetails();
        }
    });

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
            closeMessageModal();
            closeVehicleDetails();
        }
    });

    console.log("Ridesphere application initialized");
    console.log("API Base URL:", API_BASE);
    
    // Test API connection on load
    testAPIConnection();
});

// --- Toast notification helper ---
function ensureToastContainer() {
    if (!document.getElementById('toastContainer')) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.style.position = 'fixed';
        container.style.right = '1rem';
        container.style.bottom = '1rem';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '0.5rem';
        document.body.appendChild(container);
    }
}

function notify(message, type = 'info', timeout = 4000) {
    ensureToastContainer();
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.minWidth = '200px';
    toast.style.maxWidth = '360px';
    toast.style.padding = '0.75rem 1rem';
    toast.style.borderRadius = '8px';
    toast.style.color = '#fff';
    toast.style.boxShadow = '0 6px 18px rgba(2,6,23,0.4)';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(8px)';
    toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

    if (type === 'success') toast.style.background = 'linear-gradient(90deg,#16a34a,#059669)';
    else if (type === 'error') toast.style.background = 'linear-gradient(90deg,#dc2626,#b91c1c)';
    else if (type === 'warning') toast.style.background = 'linear-gradient(90deg,#f59e0b,#f97316)';
    else toast.style.background = 'linear-gradient(90deg,#0ea5e9,#0284c7)';

    toast.innerText = message;
    container.appendChild(toast);

    // animate in
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    if (timeout > 0) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(8px)';
            setTimeout(() => container.removeChild(toast), 220);
        }, timeout);
    }
    return toast;
}


// Test API connection
async function testAPIConnection() {
    console.log("Testing API connection...");
    try {
        const response = await fetch(API_BASE + '/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ action: 'test' })
        });
        
        if (response.ok) {
            console.log("✓ API connection test: SUCCESS");
        } else {
            console.warn("⚠ API connection test: Server responded with error");
        }
    } catch (error) {
        console.error("✗ API connection test: FAILED -", error.message);
        console.info("💡 Make sure:");
        console.info("   - XAMPP is running (Apache and MySQL)");
        console.info("   - Files are in xampp/htdocs/ridesphere/");
        console.info("   - Database is created (run setup_database.php)");
    }
}

// Utility function to check if user is logged in
function isLoggedIn() {
    return currentUser !== null;
}

// Demo functions for owner dashboard
function editVehicle(vehicleId) {
    notify('Edit vehicle functionality would go here for vehicle ID: ' + vehicleId, 'info');
}

function toggleVehicleStatus(vehicleId) {
    notify('Toggle vehicle status functionality would go here for vehicle ID: ' + vehicleId, 'info');
}

// Renter Interface Functions
let currentVehicleTypeFilter = 'all';
let currentSelectedVehicle = null;

function showMainInterface() {
    document.getElementById('vehiclesView').classList.add('hidden');
    document.getElementById('bookingsView').classList.add('hidden');
    document.querySelector('.renter-main-interface').classList.remove('hidden');
}

function showVehiclesView() {
    document.querySelector('.renter-main-interface').classList.add('hidden');
    document.getElementById('bookingsView').classList.add('hidden');
    document.getElementById('vehiclesView').classList.remove('hidden');
    loadVehiclesForRenter();
}

function showBookingsView() {
    document.querySelector('.renter-main-interface').classList.add('hidden');
    document.getElementById('vehiclesView').classList.add('hidden');
    document.getElementById('bookingsView').classList.remove('hidden');
    loadRenterBookings();
}

function sortVehicles(type, el) {
    currentVehicleTypeFilter = type;
    
    // Update active sort button
    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    if (el && el.classList) {
        el.classList.add('active');
    }
    
    loadVehiclesForRenter();
}

async function loadVehiclesForRenter() {
    console.log("Loading vehicles for renter view");
    
    try {
        const result = await apiCall('vehicles.php', {
            action: 'get_vehicles'
        });

        if(result.success) {
            let vehicles = result.vehicles;
            
            // Filter by type if not 'all'
            if (currentVehicleTypeFilter !== 'all') {
                vehicles = vehicles.filter(vehicle => vehicle.type === currentVehicleTypeFilter);
            }
            
            renderVehicles(vehicles);
        } else {
            console.error("Error loading vehicles:", result.message);
            document.getElementById("vehiclesContainer").innerHTML = `
                <div class="no-data">
                    <div class="icon">⚠️</div>
                    <h3>Error Loading Vehicles</h3>
                    <p>${result.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Load vehicles error:', error);
        document.getElementById("vehiclesContainer").innerHTML = `
            <div class="no-data">
                <div class="icon">⚠️</div>
                <h3>Connection Error</h3>
                <p>Unable to load vehicles. Please try again.</p>
            </div>
        `;
    }
}

async function loadRenterBookings() {
    console.log("Loading renter bookings");
    
    try {
        const result = await apiCall('bookings.php', {
            action: 'get_renter_bookings',
            // use actingRenterId if set (owner viewing as renter), otherwise use currentUser.id
            renter_id: actingRenterId || (currentUser ? currentUser.id : null)
        });

        if(result.success) {
            renderRenterBookings(result.bookings);
        } else {
            console.error("Error loading renter bookings:", result.message);
            document.getElementById("bookingsContainer").innerHTML = `
                <div class="no-data">
                    <div class="icon">⚠️</div>
                    <h3>Error Loading Bookings</h3>
                    <p>${result.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Load renter bookings error:', error);
        document.getElementById("bookingsContainer").innerHTML = `
            <div class="no-data">
                <div class="icon">⚠️</div>
                <h3>Connection Error</h3>
                <p>Unable to load your bookings. Please try again.</p>
            </div>
        `;
    }
}

function renderRenterBookings(bookings) {
    const container = document.getElementById("bookingsContainer");
    
    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="no-data">
                <div class="icon">📋</div>
                <h3>No Bookings Yet</h3>
                <p>You haven't booked any vehicles yet.</p>
                <button class="btn accent" onclick="showVehiclesView()" style="margin-top: 1rem;">
                    Browse Vehicles
                </button>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    bookings.forEach(booking => {
        const bookingCard = document.createElement("div");
        bookingCard.className = "booking-item";
        bookingCard.innerHTML = `
            <div class="booking-header">
                <div class="booking-vehicle">${booking.vehicle_name}</div>
                <span class="booking-status status-${booking.status}">${booking.status.toUpperCase()}</span>
            </div>
            
            <div class="booking-dates">
                📅 ${booking.start_date} to ${booking.end_date}
            </div>
            
            <div class="booking-amount">
                Total: ₱${booking.total_amount}
            </div>
            
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Vehicle Type:</span>
                    <span class="detail-value">${booking.vehicle_type}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Daily Rate:</span>
                    <span class="detail-value">₱${booking.daily_rate}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Owner:</span>
                    <span class="detail-value">${booking.owner_first_name} ${booking.owner_last_name}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Owner Contact:</span>
                    <span class="detail-value">${booking.owner_phone || 'N/A'}</span>
                </div>
            </div>
            
            <div style="margin-top: 1rem; font-size: 0.9rem; color: #94a3b8;">
                Booked on: ${new Date(booking.created_at).toLocaleDateString()}
            </div>
        `;
        container.appendChild(bookingCard);
    });
}

// Owner Dashboard Functions
let currentEditingVehicleId = null;

function showVehicleTypes() {
    document.getElementById('vehicleForm').style.display = 'none';
    document.getElementById('vehiclesExpandedView').classList.remove('active');
    document.getElementById('ownerBookingsView').style.display = 'none';
    document.getElementById('editVehicleForm').classList.remove('active');
}

function showAllVehicles() {
    loadOwnerVehicles();
    document.getElementById('expandedViewTitle').textContent = 'All Vehicles';
    document.getElementById('vehicleForm').style.display = 'none';
    document.getElementById('vehiclesExpandedView').classList.add('active');
    document.getElementById('ownerBookingsView').style.display = 'none';
    renderAllVehicles();
}

function showOwnerBookings() {
    document.getElementById('vehicleForm').style.display = 'none';
    document.getElementById('vehiclesExpandedView').classList.remove('active');
    document.getElementById('ownerBookingsView').style.display = 'block';
    loadOwnerBookings();
}

async function loadOwnerBookings() {
    console.log("Loading owner bookings");
    
    try {
        const result = await apiCall('bookings.php', {
            action: 'get_owner_bookings',
            owner_id: currentUser.id
        });

        if(result.success) {
            renderOwnerBookings(result.bookings);
        } else {
            console.error("Error loading owner bookings:", result.message);
            document.getElementById("ownerBookingsContainer").innerHTML = `
                <div class="no-data">
                    <div class="icon">⚠️</div>
                    <h3>Error Loading Bookings</h3>
                    <p>${result.message}</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Load owner bookings error:', error);
        document.getElementById("ownerBookingsContainer").innerHTML = `
            <div class="no-data">
                <div class="icon">⚠️</div>
                <h3>Connection Error</h3>
                <p>Unable to load bookings. Please try again.</p>
            </div>
        `;
    }
}

function renderOwnerBookings(bookings) {
    const container = document.getElementById("ownerBookingsContainer");
    
    if (bookings.length === 0) {
        container.innerHTML = `
            <div class="no-data">
                <div class="icon">📋</div>
                <h3>No Bookings Yet</h3>
                <p>You don't have any vehicle bookings yet.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = '';
    
    bookings.forEach(booking => {
        const bookingCard = document.createElement("div");
        bookingCard.className = "booking-item";
        
        let actionButtons = '';
        if (booking.status === 'pending') {
            actionButtons = `
                <div class="booking-actions">
                    <button class="btn-small success" onclick="updateBookingStatus(${booking.id}, 'confirmed')">
                        <i class="fas fa-check"></i>Confirm
                    </button>
                    <button class="btn-small danger" onclick="updateBookingStatus(${booking.id}, 'cancelled')">
                        <i class="fas fa-times"></i>Cancel
                    </button>
                </div>
            `;
        } else if (booking.status === 'confirmed') {
            actionButtons = `
                <div class="booking-actions">
                    <button class="btn-small accent" onclick="updateBookingStatus(${booking.id}, 'completed')">
                        <i class="fas fa-flag-checkered"></i>Mark Complete
                    </button>
                </div>
            `;
        }
        
        // build thumbnail if available
        const vehicleImg = booking.vehicle_image || booking.image_url || booking.image_path || '';
        const thumbHtml = vehicleImg ? `<div style="width:120px; height:80px; overflow:hidden; border-radius:8px; flex: 0 0 120px; margin-right:1rem;"><img src="${vehicleImg}" alt="${booking.vehicle_name}" style="width:100%; height:100%; object-fit:cover; display:block;"/></div>` : '';

        bookingCard.innerHTML = `
            <div style="display:flex; gap:1rem; align-items:flex-start;">
                ${thumbHtml}
                <div style="flex:1;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                        <div style="font-weight:700; font-size:1.05rem;">${booking.vehicle_name}</div>
                        <span class="booking-status status-${booking.status}">${booking.status.toUpperCase()}</span>
                    </div>
                    <div style="margin-top:0.5rem; color:#94a3b8;">📅 ${booking.start_date} to ${booking.end_date}</div>
                    <div style="margin-top:0.5rem; font-weight:700;">Total: ₱${booking.total_amount}</div>

                    <div class="detail-grid" style="margin-top:0.75rem;">
                        <div class="detail-item">
                            <span class="detail-label">Renter:</span>
                            <span class="detail-value">${booking.renter_first_name} ${booking.renter_last_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value">${booking.renter_phone || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Vehicle Type:</span>
                            <span class="detail-value">${booking.vehicle_type}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Daily Rate:</span>
                            <span class="detail-value">₱${booking.daily_rate}</span>
                        </div>
                    </div>

                    ${actionButtons}

                    <div style="margin-top: 1rem; font-size: 0.9rem; color: #94a3b8;">
                        Booked on: ${new Date(booking.created_at).toLocaleDateString()}
                    </div>
                </div>
            </div>
        `;
        container.appendChild(bookingCard);
    });
}

async function updateBookingStatus(bookingId, status) {
    try {
        const result = await apiCall('bookings.php', {
            action: 'update_booking_status',
            booking_id: bookingId,
            status: status,
            owner_id: currentUser.id
        });

        if (result.success) {
            notify(`Booking ${status} successfully!`, 'success');
            // Refresh owner bookings and vehicle lists so UI reflects new availability immediately
            await loadOwnerBookings();
            await loadOwnerVehicles();
            // If owner expanded view is active, re-render its grid
            try { renderAllVehicles(); } catch (e) { /* ignore if not visible */ }
            // Refresh renter-side vehicle list so availability updates for renters
            try { await loadVehiclesForRenter(); } catch (e) { /* ignore */ }
            try { await loadVehicles(); } catch (e) { /* ignore */ }
        } else {
            notify("Error updating booking: " + result.message, 'error');
        }
    } catch (error) {
        console.error('Update booking status error:', error);
        notify("An unexpected error occurred while updating the booking.", 'error');
    }
}

// Updated renderAllVehicles function for owner dashboard
function renderAllVehicles() {
    const container = document.getElementById('expandedVehiclesGrid');
    container.innerHTML = '';
    
    if (vehicles.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #94a3b8;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🚗</div>
                <h3>No vehicles found</h3>
                <p>No vehicles match your current selection</p>
            </div>
        `;
        return;
    }
    
    vehicles.forEach(vehicle => {
        const card = document.createElement('div');
        card.className = 'expanded-vehicle-card';
        const expandedImageSrc = vehicle.image_url || vehicle.image || vehicle.image_path || '';
        card.innerHTML = `
            <div class="expanded-vehicle-content" style="grid-column: 1 / -1;">
                <div class="expanded-vehicle-header">
                    <div>
                        <h3 style="margin: 0 0 0.5rem 0;">${vehicle.name}</h3>
                        <div style="color: var(--accent); font-weight: 600; font-size: 1.5rem;">
                            ₱${vehicle.rate}/day
                        </div>
                    </div>
                    <span style="background: var(--primary); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                        ${vehicle.type}
                    </span>
                </div>
                
                <div class="expanded-vehicle-specs">
                    <div class="expanded-vehicle-spec">
                        <div>🪑</div>
                        <div>${vehicle.seats || 4} Seats</div>
                    </div>
                    <div class="expanded-vehicle-spec">
                        <div>🚪</div>
                        <div>${vehicle.doors || 4} Doors</div>
                    </div>
                    <div class="expanded-vehicle-spec">
                        <div>🎒</div>
                        <div>${vehicle.baggage || 2} Bags</div>
                    </div>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <div style="font-weight: 600; margin-bottom: 0.5rem;">Details:</div>
                    <div style="color: #94a3b8; font-size: 0.9rem;">
                        <div>📍 ${vehicle.location}</div>
                        <div>🕒 ${vehicle.hours}</div>
                        ${vehicle.description ? `<div>📝 ${vehicle.description}</div>` : ''}
                    </div>
                </div>
                
                <div class="expanded-vehicle-actions">
                    <button class="btn-small accent" onclick="editVehicle(${vehicle.id})" style="flex: 2;">
                        <i class="fas fa-edit"></i>Edit
                    </button>
                    <button class="btn-small ${vehicle.status === 'available' ? 'secondary' : 'accent'}" 
                            onclick="toggleVehicleStatus(${vehicle.id})" style="flex: 1;">
                        <i class="fas ${vehicle.status === 'available' ? 'fa-pause' : 'fa-play'}"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(card);
    });
}

function editVehicle(vehicleId) {
    const vehicle = vehicles.find(v => v.id === vehicleId);
    if (!vehicle) return;

    currentEditingVehicleId = vehicleId;

    // Fill form with vehicle data
    document.getElementById('editVehicleName').value = vehicle.name;
    document.getElementById('editVehicleType').value = vehicle.type;
    document.getElementById('editVehicleRate').value = vehicle.rate;
    document.getElementById('editVehicleHours').value = vehicle.hours;
    document.getElementById('editVehicleLocation').value = vehicle.location;
    document.getElementById('editVehicleDescription').value = vehicle.description || '';

    // Populate edit image preview if image exists
    try {
        const imgSrc = vehicle.image_url || vehicle.image || vehicle.image_path || '';
        const editPreview = document.getElementById('editPreviewImg');
        const editImagePreview = document.getElementById('editImagePreview');
        const editUploadPlaceholder = document.getElementById('editUploadPlaceholder');
        if (imgSrc && editPreview && editImagePreview) {
            editPreview.src = imgSrc;
            editImagePreview.style.display = 'block';
            if (editUploadPlaceholder) editUploadPlaceholder.style.display = 'none';
        } else {
            if (editImagePreview) editImagePreview.style.display = 'none';
            if (editUploadPlaceholder) editUploadPlaceholder.style.display = 'block';
        }
    } catch (e) {
        console.warn('Unable to set edit image preview', e);
    }

    // Show edit form
    document.getElementById('editVehicleForm').classList.add('active');
    document.getElementById('vehiclesExpandedView').classList.remove('active');
    document.getElementById('ownerBookingsView').style.display = 'none';
}

function hideEditForm() {
    document.getElementById('editVehicleForm').classList.remove('active');
    showVehicleTypes();
}

function updateVehicle() {
    if (!currentEditingVehicleId) return;

    const name = document.getElementById('editVehicleName').value.trim();
    const type = document.getElementById('editVehicleType').value;
    const rate = document.getElementById('editVehicleRate').value;
    const hours = document.getElementById('editVehicleHours').value.trim();
    const location = document.getElementById('editVehicleLocation').value.trim();
    const description = document.getElementById('editVehicleDescription').value.trim();
    const imageInput = document.getElementById('editVehicleImageInput');

    if (!name || !type || !rate || !hours || !location) {
        notify('Please fill all required fields.', 'warning');
        return;
    }

    // Show loading state
    const updateBtn = document.querySelector('#editVehicleForm .btn.accent');
    const originalText = updateBtn ? updateBtn.innerHTML : '';
    if (updateBtn) {
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Updating...';
        updateBtn.disabled = true;
    }

    (async () => {
        try {
            const formData = new FormData();
            formData.append('action', 'update_vehicle');
            formData.append('vehicle_id', currentEditingVehicleId);
            formData.append('owner_id', currentUser.id);
            formData.append('name', name);
            formData.append('type', type);
            formData.append('rate', rate);
            formData.append('hours', hours);
            formData.append('location', location);
            formData.append('description', description);

            if (imageInput && imageInput.files && imageInput.files[0]) {
                formData.append('vehicle_image', imageInput.files[0]);
            }

            const response = await fetch(API_BASE + '/vehicles.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                notify('Vehicle updated!', 'success');
                await loadOwnerVehicles();
                renderAllVehicles();
                hideEditForm();
            } else {
                notify('Error updating vehicle: ' + (result.message || 'Unknown'), 'error');
            }
        } catch (error) {
            console.error('Update vehicle error:', error);
            notify('An unexpected error occurred while updating the vehicle.', 'error');
        } finally {
            if (updateBtn) {
                updateBtn.innerHTML = originalText;
                updateBtn.disabled = false;
            }
        }
    })();
}

function deleteVehicle(vehicleId) {
    const idToDelete = vehicleId || currentEditingVehicleId;
    if (!idToDelete) return;

    showConfirm('Delete vehicle', 'Are you sure you want to delete this vehicle? This action cannot be undone.').then(async (confirmed) => {
        if (!confirmed) return;
        try {
            const response = await fetch(API_BASE + '/vehicles.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'delete_vehicle',
                    vehicle_id: idToDelete,
                    owner_id: currentUser.id
                })
            });

            const result = await response.json();
            if (result.success) {
                notify('Vehicle deleted!', 'success');
                await loadOwnerVehicles();
                try { renderAllVehicles(); } catch (e) {}
                hideEditForm();
            } else {
                notify('Error deleting vehicle: ' + (result.message || 'Unknown'), 'error');
            }
        } catch (error) {
            console.error('Delete vehicle error:', error);
            notify('An unexpected error occurred while deleting the vehicle.', 'error');
        }
    });
}

// --- Modal confirm helper ---
let __confirmResolve = null;
function showConfirm(title, message) {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const body = document.getElementById('confirmBody');
        const titleEl = document.getElementById('confirmTitle');
        titleEl.textContent = title || 'Confirm';
        body.innerText = message || '';
        modal.classList.remove('hidden');
        modal.classList.add('open');
        __confirmResolve = resolve;
    });
}

function closeConfirm(result) {
    const modal = document.getElementById('confirmModal');
    modal.classList.add('hidden');
    modal.classList.remove('open');
    if (typeof __confirmResolve === 'function') {
        __confirmResolve(result === true);
        __confirmResolve = null;
    }
}

// Export functions for global access (for browser console debugging)
// OTP Modal and functions
const otpState = {
    email: null,
    isSignupFlow: false,
    resendTimer: null,
    resendCooldown: 0
};
// Ensure OTP listeners are initialized only once
let __otpListenersInitialized = false;

function showOTPModalForSignup(email) {
    console.log('showOTPModalForSignup called with email:', email);
    otpState.email = email;
    otpState.isSignupFlow = true;
    
    const modal = document.getElementById('otpModal');
    if (!modal) {
        console.error('OTP modal not found in DOM');
        return;
    }

    // Temporary debug overlay: visible even if modal is somehow hidden by CSS
    try {
        let dbg = document.getElementById('otpDebugOverlay');
        if (!dbg) {
            dbg = document.createElement('div');
            dbg.id = 'otpDebugOverlay';
            dbg.style.position = 'fixed';
            dbg.style.right = '12px';
            dbg.style.top = '12px';
            dbg.style.background = 'rgba(220,38,38,0.95)';
            dbg.style.color = 'white';
            dbg.style.padding = '10px 14px';
            dbg.style.borderRadius = '8px';
            dbg.style.zIndex = '10000000';
            dbg.style.fontWeight = '700';
            dbg.style.boxShadow = '0 6px 20px rgba(0,0,0,0.4)';
            dbg.style.pointerEvents = 'none';
            document.body.appendChild(dbg);
        }
        dbg.textContent = 'OTP modal opened for ' + (email || '(no email)');
        // auto-remove after 6s
        setTimeout(() => {
            const el = document.getElementById('otpDebugOverlay');
            if (el) el.remove();
        }, 6000);
    } catch (e) { console.warn('Could not create otp debug overlay', e); }

    // Set email display
    const emailDisplay = document.getElementById('otpEmailDisplay');
    if (emailDisplay) {
        emailDisplay.textContent = email;
    }

    // Clear single OTP input
    const otpInput = document.getElementById('otpInput');
    if (otpInput) otpInput.value = '';

    // Reset resend button and start countdown
    resetResendButton();
    startResendCountdown();

    // Move modal to document.body to avoid being clipped by other containers
    try { if (modal.parentNode !== document.body) document.body.appendChild(modal); } catch (e) {}
    // Make visible: remove the 'hidden' marker first, then add 'open'
    modal.classList.remove('hidden');
    // also ensure inline display in case CSS classes are overridden
    try {
        // Force inline styles to ensure visibility in stubborn environments
        modal.style.display = 'flex';
        modal.style.visibility = 'visible';
        modal.style.zIndex = '999999';
        modal.style.pointerEvents = 'auto';
    } catch (e) { console.warn('Could not apply inline modal styles', e); }
    // small delay to ensure CSS reflow so transition plays
    window.requestAnimationFrame(() => {
        modal.classList.add('open');
    });

    // Prevent background scroll while modal open
    try { document.body.style.overflow = 'hidden'; } catch (e) {}

    // Ensure input enabled and attach single-input listeners
    try { if (otpInput) { otpInput.disabled = false; if (otpInput.removeAttribute) otpInput.removeAttribute('disabled'); } } catch (e) {}
    setupOTPSingleListener();
    if (otpInput) {
        // focus after a short timeout to ensure modal is visible
        setTimeout(() => otpInput.focus(), 120);
    }
    // set aria attributes for accessibility
    modal.setAttribute('aria-hidden', 'false');
    // Debug: print computed style to help diagnose CSS overrides
    try {
        const cs = window.getComputedStyle(modal);
        console.log('otpModal classList after open:', modal.className, 'inline style.display=', modal.style.display, 'computed display=', cs.display, 'computed visibility=', cs.visibility, 'computed zIndex=', cs.zIndex);
    } catch (e) {
        console.log('otpModal classList after open:', modal.className, 'style.display=', modal.style.display);
    }
    // Force modal-content visible (in case transitions or CSS keep it hidden)
    try {
        const content = modal.querySelector('.modal-content');
        if (content) {
            content.style.opacity = '1';
            content.style.transform = 'translateY(0) scale(1)';
            content.style.visibility = 'visible';
            content.style.zIndex = '1000001';
            content.style.pointerEvents = 'auto';
            const cs2 = window.getComputedStyle(content);
            console.log('otpModal .modal-content computed display=', cs2.display, 'opacity=', cs2.opacity, 'visibility=', cs2.visibility, 'zIndex=', cs2.zIndex);
        } else {
            console.warn('otpModal modal-content not found');
        }
    } catch (e) {
        console.warn('Failed to force modal-content styles', e);
    }
}

// Expose a quick helper for debugging: open OTP modal from console
window.Ridesphere = window.Ridesphere || {};
window.Ridesphere.forceOpenOTP = function(email = '') {
    try {
        if (!email) {
            const v = prompt('Email to show in OTP modal (for testing):');
            if (!v) return;
            email = v.trim();
        }
        showOTPModalForSignup(email);
    } catch (e) {
        console.error('forceOpenOTP failed:', e);
    }
};

function closeOTPModal() {
    const modal = document.getElementById('otpModal');
    if (modal) {
        modal.classList.remove('open');
        // allow transition to finish then hide fully
        setTimeout(() => modal.classList.add('hidden'), 220);
    }
    
    // Clear single OTP input
    const otpInput = document.getElementById('otpInput');
    if (otpInput) otpInput.value = '';
    
    // Stop resend timer
    if (otpState.resendTimer) {
        clearInterval(otpState.resendTimer);
        otpState.resendTimer = null;
    }
    
    // Reset state
    otpState.email = null;
    otpState.isSignupFlow = false;
    try { document.body.style.overflow = ''; } catch (e) {}
    if (modal) modal.setAttribute('aria-hidden', 'true');
}

async function verifyOTP() {
    // Collect OTP from single input field
    const otpInput = document.getElementById('otpInput');
    let otp = '';
    if (otpInput) otp = String(otpInput.value || '').replace(/[^0-9]/g, '');
    
    if (!otp || otp.length !== 6) {
        notify('Please enter a valid 6-digit code', 'warning');
        return;
    }
    
    if (!otpState.email) {
        notify('Email not found in session', 'error');
        return;
    }
    
    try {
        // Verify OTP against database
        const result = await apiCall('auth.php', {
            action: 'verify_otp_for_email',
            email: otpState.email,
            otp: otp
        });
        
        if (result.success) {
            if (otpState.isSignupFlow) {
                // Create account after verification
                const signupData = window.pendingSignup;
                if (!signupData) {
                    notify('Signup data not found', 'error');
                    return;
                }
                
                // Create the account
                const signupResult = await apiCall('auth.php', {
                    action: 'signup',
                    firstName: signupData.firstName,
                    middleName: signupData.middleName,
                    lastName: signupData.lastName,
                    phoneNumber: signupData.phoneNumber,
                    address: signupData.address,
                    email: signupData.email,
                    password: signupData.password,
                    role: signupData.role,
                    email_verified: true  // Email already verified via OTP
                });
                
                if (signupResult.success) {
                    notify('Account created successfully! You can now login.', 'success');
                    
                    // Clear signup fields
                    const idsToClear = ['firstName','middleName','lastName','phoneNumber','address','newEmail','newPassword'];
                    idsToClear.forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.value = '';
                    });
                    
                    // Close OTP modal and show login
                    closeOTPModal();
                    showLogin();
                    
                    // Prefill email on login screen
                    const emailEl = document.getElementById('email');
                    if (emailEl) emailEl.value = signupData.email;
                } else {
                    notify('Error creating account: ' + (signupResult.message || 'Unknown error'), 'error');
                }
            }
        } else {
            notify('Invalid OTP: ' + (result.message || 'Please try again'), 'error');
        }
    } catch (error) {
        console.error('OTP verification error:', error);
        notify('Error verifying OTP: ' + error.message, 'error');
    }
}

async function resendOTP() {
    if (!otpState.email) {
        notify('Email not found', 'error');
        return;
    }
    
    if (otpState.resendCooldown > 0) {
        notify('Please wait before requesting a new code', 'warning');
        return;
    }
    
    try {
        const result = await apiCall('auth.php', {
            action: 'send_otp_to_email',
            email: otpState.email
        });
        
        if (result.success) {
            notify('New OTP sent to ' + otpState.email, 'success');
            
            // Clear single input and focus
            const otpInput = document.getElementById('otpInput');
            if (otpInput) { otpInput.value = ''; otpInput.focus(); }
            
            // Restart countdown
            startResendCountdown();
        } else {
            notify('Error: ' + (result.message || 'Failed to resend OTP'), 'error');
        }
    } catch (error) {
        console.error('Resend OTP error:', error);
        notify('Error resending OTP: ' + error.message, 'error');
    }
}

function setupOTPSingleListener() {
    if (__otpListenersInitialized) {
        // listeners already attached; just ensure input cleared
        const existing = document.getElementById('otpInput');
        if (existing) existing.value = '';
        return;
    }

    const otpInput = document.getElementById('otpInput');
    if (!otpInput) return;

    // Input handler: allow only digits and auto-submit on 6 digits
    otpInput.addEventListener('input', (e) => {
        const cleaned = (e.target.value || '').replace(/[^0-9]/g, '');
        e.target.value = cleaned.slice(0, 6);
        if (e.target.value.length === 6) {
            // small delay so UI updates before submit
            setTimeout(() => verifyOTP(), 180);
        }
    });

    otpInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            verifyOTP();
        }
        if (e.key === 'Backspace') {
            // allow natural behavior; no special focus movement needed
        }
    });

    otpInput.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text') || '';
        const digits = paste.replace(/[^0-9]/g, '').slice(0, 6);
        otpInput.value = digits;
        if (digits.length === 6) setTimeout(() => verifyOTP(), 120);
    });

    __otpListenersInitialized = true;
}

function startResendCountdown() {
    otpState.resendCooldown = 60;
    const resendBtn = document.getElementById('resendOTPBtn');
    const countdownEl = document.getElementById('resendCountdown');
    const resendTimeEl = document.getElementById('resendTime');
    
    if (resendBtn) resendBtn.style.display = 'none';
    if (countdownEl) countdownEl.style.display = 'block';
    
    if (otpState.resendTimer) {
        clearInterval(otpState.resendTimer);
    }
    
    otpState.resendTimer = setInterval(() => {
        otpState.resendCooldown--;
        if (resendTimeEl) {
            const mins = String(Math.floor(otpState.resendCooldown / 60)).padStart(2, '0');
            const secs = String(otpState.resendCooldown % 60).padStart(2, '0');
            resendTimeEl.textContent = `${mins}:${secs}`;
        }
        
        if (otpState.resendCooldown <= 0) {
            clearInterval(otpState.resendTimer);
            otpState.resendTimer = null;
            if (resendBtn) {
                resendBtn.style.display = 'block';
                resendBtn.disabled = false;
            }
            if (countdownEl) countdownEl.style.display = 'none';
        }
    }, 1000);
}

function resetResendButton() {
    otpState.resendCooldown = 0;
    const resendBtn = document.getElementById('resendOTPBtn');
    const countdownEl = document.getElementById('resendCountdown');
    
    if (resendBtn) {
        resendBtn.disabled = false;
        resendBtn.style.display = 'block';
    }
    if (countdownEl) {
        countdownEl.style.display = 'none';
    }
    
    if (otpState.resendTimer) {
        clearInterval(otpState.resendTimer);
        otpState.resendTimer = null;
    }
}

window.Ridesphere = {
    apiCall,
    showSignup,
    showLogin,
    backToChoice,
    signup,
    login,
    logout,
    addVehicle,
    loadVehicles,
    loadOwnerVehicles,
    openModal,
    closeModal,
    bookNow,
    openMessageModal,
    closeMessageModal,
    toggleOwnerRenterView,
    isLoggedIn,
    getCurrentUser: () => currentUser,
    getVehicles: () => vehicles,
    showVehicleForm,
    hideVehicleForm,
    updateDashboardStats,
    showMainInterface,
    showVehiclesView,
    showBookingsView,
    loadVehiclesForRenter,
    loadRenterBookings,
    bookVehicle,
    showOwnerBookings,
    loadOwnerBookings,
    updateBookingStatus,
    showOTPModalForSignup,
    closeOTPModal,
    verifyOTP,
    resendOTP,
};

console.log("Ridesphere app loaded successfully!");
console.log("For debugging, use Ridesphere object in console:");
console.log("  - Ridesphere.getCurrentUser()");
console.log("  - Ridesphere.getVehicles()");
console.log("  - Ridesphere.apiCall()");

// Accessibility helper: enable Enter/Space to activate non-button elements with role="button"
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[role="button"]:not(button)').forEach(el => {
        el.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                el.click();
            }
        });
        // Ensure cursor indicates interactivity
        el.style.cursor = el.style.cursor || 'pointer';
    });
});