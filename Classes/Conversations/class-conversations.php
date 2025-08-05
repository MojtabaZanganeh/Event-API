<?php
namespace Classes\Conversations;
use Classes\Users\Users;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;

class Conversations extends Users
{
    use Base, Sanitizer;

    public function get_user_conversations()
    {
        $user = $this->check_role();

        $sql =
            "SELECT 
                c.id AS id,
                CASE 
                    WHEN c.is_group = TRUE THEN c.name
                    ELSE (
                        SELECT CONCAT(u2.first_name, ' ', u2.last_name) AS name
                        FROM {$this->table['conversation_participants']} cp2
                        JOIN {$this->table['users']} u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS `name`,
                CASE 
                    WHEN c.is_group = TRUE THEN (
                        SELECT e.thumbnail FROM events e
                        WHERE e.id = c.event_id
                    )
                    ELSE (
                        SELECT u2.avatar AS avatar
                        FROM {$this->table['conversation_participants']} cp2
                        JOIN {$this->table['users']} u2 ON cp2.user_id = u2.id
                        WHERE cp2.conversation_id = c.id AND cp2.user_id != ?
                        LIMIT 1
                    )
                END AS avatar,
                c.is_group,
                 JSON_OBJECT(
                    'text', m.text,
                    'time', m.created_at
                ) AS last_message,
                CONCAT(u.first_name, ' ', u.last_name) AS last_sender_name
            FROM {$this->table['conversation_participants']} cp
            JOIN {$this->table['conversations']} c ON cp.conversation_id = c.id
            LEFT JOIN {$this->table['messages']} m ON m.id = (
                SELECT m2.id
                FROM {$this->table['messages']} m2
                WHERE m2.conversation_id = c.id
                ORDER BY m2.created_at DESC
                LIMIT 1
            )
            LEFT JOIN {$this->table['users']} u ON m.sender_id = u.id
            WHERE cp.user_id = ?
            ORDER BY m.created_at DESC;";

        $conversations = $this->getData($sql, [$user['id'], $user['id'], $user['id']], true);

        if (!$conversations) {
            Response::success('گفتگویی یافت نشد');
        }

        foreach ($conversations as &$conversation) {
            $conversation['last_message'] = json_decode($conversation['last_message']);
        }

        Response::success('گفتگوها دریافت شد', 'allConversations', $conversations);
    }

    public function get_conversation_messages($params)
    {
        $user = $this->check_role();
        $this->check_params($params, ['conversation_id']);

        $conversation_id = $params['conversation_id'];
        $user_id = $user['id'];

        $conversation = $this->getData(
            "SELECT is_group FROM {$this->table['conversations']} WHERE id = ?",
            [$conversation_id]
        );

        if (!$conversation) {
            Response::success('چت مورد نظر یافت نشد');
        }

        $is_group = $conversation['is_group'];

        $messages_json = $this->getData(
            "SELECT 
                    JSON_OBJECT(
                        'id', m.id,
                        'conversation_id', m.conversation_id,
                        'text', m.text,
                        'time', DATE_FORMAT(m.created_at, '%Y/%m/%d %H:%i'),
                        'is_self', (m.sender_id = ?),
                        'sender', CASE 
                            WHEN ? = 1 THEN JSON_OBJECT(
                                'name', CONCAT(us.first_name, ' ', us.last_name),
                                'avatar', us.avatar
                            )
                            ELSE CONCAT(us.first_name, ' ', us.last_name)
                        END,
                        'reply_to', CASE 
                            WHEN m.reply_to IS NOT NULL THEN JSON_OBJECT(
                                'id', r.id,
                                'sender', CONCAT(ur.first_name, ' ', ur.last_name),
                                'text', r.text
                            )
                            ELSE NULL
                        END
                    ) AS message_data

                FROM {$this->table['messages']} m

                LEFT JOIN {$this->table['messages']} r ON m.reply_to = r.id
                LEFT JOIN {$this->table['users']} ur ON r.sender_id = ur.id
                LEFT JOIN {$this->table['users']} us ON m.sender_id = us.id

                WHERE m.conversation_id = ?

                ORDER BY m.id ASC
            ",
            [$user_id, $is_group, $conversation_id],
            true
        );

        if (!$messages_json) {
            Response::success('هنوز پیامی ارسال نکرده‌اید');
        }

        $messages = array_column($messages_json, 'message_data');

        foreach ($messages as &$message) {
            $message = json_decode($message, true);
        }

        Response::success('پیام‌ها دریافت شد', 'allConversationMessages', $messages);
    }

    public function send_message_to_conversation($params)
    {
        $sender = $this->check_role();
        $this->check_params($params, ['conversation_id', 'text']);

        $conversation_id = $params['conversation_id'];
        $text = $params['text'];
        $reply_to = $params['reply_to'] ?? null;
        $sender_id = $sender['id'];
        $now = $this->current_time();

        $message_id = $this->insertData(
            "INSERT INTO {$this->table['messages']} (`conversation_id`, `sender_id`, `text`, `reply_to`, `created_at`) VALUES (?, ?, ?, ?, ?)",
            [$conversation_id, $sender_id, $text, $reply_to, $now]
        );

        if ($message_id) {
            Response::success('پیام ارسال شد', 'sent_message', [
                'id' => $message_id,
                'sender' => $sender['first_name'] . ' ' . $sender['last_name'],
                'avatar' => $sender['avatar']
            ]);
        }

        Response::error('خطا در ارسال پیام');
    }

}