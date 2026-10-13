<?php
require_once 'includes/functions.php';
logout_user();
session_start();
flash('success', 'You have been signed out.');
redirect('login.php');
