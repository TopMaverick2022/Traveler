<?php

// Database connection parameters
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'travel');

// Stripe API Keys
// IMPORTANT: Replace with your actual Stripe Publishable and Secret keys
// For development/testing, use test keys. For production, use live keys.
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_YOUR_STRIPE_PUBLISHABLE_KEY'); 
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_STRIPE_SECRET_KEY');

// Stripe Webhook Secret (for verifying webhook signatures)
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_STRIPE_WEBHOOK_SECRET');

// Other configuration constants can go here
// Example: Pagination limits, currency, etc.
define('DEFAULT_CURRENCY', 'usd');

?>
