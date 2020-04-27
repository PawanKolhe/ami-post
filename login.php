<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
  header("location: ./dashboard/index.php");
  exit;
}

// Include config file
require_once("config.php");

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Valiidate username
  if (empty(trim($_POST["username"]))) {
    $username_err = "Please enter username.";
  } else if(!preg_match("/^[a-z0-9_-]{3,16}$/", $_POST["username"])) {
    $username_err = "Please enter a valid username. (3-16 chars, alphabet, digit, underscore(_), hyphen(-) only)";
  } else {
    $username = trim($_POST["username"]);
  }

  // Valiidate password
  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter your password.";
  } else if(!preg_match("/^.*(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*[\d]).*$/", $_POST["password"])) {
    $password_err = "Please enter a valid password. (min 8 chars, atleast 1 lowercase, 1 uppercase, 1 digit)";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate credentials
  if (empty($username_err) && empty($password_err)) {
    // Prepare a select statement
    $sql = "SELECT id, username, password FROM users WHERE username = ?";

    if ($stmt = $mysqli->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("s", $param_username);

      // Set parameters
      $param_username = $username;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Store result
        $stmt->store_result();

        // Check if username exists, if yes then verify password
        if ($stmt->num_rows == 1) {
          // Bind result variables
          $stmt->bind_result($id, $username, $hashed_password);
          if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
              // Password is correct, so start a new session
              session_start();

              // Store data in session variables
              $_SESSION["loggedin"] = true;
              $_SESSION["id"] = $id;
              $_SESSION["username"] = $username;

              // Redirect user to welcome page
              header("location: ./dashboard/index.php");
            } else {
              // Display an error message if password is not valid
              $password_err = "The password you entered was not valid.";
            }
          }
        } else {
          // Display an error message if username doesn't exist
          $username_err = "No account found with that username.";
        }
      } else {
        echo "Oops! Something went wrong. Please try again later.";
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
  <title>Login</title>

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
      <h1>Login</h1>
      <p>Please fill in your credentials to login.</p>
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
        </div>

        <input class="button" type="submit" value="Login">
        <input class="button" type="reset" value="Reset">
        <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
      </form>
    </div>
  </div>
</body>

</html>