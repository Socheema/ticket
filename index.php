<?php


require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Ticket.php';

// Initialize Twig
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/templates');
$twig = new \Twig\Environment($loader, [
    'cache' => false, // Set to '/cache' in production
    'debug' => true
]);

// Add custom functions to Twig
// base-aware path() helper for subdirectory support

$twig->addFunction(new \Twig\TwigFunction('path', function ($path) {
    // Use APP_BASE (from config.php) when available for consistent base path handling
    $base = defined('APP_BASE') ? rtrim(APP_BASE, '/') : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base && strpos($path, '/') === 0) {
        // If mod_rewrite is not available, include index.php in the path when needed
        $includeIndex = (strpos($_SERVER['REQUEST_URI'], '/index.php') !== false) ? '/index.php' : '';
        return $base . $includeIndex . $path;
    }
    $includeIndex = (strpos($_SERVER['REQUEST_URI'], '/index.php') !== false) ? '/index.php' : '';
    return ($base ?: '') . $includeIndex . $path;
}));


// base-aware asset() helper (overrides previous asset helper)
$twig->addFunction(new \Twig\TwigFunction('asset', function ($path) {
    $base = defined('APP_BASE') ? rtrim(APP_BASE, '/') : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    if ($base) {
        return $base . '/public/' . ltrim($path, '/');
    }
    return '/public/' . ltrim($path, '/');
}));

// Make APP_BASE available in all templates
if (defined('APP_BASE')) {
    $twig->addGlobal('APP_BASE', APP_BASE);
}




// Simple routing
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $basePath = rtrim($scriptName, '/\\');
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $uri = substr($requestUri, strlen($basePath));
    if ($uri === '') { $uri = '/'; }
} else {
    $uri = $requestUri;
}
// If index.php is present in the URI (no mod_rewrite), strip it so routing works with /index.php/route
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, strlen('/index.php'));
    if ($uri === '') { $uri = '/'; }
}
$method = $_SERVER['REQUEST_METHOD'];

// Initialize handlers
$db = new Database();
$auth = new Auth($db);
$ticketHandler = new Ticket($db);

// Get current user
$currentUser = $auth->getCurrentUser();

// Handle logout
if ($uri === '/logout' && $method === 'GET') {
    $auth->logout();
    header('Location: /');
    exit;
}

// Routes
switch ($uri) {
    case '/':
        // Landing Page
        $features = [
            [
                'title' => 'Real-time Tracking',
                'description' => 'Monitor ticket status and progress in real-time with instant updates.',
                'icon' => 'clock',
                'image' => 'https://images.unsplash.com/photo-1748256622734-92241ae7b43f?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=800'
            ],
            [
                'title' => 'Easy Organization',
                'description' => 'Organize tickets by status, priority, and category for better workflow.',
                'icon' => 'chart',
                'image' => 'https://images.unsplash.com/photo-1700561306724-b3723282ce5e?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=800'
            ],
            [
                'title' => 'Quick Resolution',
                'description' => 'Streamline your support process and resolve issues faster than ever.',
                'icon' => 'check',
                'image' => 'https://images.unsplash.com/photo-1758519290233-a03c1d17ecc9?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=800'
            ]
        ];

        echo $twig->render('landing.twig', [
            'features' => $features,
            'user' => $currentUser
        ]);
        break;

    case '/login':
        if ($method === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $result = $auth->login($email, $password);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Welcome back!';
                header('Location: /dashboard');
                exit;
            } else {
                $error = $result['error'];
                echo $twig->render('login.twig', [
                    'error' => $error,
                    'email' => $email
                ]);
            }
        } else {
            if ($currentUser) {
                header('Location: /dashboard');
                exit;
            }
            echo $twig->render('login.twig', []);
        }
        break;

    case '/signup':
        if ($method === 'POST') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $result = $auth->signup($name, $email, $password, $confirmPassword);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Account created successfully!';
                header('Location: /dashboard');
                exit;
            } else {
                echo $twig->render('signup.twig', [
                    'error' => $result['error'],
                    'name' => $name,
                    'email' => $email
                ]);
            }
        } else {
            if ($currentUser) {
                header('Location: /dashboard');
                exit;
            }
            echo $twig->render('signup.twig', []);
        }
        break;

    case '/dashboard':
        if (!$currentUser) {
            header('Location: /login');
            exit;
        }

        $tickets = $ticketHandler->getAllTickets();
        $openTickets = $ticketHandler->getTicketsByStatus('open');
        $inProgressTickets = $ticketHandler->getTicketsByStatus('in_progress');
        $closedTickets = $ticketHandler->getTicketsByStatus('closed');
        $recentTickets = array_slice($tickets, 0, 5);

        $stats = [
            [
                'title' => 'Total Tickets',
                'value' => count($tickets),
                'icon' => 'ticket',
                'color' => 'indigo'
            ],
            [
                'title' => 'Open Tickets',
                'value' => count($openTickets),
                'icon' => 'clock',
                'color' => 'green'
            ],
            [
                'title' => 'Resolved Tickets',
                'value' => count($closedTickets),
                'icon' => 'check',
                'color' => 'gray'
            ]
        ];

        $successMessage = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        echo $twig->render('dashboard.twig', [
            'user' => $currentUser,
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'inProgressCount' => count($inProgressTickets),
            'successMessage' => $successMessage
        ]);
        break;

    case '/tickets':
        if (!$currentUser) {
            header('Location: /login');
            exit;
        }

        $searchQuery = $_GET['search'] ?? '';
        $tickets = $ticketHandler->searchTickets($searchQuery);

        $successMessage = $_SESSION['success_message'] ?? null;
        unset($_SESSION['success_message']);

        echo $twig->render('tickets.twig', [
            'user' => $currentUser,
            'tickets' => $tickets,
            'searchQuery' => $searchQuery,
            'successMessage' => $successMessage
        ]);
        break;

    case '/tickets/create':
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($method === 'POST') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'open';

            $result = $ticketHandler->createTicket($currentUser['id'], $title, $description, $status);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Ticket created successfully';
            }

            header('Location: /tickets');
            exit;
        }
        break;

    case (preg_match('/^\/tickets\/(\d+)\/edit$/', $uri, $matches) ? true : false):
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($method === 'POST') {
            $ticketId = $matches[1];
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $status = $_POST['status'] ?? 'open';

            $result = $ticketHandler->updateTicket($ticketId, $currentUser['id'], $title, $description, $status);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Ticket updated successfully';
            }

            header('Location: /tickets');
            exit;
        }
        break;

    case (preg_match('/^\/tickets\/(\d+)\/delete$/', $uri, $matches) ? true : false):
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        if ($method === 'POST') {
            $ticketId = $matches[1];
            $result = $ticketHandler->deleteTicket($ticketId, $currentUser['id']);

            if ($result['success']) {
                $_SESSION['success_message'] = 'Ticket deleted successfully';
            }

            header('Location: /tickets');
            exit;
        }
        break;

    default:
        http_response_code(404);
        echo $twig->render('404.twig', [
            'user' => $currentUser
        ]);
        break;
}
