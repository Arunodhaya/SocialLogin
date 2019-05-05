<?php 
// Start session 
if(!session_id()){ 
    session_start(); 
} 
 
// Remove user data from session 
unset($_SESSION['userData']); 
 
// Destroy all session data 
session_destroy(); 
 
// Redirect to the homepage 
header("Location:index.php"); 
?>