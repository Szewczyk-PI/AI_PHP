<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="index.php" method="post">
        <label>Ilość: </label><br>
        <input type="text" name="ilosc">
        <input type="submit" value="total">

    </form>
</body>
</html>

<?php
$item = "pizza";
$price = 5.99;
$ilosc = $_POST["ilosc"];
$total = null;

$total = $ilosc * $price;
echo "you have order x{$ilosc} {$item}s <br>";
echo "your total is {$total} zł";



?>
