<?php
trait UserPermission
{
    public function is_user_logged_in()
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                'You must be logged in.',
                ['status' => 401]
            );
        }
        return true;
    }
}