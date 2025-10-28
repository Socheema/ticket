<?php

class Ticket {
    private $dataFile;

    public function __construct($dataFile = null) {
        if ($dataFile === null) {
            if (defined('TICKETS_FILE')) {
                $dataFile = TICKETS_FILE;
            } else {
                $dataFile = __DIR__ . '/ticket.json';
            }
        }
        $this->dataFile = $dataFile;
        if (!file_exists($this->dataFile)) {
            // initialize empty array
            @file_put_contents($this->dataFile, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    public function getAllTickets() {
        $tickets = $this->readData();
        usort($tickets, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        return $tickets;
    }

    public function getTicketsByStatus($status) {
        $tickets = $this->readData();
        $filtered = array_filter($tickets, function($t) use ($status) {
            return isset($t['status']) && $t['status'] === $status;
        });
        usort($filtered, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        return array_values($filtered);
    }

    public function searchTickets($query) {
        if (empty($query)) {
            return $this->getAllTickets();
        }
        $q = mb_strtolower($query);
        $tickets = $this->readData();
        $filtered = array_filter($tickets, function($t) use ($q) {
            $title = mb_strtolower($t['title'] ?? '');
            $desc = mb_strtolower($t['description'] ?? '');
            return mb_stripos($title, $q) !== false || mb_stripos($desc, $q) !== false;
        });
        usort($filtered, function($a, $b) {
            return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
        });
        return array_values($filtered);
    }

    public function getTicketById($id) {
        $tickets = $this->readData();
        foreach ($tickets as $t) {
            if ((int)$t['id'] === (int)$id) return $t;
        }
        return null;
    }

    public function createTicket($userId, $title, $description, $status = 'open') {
        // Validation
        if (empty(trim($title)) || empty(trim($description))) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        $validStatuses = ['open', 'in_progress', 'closed'];
        if (!in_array($status, $validStatuses)) {
            $status = 'open';
        }

        $tickets = $this->readData();
        $maxId = 0;
        foreach ($tickets as $t) {
            $maxId = max($maxId, (int)($t['id'] ?? 0));
        }
        $newId = $maxId + 1;
        $now = date('Y-m-d H:i:s');
        $new = [
            'id' => $newId,
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'status' => $status,
            'created_at' => $now,
            'updated_at' => $now
        ];
        $tickets[] = $new;
        $this->writeData($tickets);
        return ['success' => true, 'id' => $newId];
    }

    public function updateTicket($id, $userId, $title, $description, $status) {
        // Validation
        if (empty(trim($title)) || empty(trim($description))) {
            return ['success' => false, 'error' => 'Please fill in all fields'];
        }

        $tickets = $this->readData();
        $found = false;
        foreach ($tickets as &$t) {
            if ((int)$t['id'] === (int)$id) {
                // Verify ownership
                if ((string)($t['user_id'] ?? '') !== (string)$userId) {
                    return ['success' => false, 'error' => 'Unauthorized'];
                }
                $validStatuses = ['open', 'in_progress', 'closed'];
                if (!in_array($status, $validStatuses)) {
                    $status = 'open';
                }
                $t['title'] = $title;
                $t['description'] = $description;
                $t['status'] = $status;
                $t['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        unset($t);
        if ($found) {
            $this->writeData($tickets);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Ticket not found'];
    }

    public function deleteTicket($id, $userId) {
        $tickets = $this->readData();
        $found = false;
        foreach ($tickets as $idx => $t) {
            if ((int)$t['id'] === (int)$id) {
                if ((string)($t['user_id'] ?? '') !== (string)$userId) {
                    return ['success' => false, 'error' => 'Unauthorized'];
                }
                array_splice($tickets, $idx, 1);
                $found = true;
                break;
            }
        }
        if ($found) {
            $this->writeData($tickets);
            return ['success' => true];
        }
        return ['success' => false, 'error' => 'Ticket not found'];
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

    private function readData() {
        $json = @file_get_contents($this->dataFile);
        if ($json === false) return [];
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    private function writeData(array $data) {
        // write atomically
        $tmp = $this->dataFile . '.tmp';
        file_put_contents($tmp, json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        rename($tmp, $this->dataFile);
    }
}
