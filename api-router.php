<?php

use Classes\Base\Api_Router;
$router = new Api_Router();

/**
 * Define routers for the application.
 * 
 * Each router maps an HTTP method and URL pattern to a controller method.
 * The routers defined here are responsible for handling specific API endpoints
 * in the application, and they are linked with methods in the specified classes.
 */
$router->add('/auth/send-code', 'POST', 'Classes\Users\Authentication', 'send_code');
$router->add('/auth/verify-code', 'POST', 'Classes\Users\Authentication', 'verify_code');
$router->add('/auth/register', 'POST', 'Classes\Users\Login', 'user_register');
$router->add('/auth/login', 'POST', 'Classes\Users\Login', 'user_login');
$router->add('/auth/verify-token', 'POST', 'Classes\Users\Login', 'user_validate');

$router->add('/users/edit-profile', 'POST', 'Classes\Users\Users', 'edit_profile');

$router->add('/timeslots/new', 'POST', 'Classes\Timeslots\Timeslots', 'new_timeslot');
$router->add('/timeslots/get', 'GET', 'Classes\Timeslots\Timeslots', 'get_timeslots');
$router->add('/timeslots/edit', 'POST', 'Classes\Timeslots\Timeslots', 'edit_timeslot');
$router->add('/timeslots/delete', 'POST', 'Classes\Timeslots\Timeslots', 'delete_timeslot');

$router->add('/reservations/add', 'POST', 'Classes\Reservations\Reservations', 'add_reservation');
$router->add('/reservations/cancel', 'POST', 'Classes\Reservations\Reservations', 'cancel_reservation');
$router->add('/reservations/manage', 'POST', 'Classes\Reservations\Reservations', function: 'manage_reservations');
$router->add('/reservations/info', 'GET', 'Classes\Reservations\Reservations', 'reservations_info');
$router->add('/reservations/recent', 'GET', 'Classes\Reservations\Reservations', 'recent_reservations');