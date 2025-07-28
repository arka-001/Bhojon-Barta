<!DOCTYPE html>
<html lang="en">
<?php
include("../connection/connect.php");
error_reporting(0);
session_start();

$login_error = ""; // Initialize error message variable

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (!empty($_POST["submit"])) {
        // Use prepared statements to prevent SQL injection
        $loginquery = "SELECT adm_id, username, password FROM admin WHERE username = ?";
        $stmt = mysqli_prepare($db, $loginquery);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);

            if ($row) {
                // Verify the password using password_verify
                if (password_verify($password, $row['password'])) {
                    $_SESSION["adm_id"] = $row['adm_id'];
                    header("refresh:1;url=dashboard.php");
                    exit();
                } else {
                   $login_error = "Invalid Username or Password!"; // Set error message
                }
            } else {
                 $login_error = "Invalid Username or Password!"; // Set error message
            }

            mysqli_stmt_close($stmt);
        } else {
             $login_error = "Database error!"; // Set error message
        }
    }
}
?>

<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">

  <link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Roboto:400,100,300,500,700,900'>
<link rel='stylesheet prefetch' href='https://fonts.googleapis.com/css?family=Montserrat:400,700'>
<link rel='stylesheet prefetch' href='https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css'>

    <link rel="stylesheet" href="css/login.css">
    <!-- Font Awesome 5 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" integrity="sha512-iBBXm8fW90+nuLcSKlbmrPcLa0OT92xO1BIsZ+ywDWZCvqsWgccV3gFoRBv0z+8dLJgyAHIhR35VZc2oM/gI1w==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">


      <style>
        .password-container {
          position: relative;
        }

        .password-container input[type="password"] {
          padding-right: 30px; /* Make space for the icon */
        }

        .password-container .toggle-password {
          position: absolute;
          right: 10px;
          top: 40%;
          transform: translateY(-50%);
          cursor: pointer;
        }
      </style>


</head>

<body>


<div class="container">
  <div class="info">
    <h1>Admin Panel </h1>
  </div>
</div>
<div class="form">

  <div class="thumbnail"><img src="images/manager.png"/></div>

  <!-- Display Bootstrap Alert if there is an error -->
  <?php if (!empty($login_error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $login_error; ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">×</span>
      </button>
    </div>
  <?php endif; ?>

  <span style="color:red;"><?php echo $message; ?></span>
  <span style="color:green;"><?php echo $success; ?></span>

  <form class="login-form" action="index.php" method="post">
      <input type="text" placeholder="Username" name="username"/>
      <div class="password-container">
        <input type="password" placeholder="Password" name="password" id="password-field" />
        <span class="toggle-password">
          <i class="fa fa-eye"></i>
        </span>
      </div>
      <input type="submit"  name="submit" value="Login" />
  </form>
   <p class="message"><a href="forgot_password.php">Forgot Password?</a></p> <!-- Added Forgot Password Link -->
  <!-- <p class="message">Not registered? <a href="admin_register.php">Create an account</a></p> -->


</div>
  <script src='http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
  <script src='js/index.js'></script>

  <!-- Bootstrap JS (Popper.js and jQuery are required) -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <script>
    const togglePassword = document.querySelector('.toggle-password');
    const passwordField = document.querySelector('#password-field');

    togglePassword.addEventListener('click', function (e) {
      // Toggle the type attribute
      const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordField.setAttribute('type', type);
      // Toggle the eye slash icon
      this.querySelector('i').classList.toggle('fa-eye');
      this.querySelector('i').classList.toggle('fa-eye-slash');
    });
  </script>
</body>

</html>