<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Ridesphere • Car Rental Platform</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <input type="text" id="firstName" class="form-input" placeholder="First Name *" required>
      </div>
      <div class="form-group">
        <input type="text" id="middleName" class="form-input" placeholder="Middle Name">
      </div>
      <div class="form-group">
        <input type="text" id="lastName" class="form-input" placeholder="Last Name *" required>
      </div>
    </div>
    
    <div class="form-group">
      <input type="tel" id="phoneNumber" class="form-input" placeholder="Phone Number">
    </div>
    
    <div class="form-group">
      <input type="text" id="address" class="form-input" placeholder="Complete Address">
    </div>
    
    <div class="form-group">
      <input type="email" id="newEmail" class="form-input" placeholder="Email address *" required>
    </div>

    <div class="form-group">
      <input type="password" id="newPassword" class="form-input" placeholder="Create Password *" required>
    </div>
    
    <div class="form-group">
      <select id="newRole" class="form-select" required>
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
        <input type="email" id="email" class="form-input" placeholder="Email" required>
      </div>
      
      <div class="form-group">
        <input type="password" id="password" class="form-input" placeholder="Password" required>
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
        <button class="btn secondary" onclick="openProfileModal()" id="profileBtn" aria-label="Open profile">
          <i class="fas fa-user-circle"></i>
          <span id="profileName" style="margin-left:0.5rem; display:inline-block;">Profile</span>
        </button>
      </div>
    </header>

    <section id="renterDashboard" class="hidden">
      <div class="renter-dashboard">
        <!-- Renter profile removed per request -->

        <!-- Main Renter Interface -->
        <div class="renter-main-interface">
          <div class="renter-quick-actions">
            <div class="renter-action-card" onclick="showVehiclesView()">
              <div class="renter-action-icon">🚗</div>
              <h3>View Vehicles</h3>
              <p>Browse and book available vehicles from our network of trusted owners</p>
            </div>
            <div class="renter-action-card" onclick="showBookingsView()">
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
            <div class="action-card" onclick="showVehicleForm()">
              <div class="icon">🚗</div>
              <h3>Add New Vehicle</h3>
              <p>List a new vehicle for rent</p>
            </div>
            <div class="action-card" onclick="showAllVehicles()">
              <div class="icon">📋</div>
              <h3>All Vehicles</h3>
              <p>View all your vehicles</p>
            </div>
            <div class="action-card" onclick="showOwnerBookings()">
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
                  <input type="file" id="vehicleImageInput" accept="image/*" onchange="previewImage(this)" />
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
                  <input type="file" id="editVehicleImageInput" accept="image/*" onchange="previewEditImage(this)" />
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
        <button class="close" onclick="closeVehicleDetails()">×</button>
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
        <button class="close" onclick="closeModal()">×</button>
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
        <button class="close" onclick="closeMessageModal()">×</button>
      </div>
      <div class="modal-body">
        <div id="messageThread" class="message-thread"></div>
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
          <input type="text" id="messageInput" class="form-input" placeholder="Type your message..." style="flex: 1;">
          <button class="btn primary" id="sendMessageBtn">
            <i class="fas fa-paper-plane"></i>
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
            <button class="close" onclick="closeBookingModal()">×</button>
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

  <script src="script.js"></script>
  <!-- editProfileModal removed -->
  <!-- Confirmation Modal -->
  <div id="confirmModal" class="modal hidden">
    <div class="modal-content confirm-modal">
      <div class="modal-header">
        <h3 id="confirmTitle">Confirm action</h3>
        <button class="close" onclick="closeConfirm(false)">×</button>
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
        <button class="close" onclick="closeProfileModal()">×</button>
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
</body>
</html>