<?php
namespace Classes\Notifications;
use Classes\Users\Users;
use Classes\Base\Base;
use Classes\Base\Response;
use Classes\Base\Sanitizer;

class Notifications extends Users
{
    use Base, Sanitizer;

    public function get_user_notifications()
    {
        $user = $this->check_role();

        $sql = "SELECT * FROM {$this->table['notifications']} WHERE user_id = ?";

        $notifications = $this->getData($sql, [$user['id']], true);

        if (!$notifications) {
            Response::success('تراکنشی یافت نشد');
        }

        Response::success('اعلان ها دریافت شد', 'userNotifications', $notifications);
    }
}