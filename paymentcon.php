<?php

$card_hold = $_POST["card_nname"] ?? "";
$card_num = $_POST["card_nnum"] ?? "";
$expiration_date = $_POST["exp_ddate"] ?? "";

$conn = new mysqli("localhost", "root", "", "website");

$query = "SELECT * FROM `payment` WHERE `full_name` = '$card_hold' AND `card_number` = '$card_num' AND `expiration_date` = '$expiration_date'";

$output = $conn->query($query);

if ($output && $output->num_rows > 0) {
    echo "payment succeded";
} else {
    echo "Payment failed";
}
?>
