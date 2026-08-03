<!DOCTYPE html>
<html>
<head></head>
<body>
    <?php
    $conn = new mysqli ("localhost", "root", "", "website");

    if($conn == true)
        echo "the connection is true";
    else
        echo "the connection is false";
    ?>
</body>
</html>