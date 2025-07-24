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

$router->add('/events/get-all', 'GET', 'Classes\Events\Events', 'get_events');
$router->add('/events/get-by-slug', 'POST', 'Classes\Events\Events', 'get_event_by_slug');

$router->add('/leaders/get-all', 'GET', 'Classes\Leaders\Leaders', 'get_leaders');

$router->add('/conversations/get-user-conversations', 'GET', 'Classes\Conversations\Conversations', 'get_user_conversations');
$router->add('/conversations/get-conversation-messages', 'POST', 'Classes\Conversations\Conversations', 'get_conversation_messages');
$router->add('/conversations/send-message-to-conversation', 'POST', 'Classes\Conversations\Conversations', 'send_message_to_conversation');

$router->add('/memories/get-all', 'POST', 'Classes\Memories\Memories', 'get_memories');
$router->add('/memories/get-memory-medias', 'POST', 'Classes\Memories\Memories', 'get_memoriy_medias');