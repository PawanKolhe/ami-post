<?php

// Initialize the session
session_start();

// Include config file
require_once("../config.php");

// Include htmlpurifier (to clean post content)
require_once("../htmlpurifier/library/HTMLPurifier.auto.php");
$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);

// Check if the user is logged in, if not then redirect him to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: login.php");
  exit;
}

$post = $anonymous = "";
$post_err = $no_posts_err = "";
$success_text = "";
$posts_data = array();

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Check if post is empty
  if (empty(trim($_POST["post"]))) {
    $post_err = "Please enter some content.";
  } else {
    $post = $purifier->purify(trim($_POST["post"]));
    
    // Check if anonymous value is set
    if (isset($_POST["anonymous"])) {
      $anonymous = ($_POST["anonymous"] == "1") ? "1" : "0";
    }

    // Prepare an insert statement
    $sql = "INSERT INTO posts (content, user_id, anonymous) VALUES (?, ?, ?)";

    if ($stmt = $mysqli->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("sis", $param_post, $param_user_id, $param_anonymous);

      // Set parameters
      $param_post = $post;
      $param_user_id = $_SESSION["id"];
      $param_anonymous = $anonymous;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Success
        $success_text = "Post added successfully!";
      } else {
        echo "Failed to execute fetch posts SQL query.";
      }

      // Close statement
      $stmt->close();
    }
  }
}



// PAGINATION
// Get the current page number
if (isset($_GET['pageno'])) {
  $pageno = $_GET['pageno'];
} else {
  $pageno = 1;
}
$no_of_records_per_page = 10;
$offset = ($pageno - 1) * $no_of_records_per_page;
// Get the number of total number of pages
$total_pages_sql = "SELECT COUNT(*) FROM posts";
$result = mysqli_query($mysqli, $total_pages_sql);
$total_rows = mysqli_fetch_array($result)[0];
$total_pages = ceil($total_rows / $no_of_records_per_page);

// FETCH POSTS
// $sql = "SELECT id, content, upvote, downvote, user_id, created_at, anonymous FROM posts ORDER BY created_at DESC";
// Constructing the SQL Query for pagination
$sql = "SELECT * FROM posts ORDER BY created_at DESC LIMIT $offset, $no_of_records_per_page";
$result = $mysqli->query($sql);

if ($result->num_rows > 0) {
  // Store fetched data into array
  while ($row = $result->fetch_assoc()) {
    $post_data = array();
    $post_data['id'] = $row["id"];
    $post_data['content'] = $row["content"];
    $post_data['upvote'] = $row["upvote"];
    $post_data['downvote'] = $row["downvote"];
    $post_data['user_id'] = $row["user_id"];
    $post_data['created_at'] = $row["created_at"];
    $post_data['anonymous'] = $row["anonymous"];
    array_push($posts_data, $post_data);
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>

  <!-- FONTS -->
  <link href="https://fonts.googleapis.com/css?family=Rubik:400,500,700&display=swap" rel="stylesheet">

  <!-- ICONS -->
  <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.13.0/css/all.css" integrity="sha384-IIED/eyOkM6ihtOiQsX2zizxFBphgnv1zbe1bKA+njdFzkr6cDNy16jfIKWu4FNH" crossorigin="anonymous">

  <!-- CSS -->
  <link rel="stylesheet" href="../css/common.css">
  <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
  <div id="container">

    <div class="page-header">
      <h1 class="page-title">AMI-POST</h1>
      <h2>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. 👋</h2>
      <p>
        <a href="../logout.php" class="sign-out-link">Sign Out of Your Account</a>
      </p>
    </div>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
      <div class="input-group">
        <label for="username">Write a post:</label>
        <textarea id="post" name="post" rows="6"></textarea>
        <div class="error-text"><?php echo $post_err; ?></div>
      </div>

      <input class="button" type="submit" value="Add Post">
      <input type="checkbox" id="anonymous" name="anonymous" value="1">
      <label for="anonymous"> Post anonymously</label>
    </form>

    <div class="post-top">
      <h2>All Posts&nbsp;&nbsp;<i class="far fa-mailbox"></i></h2>
      <div>
        <!-- TOP PAGINATION -->
        <div class="pagination">
          <a href="?pageno=1">
            <div class="page-button"><i class="fas fa-chevron-double-left"></i></div>
          </a>
          <a href="<?php if ($pageno <= 1) { echo '#'; } else { echo "?pageno=" . ($pageno - 1); } ?>">
            <div class="page-button <?php if ($pageno <= 1) { echo 'page-disabled'; } ?>">
              <i class="fas fa-chevron-left"></i>
            </div>
          </a>
          <div class="page-info"><?php echo "$pageno of $total_pages"; ?></div>
          <a href="<?php if ($pageno >= $total_pages) { echo '#'; } else { echo "?pageno=" . ($pageno + 1); } ?>">
            <div class="page-button <?php if ($pageno >= $total_pages) { echo 'page-disabled'; } ?>">
              <i class="fas fa-chevron-right"></i>
            </div>
          </a>
          <a href="?pageno=<?php echo $total_pages; ?>">
            <div class="page-button"><i class="fas fa-chevron-double-right"></i></div>
          </a>
        </div>
      </div>
    </div>
    <!-- OUTPUT POST DATA -->
    <div class="posts_container">
      <?php
      foreach ($posts_data as $post_value) {
        // Fetch username
        $sql = "SELECT username FROM users WHERE id = ?";
        if ($stmt = $mysqli->prepare($sql)) {
          // Bind variables to the prepared statement as parameters
          $stmt->bind_param("s", $param_username);

          // Set parameters
          $param_username = $post_value['user_id'];

          // Attempt to execute the prepared statement
          if ($stmt->execute()) {
            // Store result
            $stmt->store_result();

            // Check if username exists, if yes then verify password
            if ($stmt->num_rows == 1) {
              // Bind result variables
              $stmt->bind_result($p_username);
              $stmt->fetch();
            }
          } else {
            echo "Oops! Something went wrong. Please try again later.";
          }
        }

        $result = $mysqli->query($sql);
        $posts_data = array();

        // Check if post is anonymous
        if ($post_value['anonymous'] === "1") {
          $p_username = "anonymous";
        }
        $content = htmlspecialchars($post_value['content']);

        echo <<<EOD
        <div class="post">
          <div class="post-content">{$content}</div>
          <div class="post-user">by <span class="post-username">{$p_username}</span></div>
        </div>
        EOD;
      }

      ?>
    </div>

    <div class="post-bottom">
      <!-- BOTTOM PAGINATION -->
      <div class="pagination">
        <a href="?pageno=1">
          <div class="page-button"><i class="fas fa-chevron-double-left"></i><span>First</span></div>
        </a>
        <a href="<?php if ($pageno <= 1) { echo '#'; } else { echo "?pageno=" . ($pageno - 1); } ?>">
          <div class="page-button <?php if ($pageno <= 1) { echo 'page-disabled'; } ?>">
            <i class="fas fa-chevron-left"></i>
            <span>Prev</span>
          </div>
        </a>
        <div class="page-info"><?php echo "$pageno of $total_pages"; ?></div>
        <a href="<?php if ($pageno >= $total_pages) { echo '#'; } else { echo "?pageno=" . ($pageno + 1); } ?>">
          <div class="page-button <?php if ($pageno >= $total_pages) { echo 'page-disabled'; } ?>">
            <i class="fas fa-chevron-right"></i>
            <span>Next</span>
          </div>
        </a>
        <a href="?pageno=<?php echo $total_pages; ?>">
          <div class="page-button"><i class="fas fa-chevron-double-right"></i><span>Last</span></div>
        </a>
      </div>
    </div>
  </div>
</body>

</html>

<?php
// Close connection
$mysqli->close();
?>