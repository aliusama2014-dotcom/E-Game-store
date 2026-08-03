<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();
// Start a fresh session just to hold the flash message on the next page.
session_start();
flash_set('success', 'You have been signed out.');
redirect('index.php');
