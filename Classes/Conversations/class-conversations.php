<?php
namespace Classes\Conversations;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;
use Classes\Users\Users;

class Conversations extends Users
{
    use Base, Sanitizer;

    public function get_user_conversations($params)
    {
        $this->check_params($params, ['user_id']);
        
        $user_id = $params['user_id'];

        $sql =
            "SELECT 
                c.id AS conversation_id,
                CASE 
                    WHEN c.is_group = TRUE THEN c.name
                    ELSE (
                        SELECT CONCAT(u2.first_name, ' ', u2.last_name) AS name
                        FROM conversation_participants cp2
                        JOIN users u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS `name`,
                CASE 
                    WHEN c.is_group = TRUE THEN (
                        SELECT e.image_url FROM events e
                        WHERE e.id = c.event_id
                    )
                    ELSE (
                        SELECT u2.avatar AS avatar
                        FROM conversation_participants cp2
                        JOIN users u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS avatar,
                c.is_group,
                 JSON_OBJECT(
                    'text', m.content,
                    'time', m.created_at
                ) AS last_message,
                CONCAT(u.first_name, ' ', u.last_name) AS last_sender_name
            FROM conversation_participants cp
            JOIN conversations c ON cp.conversation_id = c.id
            LEFT JOIN messages m ON m.id = (
                SELECT m2.id
                FROM messages m2
                WHERE m2.conversation_id = c.id
                ORDER BY m2.created_at DESC
                LIMIT 1
            )
            LEFT JOIN users u ON m.sender_id = u.id
            WHERE cp.user_id = ?
            ORDER BY m.created_at DESC;";

        $conversations = $this->getData($sql, [$user_id, $user_id, $user_id], true);

        foreach ($conversations as &$conversation) {
            $conversation['last_message'] = json_decode($conversation['last_message']);
        }
        Response::success('گفتگوها با موفقیت دریافت شد', 'allConversations', $conversations);
    }

}