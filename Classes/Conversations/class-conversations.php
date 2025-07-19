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
        $user = $this->check_role(['user', 'leader', 'admin']);

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
                        SELECT e.image_url FROM events e
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
                    'text', m.content,
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

        if ($conversations) {
            foreach ($conversations as &$conversation) {
                $conversation['last_message'] = json_decode($conversation['last_message']);
            }

            Response::success('گفتگوها با موفقیت دریافت شد', 'allConversations', $conversations);
        }

        Response::error('گفتگویی یافت نشد');
    }

    public function get_conversation_messages($params)
    {
        $user = $this->check_role(['user', 'leader', 'admin']);
        $this->check_params($params, ['conversation_id']);
        $conversation_id = $params['conversation_id'];
        $user_id = $user['id'];

        $is_group = $this->getData("SELECT is_group FROM {$this->table['conversations']} WHERE id = ?", [$conversation_id]);
        if (!$is_group) {
            Response::error('چت مورد نظر یافت نشد');
        }

        if ($is_group['is_group']) {
            $messages = $this->getData("
            SELECT 
                m.id, 
                m.conversation_id, 
                m.content AS text, 
                m.sender_id, 
                m.reply_to, 
                m.read, 
                m.created_at,
                u.name AS sender_name,
                u.avatar AS sender_avatar
            FROM {$this->table['messages']} m
            JOIN {$this->table['users']} u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            ORDER BY m.id ASC
        ", [$conversation_id], true);
        } else {
            $messages = $this->getData("
            SELECT 
                m.id, 
                m.conversation_id, 
                m.content AS text, 
                m.sender_id, 
                m.reply_to, 
                m.read, 
                m.created_at
            FROM {$this->table['messages']} m
            WHERE m.conversation_id = ?
            ORDER BY m.id ASC
        ", [$conversation_id], true);
        }

        if (is_null($messages)) {
            Response::success('هنوز پیامی ارسال نکرده اید');
        }

        $formatted = array_map(function ($msg) use ($user_id, $is_group) {
            return [
                'text' => $msg['text'],
                'time' => date('Y/m/d H:i', strtotime($msg['created_at'])),
                'is_self' => $msg['sender_id'] == $user_id,
                'sender' => $is_group['is_group'] ? [
                    'name' => $msg['sender_name'],
                    'avatar' => $msg['sender_avatar']
                ] : null
            ];
        }, $messages);

        Response::success('پیام‌ها با موفقیت دریافت شد', 'allConversationMessages', $formatted);
    }

    public function send_message_to_conversation($params)
    {
        $sender = $this->check_role(['user', 'leader']);
        file_put_contents('mamad.json', json_encode($sender));
        $this->check_params($params, ['conversation_id', 'content']);

        $conversation_id = $params['conversation_id'];
        $content = $params['content'];
        $sender_id = $sender['id'];
        $now = $this->current_time();

        $message_id = $this->insertData(
            "INSERT INTO {$this->table['messages']} (`conversation_id`, `content`, `sender_id`, `created_at`) VALUES (?, ?, ?, ?)",
            [$conversation_id, $content, $sender_id, $now]
        );

        if ($message_id) {
            Response::success('پیام ارسال شد', 'message', [
                'id' => $message_id,
                'sender' => $sender['first_name'].' '.$sender['last_name'],
                'avatar' => $sender['avatar']
            ]);
        }

        Response::error('خطا در ارسال پیام');
    }

}