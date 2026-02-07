<?php

echo "<h1>Storage Link & Permission Debugger</h1>";

$target = __DIR__ . '/../storage/app/public';
$link = __DIR__ . '/storage';

echo "<h2>1. Path Information</h2>";
echo "<p><strong>Target Path (storage/app/public):</strong> " . $target . "</p>";
echo "<p><strong>Link Path (public/storage):</strong> " . $link . "</p>";
echo "<p><strong>Real Target Path:</strong> " . (realpath($target) ?: '<span style="color:red">NOT FOUND</span>') . "</p>";

echo "<h2>2. Symlink Status</h2>";

if (file_exists($link)) {
    if (is_link($link)) {
        echo "<p style='color:green'>[OK] 'public/storage' is a symbolic link.</p>";
        $linkTarget = readlink($link);
        echo "<p>It points to: <code>" . $linkTarget . "</code></p>";
        
        // Check if the target matches
        if (realpath($target) === realpath($link)) {
             echo "<p style='color:green'>[OK] The link points to the correct location.</p>";
        } else {
             if (file_exists($link)) {
                 echo "<p style='color:green'>[OK] The link is valid and works.</p>";
             } else {
                 echo "<p style='color:red'>[ERROR] The link exists but is BROKEN (target not found).</p>";
             }
        }
    } else {
        $type = @filetype($link);
        echo "<p style='color:red'>[ERROR] 'public/storage' exists but is NOT a symbolic link. It is a <strong>" . ($type ?: 'unknown type') . "</strong>.</p>";
        
        if ($type === 'dir') {
            echo "<p style='color:blue'>[AUTO-FIX] Attempting to back up and remove the conflicting directory...</p>";
            $backupName = $link . '_backup_' . date('Ymd_His');
            
            if (rename($link, $backupName)) {
                echo "<p style='color:green'>[SUCCESS] Conflicting directory renamed to: <strong>" . basename($backupName) . "</strong></p>";
                
                // Now create the link
                create_symlink($target, $link);
            } else {
                echo "<p style='color:red'>[FAIL] Could not rename directory. Permission denied. Please rename/delete 'public/storage' manually via FTP.</p>";
            }
        } else {
            echo "<p>Please delete this file via FTP/File Manager and run this script again.</p>";
        }
    }
} else {
    echo "<p style='color:orange'>[INFO] 'public/storage' link does NOT exist. Attempting to create it...</p>";
    create_symlink($target, $link);
}

echo "<h2>3. Permission Check</h2>";

if (file_exists($target)) {
    $perms = substr(sprintf('%o', fileperms($target)), -4);
    echo "<p>Storage Directory Permissions: <strong>" . $perms . "</strong></p>";
    
    if (is_writable($target)) {
        echo "<p style='color:green'>[OK] storage/app/public is writable.</p>";
    } else {
        echo "<p style='color:red'>[ERROR] storage/app/public is NOT writable by the web server.</p>";
    }
    
    // Check a sample file
    $files = scandir($target);
    $sampleFile = null;
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            $sampleFile = $f;
            break;
        }
    }
    
    if ($sampleFile) {
        echo "<p>Sample file found: $sampleFile</p>";
        if (is_readable($target . '/' . $sampleFile)) {
             echo "<p style='color:green'>[OK] Sample file is readable.</p>";
        } else {
             echo "<p style='color:red'>[ERROR] Sample file is NOT readable (403 Forbidden likely).</p>";
        }
    }

} else {
    echo "<p style='color:red'>[ERROR] storage/app/public directory does not exist!</p>";
}

echo "<hr>";
echo "<p><a href='/fix_storage.php'>Run Again</a> | <a href='/'>Go Home</a></p>";

function create_symlink($target, $link) {
    try {
        if (symlink($target, $link)) {
            echo "<p style='color:green'>[SUCCESS] Symbolic link created successfully!</p>";
        } else {
            echo "<p style='color:red'>[ERROR] Failed to create symbolic link. 'symlink()' function might be disabled.</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>[ERROR] Exception: " . $e->getMessage() . "</p>";
    }
}
