<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ridesphere • Car Rental Platform</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Add crossorigin to reduce credentialed requests which can trigger Tracking Prevention -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css"/>
</head>
<body>
    <!-- Choice Screen -->
  <div id="choiceScreen" class="center-screen bg-image hidden">
    <div class="login-box">
      <div class="logo-container">
        <img src="logo.png" alt="Ridesphere Logo" class="logo">
        <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Ridesphere</h1>
        <p style="color: #94a3b8; margin-bottom: 2rem;">Turn idle vehicles into income opportunities</p>
      </div>
      <button class="btn primary" onclick="showSignup()" style="width: 100%; margin-bottom: 1rem;">
        <i class="fas fa-user-plus"></i>Create Account
      </button>
      <button class="btn secondary" onclick="showLogin()" style="width: 100%;">
        <i class="fas fa-sign-in-alt"></i>Sign In
      </button>
    </div>
  </div>
  <!-- Signup Screen -->
  <div id="signupScreen" class="center-screen bg-image hidden">
  <div class="login-box">
    <div class="logo-container">
      <img src="logo.png" alt="Ridesphere Logo" class="logo">
      <h2>Join Ridesphere</h2>
      <p style="color: #94a3b8;">Create your account in seconds</p>
    </div>
    
      <div class="form-grid">
      <div class="form-group">
        <label for="firstName" class="visually-hidden">First name</label>
        <input type="text" id="firstName" name="firstName" class="form-input" placeholder="First Name *" autocomplete="given-name" required>
      </div>
      <div class="form-group">
        <label for="middleName" class="visually-hidden">Middle name</label>
        <input type="text" id="middleName" name="middleName" class="form-input" placeholder="Middle Name" autocomplete="additional-name">
      </div>
      <div class="form-group">
        <label for="lastName" class="visually-hidden">Last name</label>
        <input type="text" id="lastName" name="lastName" class="form-input" placeholder="Last Name *" autocomplete="family-name" required>
      </div>
    </div>
    
    <div class="form-group">
      <label for="phoneNumber" class="visually-hidden">Phone number</label>
      <input type="tel" id="phoneNumber" name="phoneNumber" class="form-input" placeholder="Phone Number" autocomplete="tel">
    </div>
    
    <div class="form-group">
      <label for="address" class="visually-hidden">Address</label>
      <input type="text" id="address" name="address" class="form-input" placeholder="Complete Address" autocomplete="street-address">
    </div>
    
    <div class="form-group">
      <label for="newEmail" class="visually-hidden">Email address</label>
      <input type="email" id="newEmail" name="newEmail" class="form-input" placeholder="Email address *" autocomplete="email" required>
    </div>

    <div class="form-group">
      <label for="newPassword" class="visually-hidden">Create password</label>
      <input type="password" id="newPassword" name="newPassword" class="form-input" placeholder="Create Password *" autocomplete="new-password" required>
    </div>

    <div class="form-group">
      <label for="newRole" class="visually-hidden">Account role</label>
      <select id="newRole" name="newRole" class="form-select" aria-label="Account role" required>
        <option value="renter">🚗 Renter - I want to rent vehicles</option>
        <option value="owner">💼 Owner - I want to list my vehicles</option>
      </select>
    </div>
    
    <!-- Terms and Conditions checkbox removed per request -->
    
    <button id="createAccountBtn" type="button" class="btn primary" onclick="signup(event)" style="width: 100%; margin-bottom: 1rem;">
      <i class="fas fa-rocket"></i>Create Account
    </button>
    <button class="btn secondary" onclick="backToChoice()" style="width: 100%;">
      <i class="fas fa-arrow-left"></i>Back
    </button>
  </div>
</div>

      <!-- Login Screen -->
  <div id="loginScreen" class="center-screen bg-image">
    <div class="login-box">
      <div class="logo-container">
        <img src="logo.png" alt="Ridesphere Logo" class="logo">
        <h2>Welcome Back</h2>
        <p style="color: #94a3b8;">Sign in to your account</p>
      </div>
      
      <div class="form-group">
        <label for="email" class="visually-hidden">Email address</label>
        <input type="email" id="email" name="email" class="form-input" placeholder="Email" autocomplete="username" required>
      </div>
      
      <div class="form-group">
        <label for="password" class="visually-hidden">Password</label>
        <input type="password" id="password" name="password" class="form-input" placeholder="Password" autocomplete="current-password" required>
      </div>
      
      <button class="btn primary" onclick="login()" style="width: 100%; margin-bottom: 1rem;">
        <i class="fas fa-sign-in-alt"></i>Sign In
      </button>
      <div style="text-align: center; margin-top: 1rem; color: var(--muted);">
        <div>Don't have an account?</div>
        <button class="btn secondary" onclick="showSignup()" style="margin-top: .5rem;">Create an account</button>
      </div>
    </div>
  </div>

  <div id="mainApp" class="hidden">
    <header>
      <div class="renter-brand">
        <img src="logo.png" alt="Ridesphere Logo" class="renter-logo">
        <h1>Ridesphere</h1>
      </div>
      <div class="user-nav">
        <span class="user-welcome" id="currentUserName">Welcome, User</span>
        <div id="toggleViewContainer"></div>
        <div id="profileContainer" style="position:relative; display:inline-block;">
          <button class="btn secondary" onclick="toggleProfileDropdown(event)" id="profileBtn" aria-label="Open profile" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-user-circle"></i>
            <span id="profileName" style="margin-left:0.5rem; display:inline-block;">Profile</span>
          </button>
          <div id="profileDropdown" class="profile-dropdown" role="menu" aria-hidden="true" style="display:none; position:absolute; right:0; top:calc(100% + 8px); min-width:160px; background:#fff; border:1px solid rgba(0,0,0,0.08); box-shadow:0 8px 24px rgba(15,23,42,0.08); border-radius:8px; z-index:10000;">
            <div style="display:flex; flex-direction:column;">
              <button id="dropdownLogoutBtn" class="dropdown-item" onclick="handleProfileLogout()" style="padding:10px 14px; text-align:left; border:none; background:none; cursor:pointer; width:100%;">Log out</button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section id="renterDashboard" class="hidden">
      <div class="renter-dashboard">
        <!-- Renter profile removed per request -->

        <!-- Main Renter Interface -->
        <div class="renter-main-interface">
          <div class="renter-quick-actions">
            <div class="renter-action-card" role="button" tabindex="0" onclick="showVehiclesView()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); this.click(); }" aria-label="View available vehicles" title="View available vehicles">
              <div class="renter-action-icon">🚗</div>
              <h3>View Vehicles</h3>
              <p>Browse and book available vehicles from our network of trusted owners</p>
            </div>
            <div class="renter-action-card" role="button" tabindex="0" onclick="showBookingsView()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); this.click(); }" aria-label="View your bookings" title="View your bookings">
              <div class="renter-action-icon">📋</div>
              <h3>Booked Vehicles</h3>
              <p>Manage your current bookings and view your rental history</p>
            </div>
          </div>
        </div>

        <!-- Vehicles View -->
        <div id="vehiclesView" class="renter-view hidden">
          <div class="view-header">
            <h2>Available Vehicles</h2>
            <button class="back-button" onclick="showMainInterface()">
              <i class="fas fa-arrow-left"></i>Back to Main
            </button>
          </div>
          
          <div class="sort-buttons">
            <button class="sort-btn active" onclick="sortVehicles('all', this)">All Vehicles</button>
            <button class="sort-btn" onclick="sortVehicles('Compact', this)">Compact</button>
            <button class="sort-btn" onclick="sortVehicles('Economy', this)">Economy</button>
            <button class="sort-btn" onclick="sortVehicles('SUV', this)">SUV</button>
            <button class="sort-btn" onclick="sortVehicles('Luxury', this)">Luxury</button>
            <button class="sort-btn" onclick="sortVehicles('Motorcycle', this)">Motorcycle</button>
          </div>
          
          <div class="vehicles-container" id="vehiclesContainer">
            <!-- Vehicles will be loaded here -->
          </div>
        </div>

        <!-- Bookings View -->
        <div id="bookingsView" class="renter-view hidden">
          <div class="view-header">
            <h2>Your Bookings</h2>
            <button class="back-button" onclick="showMainInterface()">
              <i class="fas fa-arrow-left"></i>Back to Main
            </button>
          </div>
          
          <div class="bookings-container" id="bookingsContainer">
            <!-- Bookings will be loaded here -->
          </div>
        </div>
      </div>
    </section>

       <!-- Enhanced Owner Dashboard -->
    <section id="ownerDashboard" class="hidden">
      <div class="owner-dashboard">
        <div class="container">
          <!-- Owner Header with Logo -->
          <div class="owner-header">
            <h1 class="dashboard-title">Vehicle Management</h1>
          </div>

          <!-- Quick Actions -->
          <div class="quick-actions">
            <div class="action-card" role="button" tabindex="0" onclick="showVehicleForm()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); this.click(); }" aria-label="Add new vehicle" title="Add new vehicle">
              <div class="icon">🚗</div>
              <h3>Add New Vehicle</h3>
              <p>List a new vehicle for rent</p>
            </div>
            <div class="action-card" role="button" tabindex="0" onclick="showAllVehicles()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); this.click(); }" aria-label="View all vehicles" title="View all vehicles">
              <div class="icon">📋</div>
              <h3>All Vehicles</h3>
              <p>View all your vehicles</p>
            </div>
            <div class="action-card" role="button" tabindex="0" onclick="showOwnerBookings()" onkeydown="if(event.key==='Enter'||event.key===' ') { event.preventDefault(); this.click(); }" aria-label="Manage bookings" title="Manage bookings">
              <div class="icon">📅</div>
              <h3>Bookings</h3>
              <p>Manage reservations</p>
            </div>
          </div>

          <!-- All Vehicles View -->
          <div class="vehicles-expanded-view" id="vehiclesExpandedView">
            <button class="back-button" onclick="showVehicleTypes()">
              <i class="fas fa-arrow-left"></i>Back to Dashboard
            </button>
            <h3 id="expandedViewTitle" style="margin-bottom: 1.5rem;">All Vehicles</h3>
            <div class="expanded-vehicles-grid" id="expandedVehiclesGrid">
              <!-- All vehicles will be loaded here -->
            </div>
          </div>

          <!-- Bookings View -->
          <div class="bookings-view" id="ownerBookingsView" style="display: none;">
            <button class="back-button" onclick="showVehicleTypes()">
              <i class="fas fa-arrow-left"></i>Back to Dashboard
            </button>
            <h3 style="margin-bottom: 1.5rem;">Vehicle Bookings</h3>
            <div class="bookings-container" id="ownerBookingsContainer">
              <!-- Owner bookings will be loaded here -->
            </div>
          </div>

      <!-- embedded API removed: API endpoints belong in separate PHP files (e.g., `vehicles.php`). -->
          <!-- Add Vehicle Form -->
          <div class="vehicle-form" id="vehicleForm" style="display: none;">
            <h3 style="margin-bottom: 1.5rem;">Add New Vehicle</h3>
            <div class="form-grid">
              <div class="form-group">
                <input type="text" id="vehicleName" class="form-input" placeholder="Vehicle Name & Model" required>
              </div>
              <div class="form-group">
                <select id="vehicleType" class="form-select" required>
                  <option value="">Select Type</option>
                  <option value="Compact">🚗 Compact Car</option>
                  <option value="Economy">💰 Economy Car</option>
                  <option value="SUV">🚙 SUV</option>
                  <option value="Luxury">⭐ Luxury Vehicle</option>
                  <option value="Motorcycle">🏍️ Motorcycle</option>
                </select>
              </div>
              <div class="form-group">
                <input type="number" id="vehicleRate" class="form-input" placeholder="Daily Rate (₱)" min="0" step="0.01" required>
              </div>
              <div class="form-group">
                <input type="text" id="vehicleHours" class="form-input" placeholder="Available Hours (e.g., 9AM-6PM)" required>
              </div>
              <div class="form-group">
                <input type="text" id="vehicleLocation" class="form-input" placeholder="Pickup Location" required>
              </div>
            </div>
            <div class="form-group">
              <textarea id="vehicleDescription" class="form-input" placeholder="Vehicle description, features, special notes..." rows="3"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Upload Vehicle Image</label>
              <div id="imageUploadArea" style="border: 1px dashed rgba(255,255,255,0.08); padding: 1rem; border-radius: 12px; display: flex; gap: 1rem; align-items: center;">
                <div id="uploadPlaceholder" style="width: 120px; height: 80px; background: linear-gradient(135deg, #334155, #475569); display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #fff;">
                  <div style="text-align: center;">
                    <div style="font-size: 1.6rem;">📷</div>
                    <div style="font-size: 0.75rem; opacity: 0.9;">No Image</div>
                  </div>
                </div>
                <div id="imagePreview" style="display:none; width: 120px; height: 80px; overflow: hidden; border-radius: 8px;">
                  <img id="previewImg" src="" alt="Preview" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
                <div style="flex:1;">
                  <label for="vehicleImageInput" class="visually-hidden">Upload vehicle image</label>
                  <input type="file" id="vehicleImageInput" name="vehicleImageInput" accept="image/*" onchange="previewImage(this)" aria-label="Upload vehicle image" title="Upload vehicle image" />
                  <div style="color:#94a3b8; font-size:0.85rem; margin-top:0.5rem;">Optional image — will be stored as base64 in the DB.</div>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn secondary" onclick="hideVehicleForm()">Cancel</button>
              <button class="btn accent" onclick="addVehicle()">
                <i class="fas fa-plus"></i>Add Vehicle
              </button>
            </div>
          </div>

          <!-- Edit Vehicle Form -->
          <div class="edit-vehicle-form" id="editVehicleForm">
            <h3 style="margin-bottom: 1.5rem;">Edit Vehicle</h3>
            <div class="form-grid">
              <div class="form-group">
                <input type="text" id="editVehicleName" class="form-input" placeholder="Vehicle Name & Model" required>
              </div>
              <div class="form-group">
                <select id="editVehicleType" class="form-select" required>
                  <option value="">Select Type</option>
                  <option value="Compact">🚗 Compact Car</option>
                  <option value="Economy">💰 Economy Car</option>
                  <option value="SUV">🚙 SUV</option>
                  <option value="Luxury">⭐ Luxury Vehicle</option>
                  <option value="Motorcycle">🏍️ Motorcycle</option>
                </select>
              </div>
              <div class="form-group">
                <input type="number" id="editVehicleRate" class="form-input" placeholder="Daily Rate (₱)" min="0" step="0.01" required>
              </div>
              <div class="form-group">
                <input type="text" id="editVehicleHours" class="form-input" placeholder="Available Hours (e.g., 9AM-6PM)" required>
              </div>
              <div class="form-group">
                <input type="text" id="editVehicleLocation" class="form-input" placeholder="Pickup Location" required>
              </div>
            </div>
            <div class="form-group">
              <textarea id="editVehicleDescription" class="form-input" placeholder="Vehicle description, features, special notes..." rows="3"></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Current Vehicle Image</label>
              <div id="editImageUploadArea" style="border: 1px dashed rgba(255,255,255,0.04); padding: 0.75rem; border-radius: 12px; display: flex; gap: 1rem; align-items: center;">
                <div id="editUploadPlaceholder" style="width: 120px; height: 80px; background: linear-gradient(135deg, #334155, #475569); display: flex; align-items: center; justify-content: center; border-radius: 8px; color: #fff;">
                  <div style="text-align: center;">
                    <div style="font-size: 1.6rem;">📷</div>
                    <div style="font-size: 0.75rem; opacity: 0.9;">No Image</div>
                  </div>
                </div>
                <div id="editImagePreview" style="display:none; width: 120px; height: 80px; overflow: hidden; border-radius: 8px;">
                  <img id="editPreviewImg" src="" alt="Preview" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
                <div style="flex:1;">
                  <label for="editVehicleImageInput" class="visually-hidden">Edit vehicle image</label>
                  <input type="file" id="editVehicleImageInput" name="editVehicleImageInput" accept="image/*" onchange="previewEditImage(this)" aria-label="Edit vehicle image" title="Edit vehicle image" />
                  <div style="color:#94a3b8; font-size:0.85rem; margin-top:0.5rem;">Choose a new image to replace the current one (optional).</div>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn secondary" onclick="hideEditForm()">Cancel</button>
              <button class="btn accent" onclick="updateVehicle()">
                <i class="fas fa-save"></i>Update Vehicle
              </button>
              <button class="btn danger" onclick="deleteVehicle(currentEditingVehicleId)" style="background: var(--danger);">
                <i class="fas fa-trash"></i>Delete Vehicle
              </button>
            </div>
          </div>
        </div>
      </div>
    </section>

  <!-- Vehicle Details Modal -->
  <div id="vehicleDetailsModal" class="modal hidden">
    <div class="modal-content vehicle-details-modal">
      <div class="modal-header">
        <h3 class="modal-title" id="vehicleModalTitle">Vehicle Details</h3>
        <button class="close" onclick="closeVehicleDetails()" title="Close" aria-label="Close">×</button>
      </div>
      <div class="modal-body">
        <div id="vehicleModalContent"></div>
      </div>
    </div>
  </div>

  <!-- Old Vehicle Modal (Keep for compatibility) -->
  <div id="vehicleModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modalName">Vehicle Details</h3>
        <button class="close" onclick="closeModal()" title="Close" aria-label="Close">×</button>
      </div>
          <div class="modal-body">
        <div style="display: grid; gap: 1rem;">
          <div id="modalDetails"></div>
          <button class="btn accent" onclick="openBookingModalFromVehicle(currentModalIndex)" style="width: 100%;">
            <i class="fas fa-calendar-check"></i>Book This Vehicle
          </button>
        </div>
      </div>
    </div>
  </div>

  <div id="messageModal" class="modal hidden">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="messageHeader">Chat with Owner</h3>
        <button class="close" onclick="closeMessageModal()" title="Close chat" aria-label="Close chat">×</button>
      </div>
      <div class="modal-body">
        <div id="messageThread" class="message-thread"></div>
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
          <label for="messageInput" class="visually-hidden">Message</label>
          <input type="text" id="messageInput" class="form-input" placeholder="Type your message..." style="flex: 1;" aria-label="Type your message">
          <button class="btn primary" id="sendMessageBtn" aria-label="Send message" title="Send message">
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
            <span class="visually-hidden">Send message</span>
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Booking Modal -->
<div id="bookingModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">Book Vehicle</h3>
          <button class="close" onclick="closeBookingModal()" title="Close booking" aria-label="Close booking">×</button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom: 1.5rem;">
                <h4 id="bookingVehicleName">Vehicle Name</h4>
                <p id="bookingDailyRate" style="color: var(--accent); font-weight: 600;">₱0/day</p>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="startDate" class="form-input" onchange="calculateBookingAmount()" required>
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" id="endDate" class="form-input" onchange="calculateBookingAmount()" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Total Amount</label>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span id="calculatedAmount" style="font-size: 1.5rem; font-weight: 700; color: var(--accent);">₱0</span>
                    <input type="hidden" id="totalAmount">
                </div>
                <small style="color: #94a3b8;">Amount calculated based on selected dates</small>
            </div>
            
            <button class="btn accent" onclick="submitBooking()" style="width: 100%; margin-top: 1rem;">
                <i class="fas fa-calendar-check"></i>Confirm Booking
            </button>
        </div>
    </div>
</div>

  <script src="script.js?v=20251208"></script>
  <!-- Fallback loader: if CDN is blocked by browser tracking prevention, try to load a local copy -->
  <script>
    (function(){
      // After a short delay, verify whether the Font Awesome stylesheet is present.
      setTimeout(function(){
        try {
          var found = false;
          for(var i=0;i<document.styleSheets.length;i++){
            var ss = document.styleSheets[i];
            if(ss && ss.href && ss.href.indexOf('cdnjs.cloudflare.com/ajax/libs/font-awesome') !== -1){ found = true; break; }
          }
          if(!found){
            console.warn('Font Awesome CDN stylesheet not detected. Attempting to load local fallback `vendor/fontawesome/css/all.min.css`.');
            var l = document.createElement('link');
            l.rel = 'stylesheet';
            l.href = 'vendor/fontawesome/css/all.min.css';
            l.onload = function(){ console.log('Local Font Awesome fallback loaded.'); };
            l.onerror = function(){ console.warn('Local Font Awesome fallback failed to load. Consider downloading Font Awesome files into `vendor/fontawesome/`.'); };
            document.head.appendChild(l);
          } else {
            console.log('Font Awesome CDN stylesheet loaded.');
          }
        } catch (e) {
          console.warn('Error checking Font Awesome stylesheet:', e);
        }
      }, 800);
    })();
  </script>
  <!-- editProfileModal removed -->
  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal hidden">
    <div class="modal-content confirm-modal">
      <div class="modal-header">
        <h3 id="confirmTitle">Confirm action</h3>
        <button class="close" onclick="closeConfirm(false)" title="Close" aria-label="Close">×</button>
      </div>
      <div class="modal-body" id="confirmBody">
        <!-- message inserted here -->
      </div>
      <div class="modal-footer" style="display:flex; gap:0.5rem; justify-content:flex-end;">
        <button class="btn secondary" onclick="closeConfirm(false)">Cancel</button>
        <button class="btn accent" id="confirmOkBtn" onclick="closeConfirm(true)">OK</button>
      </div>
    </div>
  </div>

  <!-- Profile Modal -->
  <div id="profileModal" class="modal hidden">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Your Profile</h3>
        <button class="close" onclick="closeProfileModal()" title="Close profile" aria-label="Close profile">×</button>
      </div>
      <div class="modal-body">
        <div id="profileContent" style="display:grid; gap:0.5rem;"></div>
      </div>
      <div class="modal-footer" style="display:flex; gap:0.5rem; justify-content:flex-end; margin-top:1rem;">
        <button class="btn secondary" onclick="closeProfileModal()">Close</button>
              <button class="btn danger" onclick="handleLogout()">Logout</button>
      </div>
    </div>
  </div>

  <!-- OTP Verification Modal -->
  <div id="otpModal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="otpModalTitle">
    <div class="modal-content" style="max-width: 450px; background: white;">
      <div style="text-align: center; padding: 2.5rem 2rem;">
        <!-- Icon -->
        <div style="margin-bottom: 1.5rem;">
          <i class="fas fa-envelope" style="font-size: 3rem; color: #667eea; opacity: 0.8;"></i>
        </div>
        
        <!-- Title -->
        <h2 style="color: #333; margin: 0 0 0.5rem 0; font-size: 1.5rem;">OTP Verification</h2>
        
        <!-- Subtitle with email -->
        <p style="color: #666; font-size: 0.95rem; margin: 0 0 1.5rem 0;" id="otpEmailContainer">
          One Time Password (OTP) has been sent via Email to<br>
          <strong style="color: #333;" id="otpEmailDisplay">your email</strong>
        </p>
        
        <!-- Instructions -->
        <p style="color: #888; font-size: 0.9rem; margin: 0 0 2rem 0;">Enter the OTP below to verify it.</p>
        
        <!-- OTP Input Boxes -->
        <form id="otpForm" autocomplete="off" onsubmit="return false;">
          <div id="otpBoxesContainer" style="display: flex; justify-content: center; gap: 0.75rem; margin-bottom: 1.5rem;" role="group" aria-label="6-digit verification code">
            <input id="otpInput" name="otpInput" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="______" aria-label="6-digit verification code" style="width:100%; max-width:360px; height:56px; font-size:28px; text-align:center; letter-spacing:14px; border: 2px solid #e2e8f0; border-radius: 8px; font-weight: 700; color: #111; font-family: 'Courier New', monospace; background: #fff; box-shadow: 0 2px 6px rgba(15,23,42,0.04);">
          </div>
        </form>
        
        <!-- Resend Timer -->
        <p id="resendCountdown" style="color: #999; font-size: 0.9rem; margin: 0 0 1.5rem 0; display: none;">Resend OTP in <span id="resendTime">00:00</span></p>
        
        <!-- Verify Button -->
        <button id="verifyOTPBtn" class="btn primary" onclick="verifyOTP()" type="button" style="width: 100%; padding: 0.75rem 1.5rem; font-weight: 600; margin-bottom: 1rem;">
          Verify OTP
        </button>
        
        <!-- Resend Link -->
        <button class="btn secondary" id="resendOTPBtn" onclick="resendOTP()" type="button" style="width: 100%; padding: 0.75rem 1.5rem; font-weight: 600; display: none;">
          Resend OTP
        </button>
        
        <!-- Close Button -->
        <button id="cancelOTPBtn" onclick="closeOTPModal()" type="button" style="background: none; border: none; color: #999; cursor: pointer; margin-top: 1rem; font-size: 0.9rem; text-decoration: underline;">
          Cancel
        </button>
      </div>
    </div>
  </div>

  <style>
    .otp-box:focus {
      border-color: #667eea !important;
      outline: none;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .otp-box:hover {
      border-color: #cbd5e0;
    }
    
    /* Single OTP input focus */
    #otpInput:focus {
      border-color: #667eea !important;
      outline: none;
      box-shadow: 0 0 0 6px rgba(102, 126, 234, 0.06);
    }
  </style>
</body>
</html>