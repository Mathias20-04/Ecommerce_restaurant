<?php
// includes/cart_cache.php
function getCachedCart($user_id) {
    $cache_key = "cart_cache_" . $user_id;
    
    // Check if cache exists and is recent (less than 5 minutes old)
    if (isset($_SESSION[$cache_key]) && isset($_SESSION[$cache_key]['timestamp'])) {
        $cache_age = time() - $_SESSION[$cache_key]['timestamp'];
        if ($cache_age < 300) { // 5 minutes
            return $_SESSION[$cache_key]['data'];
        }
    }
    
    return null; // Cache expired or not found
}

function setCachedCart($user_id, $cart_data) {
    $cache_key = "cart_cache_" . $user_id;
    $_SESSION[$cache_key] = [
        'data' => $cart_data,
        'timestamp' => time()
    ];
}

function clearCachedCart($user_id) {
    $cache_key = "cart_cache_" . $user_id;
    unset($_SESSION[$cache_key]);
}
?>