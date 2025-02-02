<?php
class AdminMiddleware {
    public function isAdmin($userData) {
        if (!isset($userData['is_admin']) || !$userData['is_admin']) {
            return false;
        }
        return true;
    }
}
?>