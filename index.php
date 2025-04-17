<?php
// Handle contact form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact-name'])) {
    include 'connection.php';
    
    $name = mysqli_real_escape_string($conn, $_POST['contact-name']);
    $email = mysqli_real_escape_string($conn, $_POST['contact-email']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['contact-message']);
    
    $sql = "INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Message sent successfully!');</script>";
    } else {
        echo "<script>alert('Error occurred while sending message.');</script>";
    }
}
?>
<?php
// Add password validation in index.php before processing form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if this is the registration form
    if (isset($_POST["form_type"]) && $_POST["form_type"] === "register") {
        $password = $_POST["password"];

        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,}$/", $password)) {
            echo "<script>alert('Password must contain at least one lowercase letter, one uppercase letter, one special character, and be at least 8 characters long.'); window.history.back();</script>";
            exit(); // Stop further execution
        }
    }

    // Proceed with form submission (registration)
}
?>

<?php
session_start();
include 'connection.php';

// Handle user login
if(isset($_POST['login_submit'])) {
    $email = mysqli_real_escape_string($conn, $_POST['login_email']);
    $password = $_POST['login_password'];

    // First check if email exists
    $sql = "SELECT * FROM user WHERE email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        // Check password
        if(password_verify($password, $user['password'])) {
            // Check status
            if($user['status'] == 1) {
                // All good - login successful
                $_SESSION['email'] = $email;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                echo "<script>alert('Login successful!'); window.location.href='user/user.php';</script>";
            } else {
                echo "<script>alert('Your account is pending approval from secretary. Please wait for approval.');</script>";
            }
        } else {
            echo "<script>alert('Invalid password! Please try again.');</script>";
        }
    } else {
        echo "<script>alert('Email not registered! Please sign up first.');</script>";
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // First verify if email exists and account is approved
    $sql = "SELECT * FROM user WHERE email = ? AND status = 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        // Verify old password
        if(password_verify($old_password, $user['password'])) {
            // Verify new password matches confirmation
            if($new_password === $confirm_password) {
                // Hash new password and update
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE user SET password = ? WHERE email = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $email);
                
                if(mysqli_stmt_execute($update_stmt)) {
                    echo "<script>alert('Password changed successfully!');</script>";
                } else {
                    echo "<script>alert('Error changing password!');</script>";
                }
            } else {
                echo "<script>alert('New password and confirmation do not match!');</script>";
            }
        } else {
            echo "<script>alert('Current password is incorrect!');</script>";
        }
    } else {
        echo "<script>alert('Email not found or account not approved!');</script>";
    }
}

// Handle user signup
if(isset($_POST['signup_submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $flatno = $_POST['flatno'];
    $phone = $_POST['phone'];
    $familymembers = $_POST['familymembers'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if user already exists
    $check_user = "SELECT * FROM user WHERE email = '$email' OR phone = '$phone'";
    $result = mysqli_query($conn, $check_user);
    
    if(mysqli_num_rows($result) > 0) {
        echo "<script>alert('User already exists with this email or phone number!');</script>";
    } else {
        // Insert new user
        $sql = "INSERT INTO user (name, email, flatno, phone, familymembers, password) 
                VALUES ('$name', '$email', '$flatno', '$phone', '$familymembers', '$password')";
        
        if(mysqli_query($conn, $sql)) {
            // Send welcome email
            require 'PHPMailer/PHPMailer.php';
            require 'PHPMailer/SMTP.php';
            require 'PHPMailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'Your_email@gmail.com';  // your Email address
                $mail->Password = 'Your_app_password';   // 16 digit app password
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('your-email@gmail.com', 'Society Management');  // Your email address
                $mail->addAddress($email, $name);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to Our Society - Registration Successful';
                
                // Email body
                $mailContent = "
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; }
                            .container { padding: 20px; }
                            .header { background-color: #1aa090; color: white; padding: 20px; text-align: center; }
                            .content { padding: 20px; }
                            .footer { background-color: #f5f5f5; padding: 10px; text-align: center; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h2>Welcome to Our Society</h2>
                            </div>
                            <div class='content'>
                                <p>Dear $name,</p>
                                <p>Thank you for registering with our society management system. Your registration details are:</p>
                                <ul>
                                    <li>Flat Number: $flatno</li>
                                    <li>Phone: $phone</li>
                                    <li>Family Members: $familymembers</li>
                                </ul>
                                <p>Your registration is pending approval from the admin. You will be notified once your account is approved.</p>
                                <p>Please note: Do not share your login credentials with anyone.</p>
                            </div>
                            <div class='footer'>
                                <p>This is an automated email. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                ";
                
                $mail->Body = $mailContent;
                $mail->AltBody = "Welcome to Our Society!\n\nDear $name,\n\nThank you for registering. Your registration is pending approval.";

                $mail->send();
                
                echo "<script>
                    alert('Registration successful! Please check your email for confirmation.');
                    window.location.href = 'index.php';
                    </script>";
            } catch (Exception $e) {
                echo "<script>
                    alert('Registration successful but email could not be sent. Error: {$mail->ErrorInfo}');
                    window.location.href = 'index.php';
                    </script>";
            }
        } else {
            echo "<script>
                alert('Error in registration. Please try again.');
                window.location.href = 'index.php';
                </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>It's my society</title>

    <link rel="icon" href="img/favicon.png" type="png" />

    <!-- Link Bootstrap's CSS -->
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    />

    <link rel="stylesheet" href="style.css" />

    <!-- Animation  -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
  </head>

  <body data-bs-spy="scroll" data-bs-target="#navbar-example2" tabindex="0">
    <!-- nav bar start  -->
    <header id="nav" class="site-header position-fixed text-white bg-dark">
      <nav id="navbar-example2" class="navbar navbar-expand-lg py-2">
        <div class="container">
          <a class="navbar-brand neon glow-effect" href="./index.html"
            ><img src="img/new2.png" class="logo" alt="image" data-aos="zoom-out"/></a>

          <button
            class="navbar-toggler text-white"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasNavbar2"
            aria-controls="offcanvasNavbar2"
            aria-label="Toggle navigation"
          >
            <i
              class="bi bi-list"
              name="menu-outline"
              style="font-size: 30px"
            ></i>
          </button>

          <div
            class="offcanvas offcanvas-end"
            tabindex="-1"
            id="offcanvasNavbar2"
            aria-labelledby="offcanvasNavbar2Label"
          >
            <div class="offcanvas-header">
              <h5 class="offcanvas-title" id="offcanvasNavbar2Label">Menu</h5>
              <button
                type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="offcanvas"
                aria-label="Close"
              ></button>
            </div>
            <div class="offcanvas-body">
              <ul
                class="navbar-nav align-items-center justify-content-end align-items-center flex-grow-1"
              >
                <li class="nav-item">
                  <a class="nav-link active me-md-4 neon glow-effect" href="#hero">Home</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-md-4 rules-glow" href="#rule"
                    >Rules & Regulation</a
                  >
                </li>
                <li class="nav-item">
                  <a class="nav-link me-md-4" href="#facilities">Facilities</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-md-4" href="#gallery-img">Gallery</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link me-md-4" href="#contact">Contact us</a>
                </li>
                <li class="nav-item">
                <a
                    class="btn-medium btn btn-primary"
                    href="#"
                    data-bs-toggle="modal"
                    data-bs-target="#exampleModal"
                    onclick="openSignupTab()"
                    style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);"
                    >Register</a>
                </li>

                <!-- Modal -->
                <div
                  class="modal fade"
                  id="exampleModal"
                  tabindex="-1"
                  aria-labelledby="exampleModalLabel"
                  aria-hidden="true"
                >
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <button
                          type="button"
                          class="btn-close"
                          data-bs-dismiss="modal"
                          aria-label="Close"
                        ></button>
                      </div>
                      <div class="modal-body">
                        <div class="tabs-listing mt-4">
                          <nav>
                            <div
                              class="nav nav-tabs d-flex justify-content-center border-0"
                              id="nav-tab"
                              role="tablist"
                            >
                              <button
                                class="btn btn-outline-primary text-uppercase me-3 active"
                                id="nav-sign-in-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#nav-sign-in"
                                type="button"
                                role="tab"
                                aria-controls="nav-sign-in"
                                aria-selected="true"
                              >
                                Log In
                              </button>
                              <button
                                class="btn btn-outline-primary text-uppercase"
                                id="nav-register-tab"
                                data-bs-toggle="tab"
                                data-bs-target="#nav-register"
                                type="button"
                                role="tab"
                                aria-controls="nav-register"
                                aria-selected="false"
                              >
                                Sign Up
                              </button>
                            </div>
                          </nav>
                          <div class="tab-content" id="nav-tabContent">
                            <div
                              class="tab-pane fade active show"
                              id="nav-sign-in"
                              role="tabpanel"
                              aria-labelledby="nav-sign-in-tab"
                            >
                              <form id="loginForm" class="form-group flex-wrap p-3" method="POST">
                                <div class="form-input col-lg-12 my-4">
                                  <label for="login_email" class="form-label fs-6 text-uppercase fw-bold text-black">Email</label>
                                  <input type="email" name="login_email" id="login_email" class="form-control ps-3" 
                                         placeholder="Enter your email" required autocomplete="off">
                                  <div class="invalid-feedback">
                                    Please enter a valid email
                                  </div>
                                </div>
                                <div class="form-input col-lg-12">
                                  <label for="login_password" class="form-label fs-6 text-uppercase fw-bold text-black">Password</label>
                                  <div class="input-group">
                                    <input type="password" name="login_password" id="login_password" class="form-control ps-3" placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('login_password')">
                                      <i class="bi bi-eye"></i>
                                    </button>
                                  </div>
                                  <div class="invalid-feedback">
                                    Please enter your password
                                  </div>
                                </div>
                                <div class="col-lg-12 mt-4">
                                  <button type="submit" name="login_submit" class="btn btn-primary w-100 btn-lg text-uppercase btn-rounded-none fs-6" style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);">Login</button>
                                </div>
                                <div class="col-lg-12 mt-3 text-center">
                                  <a href="changepass.php" class="text-primary">Forgot Password</a>
                                </div>
                              </form>
                            </div>
                            <div
                              class="tab-pane fade"
                              id="nav-register"
                              role="tabpanel"
                              aria-labelledby="nav-register-tab"
                            >
                              <form id="signupForm" class="form-group flex-wrap p-3" method="POST" novalidate>
                                <div class="form-input col-lg-12 my-4">
                                  <label for="name" class="form-label fs-6 text-uppercase fw-bold text-black">Name</label>
                                  <input type="text" name="name" id="name" class="form-control ps-3" maxlength="30" 
                                         placeholder="Enter your name" required autocomplete="off">
                                  <div class="invalid-feedback">
                                    Please enter your name
                                  </div>
                                </div>
                                
                                <div class="form-input col-lg-12 my-4">
                                  <label for="email" class="form-label fs-6 text-uppercase fw-bold text-black">Email</label>
                                  <input type="email" name="email" id="email" class="form-control ps-3" maxlength="50" 
                                         pattern="[a-zA-Z0-9._%+-]+@gmail\.com$" oninput="validateEmail(this)"
                                         placeholder="Enter your Gmail address" required autocomplete="off">
                                  <div class="invalid-feedback" id="emailFeedback">
                                    Please enter a valid Gmail address
                                  </div>
                                </div>
                                
                                <div class="form-input col-lg-12 my-4">
                                  <label for="flatno" class="form-label fs-6 text-uppercase fw-bold text-black">Flat No</label>
                                  <input type="text" name="flatno" id="flatno" class="form-control ps-3" maxlength="10" 
                                         placeholder="Enter flat number" required autocomplete="off">
                                  <div class="invalid-feedback">
                                    Please enter your flat number
                                  </div>
                                </div>
                                
                                <div class="form-input col-lg-12 my-4">
                                  <label for="phone" class="form-label fs-6 text-uppercase fw-bold text-black">Phone</label>
                                  <input type="tel" name="phone" id="phone" class="form-control ps-3" 
                                         pattern="^[6-9]\d{9}$" oninput="validatePhone(this)"
                                         placeholder="Enter 10-digit Indian mobile number" required autocomplete="off">
                                  <div class="invalid-feedback" id="phoneFeedback">
                                    Please enter a valid Indian mobile number
                                  </div>
                                </div>
                                
                                <div class="form-input col-lg-12 my-4">
                                  <label for="familymembers" class="form-label fs-6 text-uppercase fw-bold text-black">Family Members</label>
                                  <input type="number" name="familymembers" id="familymembers" class="form-control ps-3" 
                                         min="1" max="15" placeholder="Enter number of family members" required autocomplete="off">
                                  <div class="invalid-feedback">
                                    Please enter number of family members (1-15)
                                  </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="signupPassword" class="form-label fs-6 text-uppercase fw-bold text-black">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                        <input type="password" class="form-control" id="signupPassword" name="password" 
                                            placeholder="Enter password" required>
                                            <input type="hidden" name="form_type" value="register">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('signupPassword')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter your password
                                    </div>
                                </div>
                                
                                <div class="col-lg-12 mt-4">
                                  <button type="submit" name="signup_submit" class="btn btn-primary w-100 btn-lg text-uppercase btn-rounded-none fs-6 btn-glow" style="background-color: rgb(26, 160, 144); border-color: rgb(26, 160, 144);">Sign Up</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </ul>
            </div>
          </div>
        </div>
      </nav>
    </header>

    <!-- hero section start  -->
    <section id="hero">
    <div class="home-section">
      <video autoplay muted loop id="myVideo" class="hero-video">
        <source src="img/soc_vid.mp4" type="video/mp4">
        Your browser does not support the video tag.
      </video>
      <div class="video-overlay"></div>
      <div class="hero-content" data-aos="fade-down">
        <h2>Digital Society Management System</h2>
        <p class="typing-effect"></p>
      </div>
    </div>
  </section>

    <!-- Rules and regulation section start  -->
    <section class="rules-section" id="rule" style="background-color: #1a242f;">
      <div class="container">
        <h2 class="text-center mb-4 pt-5 rules-glow" data-aos="fade-up">Rules & Regulations</h2>
        <div class="row align-items-center rules-container">
          <!-- Left Side Image -->
          <div class="col-md-6 rules-image mb-4 mb-md-0">
            <img src="img/ap5.jpg" alt="Rules Image" data-aos="fade-up" />
          </div>

          <!-- Right Side Rules -->
          <div class="col-md-6" data-aos="fade-up">
            <ol class="rules-list">
              <li>
                Members and residents are required to keep their flats/homes and
                nearby premises clean and habitable.
              </li>
              <li>
                The residents should also maintain proper cleanliness etiquette
                while using common areas, parking lot, etc. and not throw litter
                from their balconies and windows.
              </li>
              <li>
                Keeping pets is allowed after submitting the required NOC to the
                society. But if pets like dogs are creating any kind of
                disturbance to other society members then the pets won't be
                allowed.
              </li>
              <li>
                Every member of the society should park their vehicles in their
                respective allotted parking spaces only.
              </li>
              <li>
                After using the community hall for any event or function it
                should be cleaned and no damages should be caused.
              </li>
              <li>Wastage and over usage of water is not allowed.</li>
              <li>Smoking in lobbies, passage is not allowed.</li>
              <li>
                If any irresponsible person is found smoking in the no smoking
                zone, he/she shall be charged with penalty.
              </li>
            </ol>
          </div>
        </div>
      </div>
    </section>


    <!-- Facilities section start -->
  <section class="features-section" id="facilities">
    <div class="container py-5">
      <h2 class="text-center mb-5" data-aos="fade-up">Our Facilities</h2>

      <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div class="col" data-aos="fade-up" data-aos-delay="100">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-shield-check"></i>
              </div>
              <h3 class="card-title h4 mb-3">24/7 Security</h3>
              <p class="card-text">Professional security guards, CCTV monitoring, and visitor management system ensure
                the safety of all residents.</p>
            </div>
          </div>
        </div>

        <div class="col" data-aos="fade-up" data-aos-delay="200">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-water"></i>
              </div>
              <h3 class="card-title h4 mb-3">Water Supply</h3>
              <p class="card-text">24/7 clean water supply with RO purification system. Regular water quality testing
                and maintenance of underground tanks ensures hygienic water for all residents.</p>
            </div>
          </div>
        </div>

        <div class="col" data-aos="fade-up" data-aos-delay="300">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-lightning-charge"></i>
              </div>
              <h3 class="card-title h4 mb-3">Power Backup</h3>
              <p class="card-text">Automatic power backup system with high-capacity generators covering all flats and
                houses,
                common areas, lifts, and water pumps.</p>
            </div>
          </div>
        </div>

        <div class="col" data-aos="fade-up" data-aos-delay="400">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-tree"></i>
              </div>
              <h3 class="card-title h4 mb-3">Garden Area</h3>
              <p class="card-text">Beautifully landscaped gardens with walking tracks, children's play area, and senior
                citizen sitting area. Regular maintenance ensures a clean and green environment.</p>
            </div>
          </div>
        </div>

        <div class="col" data-aos="fade-up" data-aos-delay="500">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-heart-pulse"></i>
              </div>
              <h3 class="card-title h4 mb-3">Fitness Center</h3>
              <p class="card-text">Well-equipped gym with modern equipment and separate yoga room.
                Regular maintenance of equipment and sanitization ensures safe workout environment.</p>
            </div>
          </div>
        </div>

        <div class="col" data-aos="fade-up" data-aos-delay="600">
          <div class="card h-100 feature-card border-0">
            <div class="card-body text-center p-4">
              <div class="icon-wrapper mb-4">
                <i class="bi bi-people"></i>
              </div>
              <h3 class="card-title h4 mb-3">Community Hall</h3>
              <p class="card-text">Spacious air-conditioned hall for society events, festivals and private functions.
                Fully equipped kitchen, audio system and seating arrangements available for community gatherings.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

    <!-- Gallery section start -->
    <section class="gallery-section" id="gallery-img">
      <div class="container">
        <h2 class="gallery-title text-center mb-4 pt-5" data-aos="fade-down">Our Gallery</h2>
        <div id="carouselExampleFade" class="carousel slide carousel-fade mb-5">
          <div class="carousel-inner" data-aos="fade-up">
            <!-- Images -->
            <div class="carousel-item active">
              <img src="img/ap1.jpg" class="d-block w-100" alt="Image 1" />
            </div>
            <div class="carousel-item">
              <img src="img/ap2.jpg" class="d-block w-100" alt="Image 2" />
            </div>
            <div class="carousel-item">
              <img src="img/ap3.jpg" class="d-block w-100" alt="Image 3" />
            </div>
            <div class="carousel-item">
              <img src="img/ap4.jpg" class="d-block w-100" alt="Image 4" />
            </div>
            <div class="carousel-item">
              <img src="img/ap5.jpg" class="d-block w-100" alt="Image 5" />
            </div>
            <div class="carousel-item">
              <img src="img/ap6.jpg" class="d-block w-100" alt="Image 6" />
            </div>
            <div class="carousel-item">
              <img src="img/ap1.jpg" class="d-block w-100" alt="Image 7" />
            </div>
            <div class="carousel-item">
              <img src="img/ap2.jpg" class="d-block w-100" alt="Image 8" />
            </div>
            <div class="carousel-item">
              <img src="img/ap3.jpg" class="d-block w-100" alt="Image 9" />
            </div>
            <div class="carousel-item">
              <img src="img/ap4.jpg" class="d-block w-100" alt="Image 10" />
            </div>
            <div class="carousel-item">
              <img src="img/ap5.jpg" class="d-block w-100" alt="Image 11" />
            </div>
            <div class="carousel-item">
              <img src="img/ap6.jpg" class="d-block w-100" alt="Image 12" />
            </div>
          </div>
          <!-- Controls -->
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleFade" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleFade" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Counter section  -->
    <section class="parallax">
      <div class="container text-center">
        <div class="row">
          <div class="col-12 col-md-4">
            <div class="counter-section">
              <h2 class="counter" data-target="85" data-aos="fade-up">0</h2>
              <p class="text-white" data-aos="fade-up">Flats</p>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="counter-section">
              <h2 class="counter" data-target="45" data-aos="fade-up">0</h2>
              <p class="text-white" data-aos="fade-up">Homes</p>
            </div>
          </div>
          <div class="col-12 col-md-4">
            <div class="counter-section">
              <h2 class="counter" data-target="125" data-aos="fade-up">0</h2>
              <p class="text-white" data-aos="fade-up">Trees</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Contact us section  -->
    <section class="contact-section section-padding pt-lg-5" id="contact">
      <div class="container">
        <div class="row">
          <div class="col-lg-8 col-12 mx-auto p-4">
            <h2 class="text-center mb-4 mt-lg-3 neon glow-effect" data-aos="fade-up">Contact us</h2>

            <nav class="d-flex justify-content-center">
              <div
                class="nav nav-tabs align-items-baseline justify-content-center"
                id="nav-tab"
                role="tablist"
              >
                <button
                  class="nav-link active"
                  id="nav-ContactForm-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#nav-ContactForm"
                  type="button"
                  role="tab"
                  aria-controls="nav-ContactForm"
                  aria-selected="false"
                >
                  <h5 data-aos="fade-up">Contact Form</h5>
                </button>

                <button
                  class="nav-link"
                  id="nav-ContactMap-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#nav-ContactMap"
                  type="button"
                  role="tab"
                  aria-controls="nav-ContactMap"
                  aria-selected="false"
                >
                  <h5 data-aos="fade-up">Google Maps</h5>
                </button>
              </div>
            </nav>

            <div class="tab-content shadow-lg mt-5" id="nav-tabContent">
              <div
                class="tab-pane fade show active"
                id="nav-ContactForm"
                role="tabpanel"
                aria-labelledby="nav-ContactForm-tab"
              >
                <form
                  class="custom-form contact-form mb-5 mb-lg-0"
                  action="<?php echo $_SERVER['PHP_SELF']; ?>#contact"
                  method="post"
                  role="form"
                >
                  <div class="contact-form-body">
                    <div class="row p-lg-5">
                      <div class="col-lg-6 col-md-6 col-12" data-aos="fade-up">
                        <input
                          type="text"
                          name="contact-name"
                          id="contact-name"
                          class="form-control"
                          placeholder="Full name"
                          required
                        />
                      </div>

                      <div class="col-lg-6 col-md-6 col-12" data-aos="fade-up">
                        <input
                          type="email"
                          name="contact-email"
                          id="contact-email"
                          pattern="[^ @]*@[^ @]*"
                          class="form-control"
                          placeholder="Email address"
                          required
                        />
                      </div>
                    </div>

                    <input
                      type="text"
                      name="subject"
                      id="subject"
                      class="form-control"
                      placeholder="Subject"
                      required
                      data-aos="fade-up"
                    />

                    <textarea
                      name="contact-message"
                      rows="3"
                      class="form-control"
                      id="contact-message"
                      placeholder="Message"
                      data-aos="fade-up"
                      required
                    ></textarea>

                    <div class="col-lg-4 col-md-10 col-8 mx-auto">
                      <button type="submit" class="form-control mb-lg-5" data-aos="fade-up">
                        Send message
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <div
                class="tab-pane fade"
                id="nav-ContactMap"
                role="tabpanel"
                aria-labelledby="nav-ContactMap-tab"
                data-aos="fade-up"
              >
                <iframe
                  class="google-map"
                  src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3718.4677770136846!2d72.89234119999999!3d21.2529435!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3be04928ca1a67db%3A0x784e01bc4bb52cae!2sGopin%20Gam!5e0!3m2!1sen!2sin!4v1736069749666!5m2!1sen!2sin"
                  width="100%"
                  height="450"
                  style="border: 0"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"
                ></iframe>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer start  -->
    <section id="footer" class="footer-section">
    <div class="container footer-container">
      <footer class="row row-cols-1 row-cols-sm-2 row-cols-md-5 py-5">
        <div class="col-md-4 mb-4">
          <div class="footer-brand" data-aos="fade-up">
            <h3><img src="img/new2.png" alt="image" class="logo mb-3" /></h3>
            <p class="footer-address"><i class="bi bi-geo-alt-fill me-2"></i>Gopin Gam, Mota varachha, Surat, Gujarat
              364105</p>
            <div class="social-icons">
              <a href="#" class="social-icon"><i class="bi bi-facebook"></i></a>
              <a href="#" class="social-icon"><i class="bi bi-instagram"></i></a>
              <a href="#" class="social-icon"><i class="bi bi-twitter"></i></a>
              <a href="#" class="social-icon"><i class="bi bi-youtube"></i></a>
            </div>
          </div>
        </div>

        <div class="col-md-2 mb-4">
          <div class="footer-links" data-aos="fade-up" data-aos-delay="100">
            <h5 class="footer-heading">Quick Links</h5>
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-house-door me-2"></i>Home
                </a>
              </li>
              <li class="nav-item">
                <a href="#facilities" class="nav-link">
                  <i class="bi bi-stars me-2"></i>Facilities
                </a>
              </li>
              <li class="nav-item">
                <a href="#gallery-img" class="nav-link">
                  <i class="bi bi-images me-2"></i>Gallery
                </a>
              </li>
              <li class="nav-item">
                <a href="#contact" class="nav-link">
                  <i class="bi bi-telephone me-2"></i>Contact
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="col-md-3 mb-4">
          <div class="footer-links" data-aos="fade-up" data-aos-delay="200">
            <h5 class="footer-heading">Member Services</h5>
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-bell-fill me-2"></i>Notice Board
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>Register Complaint
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-currency-rupee me-2"></i>Maintenance
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-file-text me-2"></i>Society Rules
                </a>
              </li>
            </ul>
          </div>
        </div>

        <div class="col-md-3 mb-4">
          <div class="footer-contact" data-aos="fade-up" data-aos-delay="300">
            <h5 class="footer-heading">Contact Us</h5>
            <ul class="nav flex-column">
              <li class="nav-item">
                <a href="mailto:itsmysociety.com" class="nav-link">
                  <i class="bi bi-envelope-fill me-2"></i>Email: itsmysociety@gmail.com
                </a>
              </li>
              <li class="nav-item">
                <a href="tel:12345678900" class="nav-link">
                  <i class="bi bi-telephone-fill me-2"></i>Phone: 9876543210
                </a>
              </li>
              <li class="nav-item">
                <a href="#" class="nav-link">
                  <i class="bi bi-geo-alt-fill me-2"></i>Address: Gopin Gam, Mota varachha, Surat, Gujarat 364105
                </a>
              </li>
            </ul>
          </div>
        </div>
      </footer>
    </div>

    <div class="footer-bottom">
      <div class="container">
        <footer class="d-flex flex-wrap justify-content-between align-items-center py-3">
          <div class="col-md-8">
            <p class="mb-0">&copy; 2025 Itsmysociety, Inc. All rights reserved.</p>
          </div>
          <div class="col-md-4">
            <p class="mb-0">
              Design and Developed by
              <a href="https://linkedin.com/in/priyankvaghani" class="developer-link" target="_blank">Priyank
                vaghani</a>
            </p>
          </div>
        </footer>
      </div>
    </div>
  </section>

    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

    <script>
      // Password toggle functionality
      function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = input.nextElementSibling.querySelector('i');
        
        if (input.type === 'password') {
          input.type = 'text';
          icon.classList.remove('bi-eye');
          icon.classList.add('bi-eye-slash');
        } else {
          input.type = 'password';
          icon.classList.remove('bi-eye-slash');
          icon.classList.add('bi-eye');
        }
      }

      // Initialize AOS
      AOS.init();
    </script>
    <script>
      // Simple password toggle function
      function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const button = input.nextElementSibling.querySelector("i");

        if (input.type === "password") {
          input.type = "text";
          button.classList.remove("bi-eye");
          button.classList.add("bi-eye-slash");
        } else {
          input.type = "password";
          button.classList.remove("bi-eye-slash");
          button.classList.add("bi-eye");
        }
      }
    </script>

    <style>
      /* Style for password toggle button */
      .input-group .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
      }
      .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #495057;
      }
      .input-group .btn-outline-secondary:focus {
        box-shadow: none;
      }
      .input-group .bi {
        font-size: 16px;
      }
    </style>

    <script src="script.js"></script>
    <script>
      // AOS.init();
      // Effect
      AOS.init({
        duration: 1500,
      });
    </script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="script.js"></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
      crossorigin="anonymous"
    ></script>
    <script>
      document.getElementById("form2").onsubmit = function (e) {
        e.preventDefault();
        if (confirm("Are you sure you want to sign up?")) {
          this.submit();
        }
      };
    </script>
    <script>
      document.getElementById("signupForm").addEventListener("submit", function (e) {
        // Form will submit normally
        return true;
      });
    </script>
    <script>
      // Function to open signup tab
      function openSignupTab() {
        const registerTab = document.getElementById("nav-register-tab");
        if (registerTab) {
          registerTab.click();
        }
      }

      // Function to toggle password visibility
      function togglePasswordVisibility(inputId, buttonId) {
        const passwordInput = document.getElementById(inputId);
        const toggleButton = document.getElementById(buttonId);

        if (!passwordInput || !toggleButton) {
          console.log("Element not found:", inputId, buttonId);
          return;
        }

        const icon = toggleButton.querySelector("i");

        if (passwordInput.type === "password") {
          passwordInput.type = "text";
          icon.classList.remove("bi-eye");
          icon.classList.add("bi-eye-slash");
        } else {
          passwordInput.type = "password";
          icon.classList.remove("bi-eye-slash");
          icon.classList.add("bi-eye");
        }
      }

      // Add click event listeners for password toggles
      document.addEventListener("DOMContentLoaded", function () {
        // Admin login password toggle
        document.querySelectorAll("#toggleAdminPassword").forEach((button) => {
          button.addEventListener("click", function () {
            togglePasswordVisibility("adminPassword", "toggleAdminPassword");
          });
        });

        // User login password toggle
        document.querySelectorAll("#toggleLoginPassword").forEach((button) => {
          button.addEventListener("click", function () {
            togglePasswordVisibility("login_password", "toggleLoginPassword");
          });
        });

        // Signup password toggle
        document.querySelectorAll("#toggleSignupPassword").forEach((button) => {
          button.addEventListener("click", function () {
            togglePasswordVisibility("signupPassword", "toggleSignupPassword");
          });
        });
      });
    </script>
    <style>
      /* Add hover effect for Register button */
      .nav-link:hover {
        color: rgb(26, 160, 144) !important;
      }

      /* Style for password toggle button */
      .input-group .btn-outline-secondary {
        border-color: #ced4da;
        color: #6c757d;
      }
      .input-group .btn-outline-secondary:hover {
        background-color: #e9ecef;
        border-color: #ced4da;
        color: #495057;
      }
      .input-group .btn-outline-secondary:focus {
        box-shadow: none;
      }
      .input-group .bi {
        font-size: 16px;
      }
    </style>
    <script>
    function validateEmail(input) {
        const email = input.value;
        const emailRegex = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;
        const feedback = document.getElementById('emailFeedback');
        
        if (!emailRegex.test(email)) {
            input.setCustomValidity('Please enter a valid Gmail address');
            input.classList.add('is-invalid');
            feedback.textContent = 'Only Gmail addresses are allowed (example@gmail.com)';
        } else if (email.includes('..') || email.startsWith('.') || email.indexOf('@') > 30) {
            input.setCustomValidity('Invalid Gmail format');
            input.classList.add('is-invalid');
            feedback.textContent = 'Invalid Gmail format';
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }
    </script>
    <script>
    function validatePhone(input) {
        const phone = input.value;
        const phoneRegex = /^[6-9]\d{9}$/;
        const feedback = document.getElementById('phoneFeedback');
        
        if (!phoneRegex.test(phone)) {
            input.setCustomValidity('Please enter a valid Indian mobile number');
            input.classList.add('is-invalid');
            feedback.textContent = 'Enter a valid 10-digit number starting with 6-9';
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }
    </script>
    <script>
    function validatePassword(input) {
        const password = input.value;
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{10,}$/;
        const feedback = document.getElementById('passwordFeedback');
        const strengthDiv = document.getElementById('passwordStrength');
        
        // Check individual requirements
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecialChar = /[@$!%*?&]/.test(password);
        const isLongEnough = password.length >= 10;
        
        // Calculate password strength
        let strength = 0;
        if (hasUpperCase) strength++;
        if (hasLowerCase) strength++;
        if (hasNumber) strength++;
        if (hasSpecialChar) strength++;
        if (isLongEnough) strength++;
        
        // Update strength indicator
        let strengthText = '';
        let strengthColor = '';
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'Weak';
                strengthColor = 'red';
                break;
            case 2:
            case 3:
                strengthText = 'Moderate';
                strengthColor = 'orange';
                break;
            case 4:
                strengthText = 'Strong';
                strengthColor = '#2ecc71';
                break;
            case 5:
                strengthText = 'Very Strong';
                strengthColor = '#27ae60';
                break;
        }
        
        strengthDiv.innerHTML = `Password Strength: <span style="color: ${strengthColor}">${strengthText}</span>`;
        
        if (!passwordRegex.test(password)) {
            input.setCustomValidity('Password does not meet requirements');
            input.classList.add('is-invalid');
        } else {
            input.setCustomValidity('');
            input.classList.remove('is-invalid');
        }
    }
    </script>
    <style>
    .password-strength {
        font-size: 0.875rem;
        margin-top: 5px;
    }
    </style>
  </body>
</html>
