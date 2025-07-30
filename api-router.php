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
$router->add('/auth/reset-password', 'POST', 'Classes\Users\Login', 'reset_password');

$router->add('/users/get-profile', 'GET', 'Classes\Users\Profile', 'get_profile');
$router->add('/users/update-profile', 'POST', 'Classes\Users\Profile', 'update_profile');

$router->add('/events/get-all', 'GET', 'Classes\Events\Events', 'get_events');
$router->add('/events/get-by-slug', 'POST', 'Classes\Events\Events', 'get_event_by_slug');
$router->add('/events/get-similar', 'POST', 'Classes\Events\Events', 'get_similar_events');

$router->add('/leaders/get-all', 'GET', 'Classes\Leaders\Leaders', 'get_leaders');
$router->add('/leaders/get-profile', 'GET', 'Classes\Leaders\Leaders', 'get_leader_profile_data');

$router->add('/conversations/get-user-conversations', 'GET', 'Classes\Conversations\Conversations', 'get_user_conversations');
$router->add('/conversations/get-conversation-messages', 'POST', 'Classes\Conversations\Conversations', 'get_conversation_messages');
$router->add('/conversations/send-message-to-conversation', 'POST', 'Classes\Conversations\Conversations', 'send_message_to_conversation');

$router->add('/memories/get-all', 'POST', 'Classes\Memories\Memories', 'get_memories');
$router->add('/memories/get-memory-medias', 'POST', 'Classes\Memories\Memories', 'get_memoriy_medias');

$router->add('/reservations/get-user-reservations', 'GET', 'Classes\Reservations\Reservations', 'get_user_reservations');

$router->add('/transactions/get-user-transactions', 'GET', 'Classes\Reservations\Transactions', 'get_user_transactions');

$router->add('/notifications/get-user-notifications', 'GET', 'Classes\Notifications\Notifications', 'get_user_notifications');

$router->add('/support/tickets/get-user-tickets', 'GET', 'Classes\Support\Tickets', 'get_user_tickets');