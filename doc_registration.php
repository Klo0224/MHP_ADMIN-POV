<?php
  session_start();

  // Database connection
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "_Mindsoothe";

  $conn = new mysqli($servername, $username, $password, $dbname);
  if ($conn->connect_error) {
      die("Connection failed: " . $conn->connect_error);
  }
  $message = '';

  // Registration process
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signUp'])) {
      $fname = $_POST['fname'];
      $lname = $_POST['lname'];
      $email = $_POST['email'];
      $specialization = $_POST['specialization'];
      $experience = $_POST['experience'];
      if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = "Passwords do not match.";
        $upload_ok = false;
    } else {
        // Proceed with password hashing only if passwords match
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }
  
      
      // Check if email already exists
      $check_email = "SELECT * FROM doctors WHERE email = ?";
      $stmt = $conn->prepare($check_email);
      $stmt->bind_param("s", $email);
      $stmt->execute();
      $result = $stmt->get_result();
      
      if ($result->num_rows > 0) {
          $message = "This email is already registered. Please use a different email.";
      } else {
          // Handle license uploads
          $upload_dir = "uploads/";
          $license_front = $license_back = "";
          $upload_ok = true;
          
          // Function to handle file upload
          function handleUpload($file, $side) {
              global $upload_dir, $upload_ok;
              $target_file = $upload_dir . basename($file["name"]);
              $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
              
              // Check if image file is an actual image
              $check = getimagesize($file["tmp_name"]);
              if($check === false) {
                  global $message;
                  $message .= "File is not an image. ";
                  $upload_ok = false;
              }
              
              // Check file size (limit to 5MB)
              if ($file["size"] > 5000000) {
                  global $message;
                  $message .= "Sorry, your file is too large. ";
                  $upload_ok = false;
              }
              
              // Allow certain file formats
              if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                  global $message;
                  $message .= "Sorry, only JPG, JPEG, PNG & GIF files are allowed. ";
                  $upload_ok = false;
              }
              
              // Generate a unique filename
              $new_filename = uniqid() . '_' . $side . '.' . $imageFileType;
              $target_file = $upload_dir . $new_filename;
              
              // Upload file
              if ($upload_ok) {
                  if (move_uploaded_file($file["tmp_name"], $target_file)) {
                      return $target_file;
                  } else {
                      global $message;
                      $message .= "Sorry, there was an error uploading your file. ";
                      return false;
                  }
              }
              return false;
          }
          
          // Handle front license upload
          if(isset($_FILES["license_front"]) && $_FILES["license_front"]["error"] == 0) {
              $license_front = handleUpload($_FILES["license_front"], "front");
          } else {
              $message .= "Front license file is required. ";
              $upload_ok = false;
          }
          
          // Handle back license upload
          if(isset($_FILES["license_back"]) && $_FILES["license_back"]["error"] == 0) {
              $license_back = handleUpload($_FILES["license_back"], "back");
          } else {
              $message .= "Back license file is required. ";
              $upload_ok = false;
          }
          
          // If everything is ok, insert into database
          if ($upload_ok) {
              $sql = "INSERT INTO doctors (fname, lname, email, specialization, experience, license_front, license_back, password, status) VALUES (?, ?, ?, ?, ?, ?, ?,?, 'pending')";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("ssssssss", $fname, $lname, $email, $specialization, $experience, $license_front, $license_back, $password);
              
              if ($stmt->execute()) {
                  $message = "Registration successful. Please wait for admin approval.";
              } else {
                  $message = "Error: " . $stmt->error;
              }
          }
      }
  }



  // Login process
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signIn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM doctors WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
      $doctor = $result->fetch_assoc();
      if (password_verify($password, $doctor['password'])) {
          if ($doctor['status'] === 'approved') {
              $_SESSION['doctor_id'] = $doctor['id'];
              $_SESSION['doctor_first_name'] = $doctor['fname'];
              $_SESSION['doctor_last_name'] = $doctor['lname'];
              header("Location: mhpdashboard.html");
              exit();
          } elseif ($doctor['status'] === 'declined') {
              $message = "You have been rejected by the admin. Contact us if you have queries at admin@gmail.com";
          } else {
              $message = "Your account is pending approval. Please wait for admin confirmation.";
          }
      } else {
          $message = "Invalid email or password.";
      }
  } else {
      $message = "Invalid email or password.";
  }
}
  // Logout process
  if (isset($_GET['logout'])) {
      session_destroy();
      header("Location: " . $_SERVER['PHP_SELF']);
      exit();
  }

  ?>

  <!DOCTYPE html>
  <html lang="en">
  <head>
    <meta charset="UTF-8">
    <title>Doctor Login & Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    <style>
      @import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

      * {
        box-sizing: border-box;
      }

      body {
        background: #f6f5f7;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        font-family: 'Montserrat', sans-serif;
        height: 100vh;
        margin: -20px 0 50px;
      }

      h1 {
        font-weight: bold;
        margin: 0;
      }

      h2 {
        text-align: center;
      }

      p {
        font-size: 14px;
        font-weight: 100;
        line-height: 20px;
        letter-spacing: 0.5px;
        margin: 20px 0 30px;
      }

      span {
        font-size: 12px;
      }

      a {
        color: #333;
        font-size: 14px;
        text-decoration: none;
        margin: 15px 0;
      }

      button {
        border-radius: 20px;
        border: 1px solid #1cabe3;
        background-color: #1cabe3;
        color: #FFFFFF;
        font-size: 12px;
        font-weight: bold;
        padding: 12px 45px;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: transform 80ms ease-in;
      }

      button:active {
        transform: scale(0.95);
      }

      button:focus {
        outline: none;
      }

      button.ghost {
        background-color: transparent;
        border-color: #FFFFFF;
      }

      form {
        background-color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 50px;
        height: 100%;
        text-align: center;
      }

      input {
        background-color: #eee;
        border: none;
        padding: 12px 15px;
        margin: 5px 0;
        width: 100%;
      }
      select {
        background-color: #eee;
        border: none;
        padding: 12px 15px;
        margin: 5px 0;
        width: 100%;
      }
      

      .container {
        background-color: #fff;
        border-radius: 10px;
          box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
            0 10px 10px rgba(0,0,0,0.22);
        position: relative;
        overflow: hidden;
        width: 768px;
        max-width: 100%;
        min-height: 550px;
      }

      .form-container {
        position: absolute;
        top: 0;
        height: 100%;
        transition: all 0.6s ease-in-out;
      }

      .sign-in-container {
        left: 0;
        width: 50%;
        z-index: 2;
      }

      .container.right-panel-active .sign-in-container {
        transform: translateX(100%);
      }

      .sign-up-container {
        left: 0;
        width: 50%;
        opacity: 0;
        z-index: 1;
      }

      .container.right-panel-active .sign-up-container {
        transform: translateX(100%);
        opacity: 1;
        z-index: 5;
        animation: show 0.6s;
      }

      @keyframes show {
        0%, 49.99% {
          opacity: 0;
          z-index: 1;
        }
        
        50%, 100% {
          opacity: 1;
          z-index: 5;
        }
      }

      .overlay-container {
        position: absolute;
        top: 0;
        left: 50%;
        width: 50%;
        height: 100%;
        overflow: hidden;
        transition: transform 0.6s ease-in-out;
        z-index: 100;
      }

      .container.right-panel-active .overlay-container{
        transform: translateX(-100%);
      }

      .overlay {
        background: #FF416C;
        background: linear-gradient(180deg, rgb(200, 185, 250) 14%, rgb(159, 194.5, 244.5) 25%, rgb(28, 171, 227) 64%);
        background-repeat: no-repeat;
        background-size: cover;
        background-position: 0 0;
        color: #FFFFFF;
        position: relative;
        left: -100%;
        height: 100%;
        width: 200%;
          transform: translateX(0);
        transition: transform 0.6s ease-in-out;
      }

      .container.right-panel-active .overlay {
          transform: translateX(50%);
      }

      .overlay-panel {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        padding: 0 40px;
        text-align: center;
        top: 0;
        height: 100%;
        width: 50%;
        transform: translateX(0);
        transition: transform 0.6s ease-in-out;
      }

      .overlay-left {
        transform: translateX(-20%);
      }

      .container.right-panel-active .overlay-left {
        transform: translateX(0);
      }

      .overlay-right {
        right: 0;
        transform: translateX(0);
      }

      .container.right-panel-active .overlay-right {
        transform: translateX(20%);
      }

      .social-container {
        margin: 20px 0;
      }

      .social-container a {
        border: 1px solid #DDDDDD;
        border-radius: 50%;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        height: 40px;
        width: 40px;
      }
      .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
      }

      .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        width: 80%;
        max-width: 500px;
        position: relative;
      }

      .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
      }

      .close:hover,
      .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
      }

      .license-upload-container {
        margin: 10px 0;
        width: 100%;
      }

      .license-upload-input {
          background-color: #eee;
          border: none;
          padding: 12px 15px;
          margin: 8px 0;
          width: 100%;
          cursor: pointer;
          text-align: center;
      }

      .license-upload-btn {
          display: inline-block;
          padding: 10px 20px;
          margin: 10px 0;
          background-color: #1cabe3;
          color: white;
          border-radius: 5px;
          cursor: pointer;
          width: 100%;
          text-align: center;
      }

      .license-upload-btn:hover {
          background-color: #1597c9;
      }

      .modal-title {
          margin-bottom: 20px;
          color: #333;
      }

      .modal-description {
          color: #666;
          margin-bottom: 20px;
          line-height: 1.5;
      }
      .modal-actions {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.modal-actions button {
    padding: 10px 20px;
    background-color: #1cabe3;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.modal-actions button:hover {
    background-color: #1597c9;
}
    </style>
  </head>
  <body>
    <div class="container" id="container">
      <div class="form-container sign-up-container">
        <form method="post" enctype="multipart/form-data">
          <h1>Create Account</h1>
          <div class="social-container"></div>
          <input type="fname" name="fname" placeholder="First Name" required>
          <input type="lname" name="lname" placeholder="Last Name" required/>
          <input type="email" name="email" placeholder="Email" required/>
          <select name="specialization" required>
          <option value="">Select Specialization</option>
          <option value="Psychiatrist">Psychiatrist</option>
          <option value="Psychologist">Psychologist</option>
          <option value="Clinical Psychologist">General practitioner</option>
          <option value="Counseling Psychologist">Counselor</option>
          <option value="Neuropsychologist">psychotherapy</option>
          </select>
          <input type="number" name="experience" placeholder="Years of Experience"  min="0" required/>
          <div class="license-upload-container">
           <input type="text" 
           id="license_upload_trigger" 
           class="license-upload-input" 
           readonly 
           value="Upload License" 
           onclick="openLicenseModal()" />
    
          <!-- Hidden file inputs -->
          <input type="file" 
           name="license_front" 
           id="license_front" 
           style="display: none;" 
           accept=".jpg,.jpeg,.png,.gif" 
           required/>
          <input type="file" 
           name="license_back" 
           id="license_back" 
           style="display: none;" 
           accept=".jpg,.jpeg,.png,.gif" 
           required/>
          </div>
          <input type="password" name="password" placeholder="Password" required />
          <input type="password" name="confirm_password" placeholder="Confirm Password" required />
          <button type="submit" name="signUp">Sign Up</button>
        </form>
      </div>
      <div class="form-container sign-in-container">
        <form method="post">
          <h1>Sign in</h1>
          <div class="social-container">
            <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
            <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
          </div>
          <span>or use your account</span>
          <input type="email" name="email" placeholder="Email" required/>
          <input type="password" name="password" placeholder="Password" required/>
          <a href="#">Forgot your password?</a>
          <button type="submit" name="signIn">Sign In</button>
        </form>
      </div>
      <div class="overlay-container">
        <div class="overlay">
          <div class="overlay-panel overlay-left">
            <h1>Welcome Back!</h1>
            <p>To keep connected with us please login with your personal info</p>
            <button class="ghost" id="signIn">Sign In</button>
          </div>
          <div class="overlay-panel overlay-right">
            <h1>Hello, Doctor!</h1>
            <p>Enter your personal details and start your journey with us</p>
            <button class="ghost" id="signUp">Sign Up</button>
          </div>
        </div>
      </div>
    </div>
    <div id="licenseModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeLicenseModal()">&times;</span>
        <h2 class="modal-title">Medical License Upload</h2>
        <p class="modal-description">
            To ensure the highest quality of care and maintain trust in our platform, 
            we require all professionals to verify their credentials. Please 
            provide clear, readable images of both the front and back of your current 
            license. This information will be securely stored and reviewed by 
            our verification team.
        </p>
        <div class="license-upload-container">
            <label class="license-upload-btn" for="modal_license_front">
                Upload Front of License
                <div id="front_file_name">No file chosen</div>
            </label>
            <input type="file" 
                   id="modal_license_front" 
                   accept=".jpg,.jpeg,.png,.gif" 
                   style="display: none;" 
                   onchange="handleLicenseUpload(this, 'front')"/>
        </div>
        <div class="license-upload-container">
            <label class="license-upload-btn" for="modal_license_back">
                Upload Back of License
                <div id="back_file_name">No file chosen</div>
            </label>
            <input type="file" 
                   id="modal_license_back" 
                   accept=".jpg,.jpeg,.png,.gif" 
                   style="display: none;" 
                   onchange="handleLicenseUpload(this, 'back')"/>
        </div>
        <div class="modal-actions">
            <button onclick="saveLicenseUpload()">Save</button>
        </div>
    </div>
</div>
    
    <?php if ($message): ?>
      <p><?php echo $message; ?></p>
    <?php endif; ?>

    <script>
      const signUpButton = document.getElementById('signUp');
      const signInButton = document.getElementById('signIn');
      const container = document.getElementById('container');

      signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
      });

      signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
      });

      function saveLicenseUpload() {
    const frontFile = document.getElementById('license_front');
    const backFile = document.getElementById('license_back');
    const modalFrontFile = document.getElementById('modal_license_front');
    const modalBackFile = document.getElementById('modal_license_back');

    // Transfer files from modal inputs to form inputs
    if (modalFrontFile.files.length) {
        frontFile.files = modalFrontFile.files;
    }
    if (modalBackFile.files.length) {
        backFile.files = modalBackFile.files;
    }

    // Update trigger input text
    updateTriggerText();

    // Close the modal
    closeLicenseModal();
}

      function openLicenseModal() {
    document.getElementById('licenseModal').style.display = 'block';
}

function closeLicenseModal() {
    document.getElementById('licenseModal').style.display = 'none';
}

function handleLicenseUpload(input, side) {
    if (input.files && input.files[0]) {
        // Update the display name in the modal
        const fileName = input.files[0].name;
        document.getElementById(side + '_file_name').textContent = fileName;
        
        // Update the hidden form input
        const formInput = document.getElementById('license_' + side);
        formInput.files = input.files;
        
        // Update the trigger input text
        updateTriggerText();
    }
}

function updateTriggerText() {
    const frontFile = document.getElementById('license_front').files;
    const backFile = document.getElementById('license_back').files;
    const triggerInput = document.getElementById('license_upload_trigger');
    
    if (frontFile.length && backFile.length) {
        triggerInput.value = 'Both sides of license uploaded';
    } else if (frontFile.length || backFile.length) {
        triggerInput.value = 'One side of license uploaded';
    } else {
        triggerInput.value = 'Upload License';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('licenseModal');
    if (event.target == modal) {
        closeLicenseModal();
    }
}
    </script>
  </body>
  </html>