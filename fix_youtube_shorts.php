<?php
require_once('./assets/init.php');

// Temukan semua video Shorts yang memiliki kolom youtube terisi, namun sudah selesai diunduh.
$videos = $db->where('is_short', 1)->where('youtube', '', '!=')->get(T_VIDEOS);

$count = 0;
foreach ($videos as $video) {
    // Jika lokasi video mengarah ke direktori lokal (MP4 asli) -> Berarti sudah selesai diunduh
    if (strpos($video->video_location, 'upload/videos') !== false && strpos($video->video_location, '.mp4') !== false) {
        $db->where('id', $video->id)->update(T_VIDEOS, [
            'youtube' => '',
            'converted' => 1,
            'download_status' => 0
        ]);
        $count++;
    }
}

echo "<div style='font-family: Arial, sans-serif; text-align: center; margin-top: 50px;'>";
echo "<h2>Perbaikan Berhasil! 🎉</h2>";
echo "<p>Telah menormalkan <strong>{$count}</strong> video Shorts Anda dari status nyangkut dan URL error.</p>";
echo "<p>Silakan tutup halaman ini dan buka kembali menu Shorts di website Anda, video sekarang sudah bisa diputar secara normal.</p>";
echo "<p><small><b>Catatan Keamanan:</b> Untuk memastikan keamanan, file skrip ini akan dianjurkan untuk dihapus jika Anda sudah selesai memperbaikinya.</small></p>";
echo "</div>";
?>
