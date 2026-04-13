<?php
// Script to download YouTube Shorts in background
// Should be run via cron every minute: * * * * * php /var/www/html/scripts/yt_downloader.php

// Set working directory to project root for relative includes
chdir(dirname(__DIR__));

// Fix CLI warnings for PlayTube's app_start.php
if (php_sapi_name() == 'cli') {
    $_SERVER['HTTP_HOST'] = getenv('SITE_URL') ?: 'localhost';
    if (strpos($_SERVER['HTTP_HOST'], '://') !== false) {
        $_SERVER['HTTP_HOST'] = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
    }
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

require_once "assets/init.php";

echo "[" . date('Y-m-d H:i:s') . "] Starting Shorts Downloader Worker...\n";

// Set PHP execution time to 15 minutes for large downloads
set_time_limit(900);

// Get pending videos (download_status = 1)
$pending_videos = $db->where('download_status', 1)->where('youtube', '', '<>')->get(T_VIDEOS, 5);

if (empty($pending_videos)) {
    echo "No pending downloads found.\n";
    exit;
}

foreach ($pending_videos as $video) {
    echo "Processing Video ID: {$video->id} (YT ID: {$video->youtube})\n";

    // Set status to processing (2) to avoid double processing
    $db->where('id', $video->id)->update(T_VIDEOS, ['download_status' => 2]);

    $youtube_id = $video->youtube;
    $video_url = "https://www.youtube.com/watch?v=" . $youtube_id;
    
    // Prepare directory: upload/videos/YEAR/MONTH
    $year = date('Y');
    $month = date('m');
    $upload_dir = "upload/videos/{$year}/{$month}";
    if (!file_exists($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }

    $file_hash = PT_GenerateKey(20, 20);
    $output_filename = $file_hash . "_video.mp4";
    $output_path = $upload_dir . "/" . $output_filename;
    
    // Python yt-dlp command
    // Using command installed via pip3 in Dockerfile
    $yt_dlp_path = "python3 -m yt_dlp";
    // Optimization: best mp4 format to avoid heavy conversion if possible
    $command = "{$yt_dlp_path} -f 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best' --merge-output-format mp4 -o '{$output_path}' '{$video_url}' 2>&1";
    
    echo "Running command: {$command}\n";
    
    $output = shell_exec($command);
    echo "Output: {$output}\n";

    if (file_exists($output_path) && filesize($output_path) > 0) {
        echo "Download SUCCESS: {$output_path}\n";
        
        // Update database
        $db->where('id', $video->id)->update(T_VIDEOS, [
            'video_location' => $output_path,
            'download_status' => 0, // Mark as finished
            'converted' => 0,       // Set to 0 to let FFmpeg process it later if needed
            'privacy' => 0          // Set to public after successful download
        ]);
        
        // Add to conversion queue if FFmpeg is enabled
        if ($pt->config->ffmpeg_system == 'on') {
            $db->insert(T_QUEUE, [
                'video_id' => $video->id,
                'video_res' => 'all',
                'processing' => 0
            ]);
            echo "Added to FFmpeg conversion queue.\n";
        }
    } else {
        echo "Download FAILED or File Empty for Video ID: {$video->id}\n";
        // Reset status to 3 (Failed)
        $db->where('id', $video->id)->update(T_VIDEOS, ['download_status' => 3]);
    }
}

echo "Worker finished.\n";
?>
