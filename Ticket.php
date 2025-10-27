<?php

class Ticket {
    private $db;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getAllTickets() {
        return $this->db->fetchAll(
            "SELECT t.*, u.name as user_name
             FROM tickets t
             LEFT JOIN users u ON t.user_id = u.id
             ORDER BY t.created_at DESC"
        );
    }

    public function getTicketsByStatus($status) {
        return $this->db->fetchAll(
            "SELECT t.*, u.name as user_name
             FROM tickets t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.status = ?
             ORDER BY t.created_at DESC",
            [$status]
        );
    }

    public function searchTickets($query) {
        if (empty($query)) {
            return $this->getAllTickets();
        }

        return $this->db->fetchAll(
            "SELECT t.*, u.name as user_name
             FROM tickets t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.title LIKE ? OR t.description LIKE ?
             ORDER BY t.created_at DESC",
            ['%' . $query . '%', '%' . $query . '%']
        );
    }

    public function getTicketById($id) {
        return $this->db->fetchOne(
            "SELECT t.*, u.name as user_name
             FROM tickets t
             LEFT JOIN users u ON t.user_id = u.id
             WHERE t.id = ?",
            [$id]
        );
    }

    public function createTicket($userId, $title, $description, $status = 'open') {
        // Validation
        if (empty($title) || empty($description)) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        $validStatuses = ['open', 'in_progress', 'closed'];
        if (!in_array($status, $validStatuses)) {
            $status = 'open';
        }

        $result = $this->db->query(
            "INSERT INTO tickets (user_id, title, description, status, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$userId, $title, $description, $status]
        );

        if ($result) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }

        return ['success' => false, 'error' => 'Failed to create ticket'];
    }

    public function updateTicket($id, $userId, $title, $description, $status) {
        // Validation
        if (empty($title) || empty($description)) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        // Verify ownership
        $ticket = $this->getTicketById($id);
        if (!$ticket || $ticket['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $validStatuses = ['open', 'in_progress', 'closed'];
        if (!in_array($status, $validStatuses)) {
            $status = 'open';
        }

        $result = $this->db->query(
            "UPDATE tickets
             SET title = ?, description = ?, status = ?, updated_at = NOW()
             WHERE id = ?",
            [$title, $description, $status, $id]
        );

        if ($result) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to update ticket'];
    }

    public function deleteTicket($id, $userId) {
        // Verify ownership
        $ticket = $this->getTicketById($id);
        if (!$ticket || $ticket['user_id'] != $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $result = $this->db->query("DELETE FROM tickets WHERE id = ?", [$id]);

        if ($result) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Failed to delete ticket'];
    }

    public function getStatusColor($status) {
        switch ($status) {
            case 'open':
                return 'bg-green-100 text-green-700 border-green-200';
            case 'in_progress':
                return 'bg-amber-100 text-amber-700 border-amber-200';
            case 'closed':
                return 'bg-gray-100 text-gray-700 border-gray-200';
            default:
                return 'bg-gray-100 text-gray-700 border-gray-200';
        }
    }

    public function getStatusLabel($status) {
        switch ($status) {
            case 'open':
                return 'Open';
            case 'in_progress':
                return 'In Progress';
            case 'closed':
                return 'Closed';
            default:
                return ucfirst(str_replace('_', ' ', $status));
        }
    }
}
