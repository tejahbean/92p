<?php
header('Content-Type: application/json');
require 'db.php';

// Helper to parse JSON body on PUT/POST
function getJsonInput() {
    return json_decode(file_get_contents('php://input'), true);
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Fetch all tasks
        $stmt = $pdo->query('SELECT * FROM tasks ORDER BY created_at DESC');
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($tasks);
        break;

    case 'POST':
        // Add a new task
        $data = getJsonInput();
        if (!isset($data['title']) || empty(trim($data['title']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Task title is required']);
            exit;
        }

        $sql = "INSERT INTO tasks (title, category, urgency, due, notes, completed) VALUES (:title, :category, :urgency, :due, :notes, 0)";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':title' => $data['title'],
            ':category' => $data['category'] ?? 'Podcast',
            ':urgency' => $data['urgency'] ?? 'low',
            ':due' => !empty($data['due']) ? $data['due'] : null,
            ':notes' => $data['notes'] ?? null,
        ]);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        break;

    case 'PUT':
        // Update a task (toggle completed, or edit)
        $data = getJsonInput();
        if (!isset($data['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is required']);
            exit;
        }

        $sql = "UPDATE tasks SET title = :title, category = :category, urgency = :urgency, due = :due, notes = :notes, completed = :completed WHERE id = :id";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':title' => $data['title'],
            ':category' => $data['category'],
            ':urgency' => $data['urgency'],
            ':due' => !empty($data['due']) ? $data['due'] : null,
            ':notes' => $data['notes'],
            ':completed' => $data['completed'] ? 1 : 0,
            ':id' => $data['id'],
        ]);

        echo json_encode(['success' => true]);
        break;

    case 'DELETE':
        // Delete a task by ID
        parse_str(file_get_contents("php://input"), $delete_vars);
        $id = $delete_vars['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Task ID is required']);
            exit;
        }

        $stmt = $pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);

        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
