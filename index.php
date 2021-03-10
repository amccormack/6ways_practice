<?php
session_start();
$servername = "localhost";
$db_username = "web";
$db_password = "webapp456--secure";
$conn = new mysqli($servername, $db_username, $db_password, "web");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$action = $_GET["action"];

function get_username($conn){
    if (isset($_SESSION["username"])){
        return $_SESSION["username"];
    }
    if (isset($_SESSION["user_id"])){
        $user_id = $_SESSION["user_id"];
        $sql = "SELECT id, name from users where id = $user_id;";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = mysqli_fetch_assoc($result);
            $_SESSION["username"] = $row["name"];
            return $row["name"];
        } else {
            unset($_SESSION["user_id"]);
            return NULL;
        }
    }
    return NULL;
}

if (isset($_SESSION["user_id"])){
    $user_id = $_SESSION["user_id"];
    $sql = "SELECT id, name from users where id = $user_id;";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = mysqli_fetch_assoc($result);
        $username = $row["name"];
    } else {
        unset($_SESSION["user_id"]);
    }
}

function action_signup($conn){
    $u = $_POST["username"];
    $p = $_POST["password"];
    if (strlen($u) < 1 || strlen($u) > 29) {
        return "Invalid username";
    }
    if (strlen($p) < 1 || strlen($p) > 29) {
        return "Invalid password";
    }
    $sql = "SELECT name from users where name = '$u'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return "That name is already taken";
    } else {
        $sql = "INSERT INTO users (name, password) VALUES ('$u', '$p');";
        if ($conn->query($sql) === TRUE) {
            return "New user created";
          } else {
            return "Error: " . $sql . "<br>" . $conn->error;
        }
    }    
}

function action_login($conn){
    $u = $_POST["username"];
    $p = $_POST["password"];
    if (strlen($u) < 1 || strlen($u) > 29) {
        return "Invalid username";
    }
    if (strlen($p) < 1 || strlen($p) > 29) {
        return "Invalid password";
    }
    $sql = "SELECT id from users where name = '$u' and password = '$p'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = mysqli_fetch_assoc($result);
        $_SESSION["user_id"] = $row["id"];
        return "Logged In";
    } else {
        return "Login Failure";
    }
}

function action_upload($conn){
    if (!isset($_SESSION["user_id"])){
        return "Must be logged in";
    }
    if (!isset($_FILES["userfile"])){
        return  "no file provided";
    }
    $uploaddir = '/var/www/html/images/';
    $filename = basename($_FILES['userfile']['tmp_name']) . basename($_FILES['userfile']['name']);
    $uploadfile = $uploaddir . $filename;
    if (move_uploaded_file($_FILES["userfile"]["tmp_name"], $uploadfile)) {
        $user_id = $_SESSION["user_id"];
        $sql = "INSERT INTO images (location, fk_user_id) VALUES ('images/$filename', '$user_id');";
        if ($conn->query($sql) === TRUE) {
            return "New image created successfully";
          } else {
            return "Error: " . $sql . "<br>" . $conn->error;
        }            
    } else {
        return "problem uploading image";
    }
}


switch ($action){
    case "signup":
        $TOAST = action_signup($conn);
        break;
    case "login":
        $TOAST = action_login($conn);
        break;
    case "logout":
        unset($_SESSION["user_id"]);
        unset($_SESSION["username"]);
        break;
    case "upload":
        $TOAST = action_upload($conn);
        break;
}


?>

<html>
    <head><title>Image Share</title></head>
    <body>
        <?php if (isset($TOAST)): ?>
            <div>
                <pre><?php echo $TOAST; ?></pre>
            </div>        
        <?php endif; ?>
        <div>
            <h3>
            <?php
                $username = get_username($conn);
                if (!is_null($username)) {
                    echo "Hello $username! <a href='?action=logout'>Logout</a>";
                } else {
                    echo "Hello guest";
                }
            ?>
            </h3>
        </div>

        <?php if (is_null($username)): ?>
            <div>
                <h3>Signup</h3>
                <form action="index.php?action=signup" method="POST">
                    <input type="text" name="username" />
                    <input type="password" name="password" />
                    <input type="submit" value="submit" />
                </form>
            </div>
            <div>
                <h3>Login</h3>
                <form action="index.php?action=login" method="POST">
                    <input type="text" name="username" />
                    <input type="password" name="password" />
                    <input type="submit" value="submit" />
                </form>
            </div>
        <?php endif; ?>

        <?php if (!is_null($username)): ?>
        <div>
            <h3>Upload Image</h3>
            <form enctype="multipart/form-data" action="index.php?action=upload" method="POST">
                <input type="file" name="userfile" />
                <input type="submit" value="submit" />
            </form>
        </div>        
        <div>
            <h3>My Images </h3>
            <ul>
                <?php
                $sql = "SELECT location from images where fk_user_id=$user_id";
                $result = $conn->query($sql);
                while($row = $result->fetch_assoc()) {
                    $l = $row["location"];
                    echo "<li><a href=\"$l\"><img src=\"$l\" style=\"max-width:500px;max-height:500px;\" /></a></li>\n";
                }                
                ?>
            </ul>
        </div>
        <?php endif; ?>
    </body>
</html>