<?php
// Include config file
require_once("config.php");

// Define variables and initialize with empty values
$username = $password = $confirm_password = "";
$username_err = $password_err = $confirm_password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Validate username
  if (empty(trim($_POST["username"]))) {
    $username_err = "Please enter a username.";
  } else if(!preg_match("/^[a-z0-9_-]{3,16}$/", $_POST["username"])) {
    $username_err = "Please enter a valid username. (3-16 chars, alphabet, digit, underscore(_), hyphen(-) only)";
  } else {
    // Prepare a select statement
    $sql = "SELECT id FROM users WHERE username = ?";

    if ($stmt = $mysqli->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("s", $param_username);

      // Set parameters
      $param_username = trim($_POST["username"]);

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // store result
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
          $username_err = "This username is already taken.";
        } else {
          $username = trim($_POST["username"]);
        }
      } else {
        echo "Oops! Something went wrong. Please try again later.";
      }
    }
  }

  // Validate password
  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter a password.";
  } else if(!preg_match("/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).*$/", trim($_POST["password"]))) {
    $password_err = "Please enter a valid password. (min 8 chars, atleast 1 lowercase, 1 uppercase, 1 digit)";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate confirm password
  if (empty(trim($_POST["confirm_password"]))) {
    $confirm_password_err = "Please confirm password.";
  } else {
    $confirm_password = trim($_POST["confirm_password"]);
    if (empty($password_err) && ($password != $confirm_password)) {
      $confirm_password_err = "Password did not match.";
    }
  }

  // Check input errors before inserting in database
  if (empty($username_err) && empty($password_err) && empty($confirm_password_err)) {
    // Prepare an insert statement
    $sql = "INSERT INTO users (username, password) VALUES (?, ?)";

    if ($stmt = $mysqli->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("ss", $param_username, $param_password);

      // Set parameters
      $param_username = $username;
      $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Redirect to login page
        header("location: login.php");
      } else {
        echo "Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }

  // Close connection
  $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>

  <!-- FONTS -->
  <link href="https://fonts.googleapis.com/css?family=Rubik:400,500,700&display=swap" rel="stylesheet">

  <!-- CSS -->
  <link rel="stylesheet" href="css/common.css">
  <link rel="stylesheet" href="css/register.css">
</head>

<body>
  <div id="container">

    <h1 class="page-title">AMI-POST</h1>

    <div id="registerFormSection">
      <h1>Sign Up</h1>
      <p>Please fill this form to create an account.</p>
      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="input-container">
          <div class="input-group">
            <label for="username">Username:</label>
            <input type="text" name="username" value="<?php echo $username; ?>">
            <div class="error-text"><?php echo $username_err; ?></div>
          </div>
          <div class="input-group">
            <label for="username">Password:</label>
            <input type="password" name="password" value="<?php echo $password; ?>">
            <div class="error-text"><?php echo $password_err; ?></div>
          </div>
          <div class="input-group">
            <label for="username">Confirm Password:</label>
            <input type="password" name="confirm_password" value="<?php echo $confirm_password; ?>">
            <div class="error-text"><?php echo $confirm_password_err; ?></div>
          </div>
        </div>

        <input class="button" type="submit" value="Sign Up">
        <input class="button" type="reset" value="Reset">
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
      </form>
    </div>

  </div>
</body>

</html>