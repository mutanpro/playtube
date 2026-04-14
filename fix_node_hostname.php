<?php
require_once('assets/includes/functions_general.php');
require_once('config.php');

$db = new mysqli($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Hostname untuk sisi BROWSER (client). Tidak boleh 0.0.0.0.
// Gunakan domain Anda atau localhost.
$new_hostname = "localhost"; 

$settings = [
    'hostname' => $new_hostname,
    'server_port' => '4545',
    'server' => 'nodejs'
];

foreach ($settings as $key => $value) {
    $db->query("UPDATE config SET value = '$value' WHERE name = '$key'");
}

echo "NodeJS configuration corrected in database.<br>";
echo "Hostname: $new_hostname (Client-side)<br>";
echo "Port: 4545<br>";
echo "<br>Selesai. Silakan coba akses kembali website Anda.";
?>
