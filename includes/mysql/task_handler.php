<?php
// Start a session only if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is authenticated before handling any task mutations or queries
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

require_once 'conn.php';

/**
 * Fetch all tasks for a specific user with optional search, status, priority, and category filters
 */
function getTasks($pdo, $userId, $search = '', $status = 'all', $priority = 'all', $tag = 'all') {
    $query = "SELECT * FROM tasks WHERE user_id = ?";
    $params = [$userId];

    if (!empty($search)) {
        $query .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($status === 'completed') {
        $query .= " AND completed = 1";
    } elseif ($status === 'pending') {
        $query .= " AND completed = 0";
    }

    if ($priority !== 'all') {
        $query .= " AND priority = ?";
        $params[] = $priority;
    }

    if ($tag !== 'all') {
        $query .= " AND category = ?";
        $params[] = $tag;
    }

    $query .= " ORDER BY created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Fetch a single task by ID and verify it belongs to the user
 */
function getTaskById($pdo, $id, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    return $stmt->fetch();
}

/**
 * Insert a new task into the database linked to the user
 */
function insertTask($pdo, $userId, $title, $description, $priority, $dueDate, $category) {
    $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, description, priority, due_date, category, completed, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
    return $stmt->execute([
        $userId,
        $title, 
        empty($description) ? null : $description, 
        $priority, 
        empty($dueDate) ? null : $dueDate, 
        empty($category) ? 'General' : $category
    ]);
}

/**
 * Update an existing task in the database ensuring ownership
 */
function updateTask($pdo, $id, $userId, $title, $description, $priority, $dueDate, $category) {
    $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, priority = ?, due_date = ?, category = ? WHERE id = ? AND user_id = ?");
    return $stmt->execute([
        $title, 
        empty($description) ? null : $description, 
        $priority, 
        empty($dueDate) ? null : $dueDate, 
        empty($category) ? 'General' : $category,
        $id,
        $userId
    ]);
}

/**
 * Toggle task completion status ensuring ownership
 */
function toggleTaskStatus($pdo, $id, $userId) {
    $stmt = $pdo->prepare("UPDATE tasks SET completed = NOT completed WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $userId]);
}

/**
 * Delete task from database ensuring ownership
 */
function deleteTask($pdo, $id, $userId) {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    return $stmt->execute([$id, $userId]);
}

/**
 * Get distinct categories for filter dropdown restricted to the user's tasks
 */
function getCategories($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT DISTINCT category FROM tasks WHERE user_id = ? ORDER BY category ASC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Handle incoming POST requests for SSR operations
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'];

    if ($action === 'add') {
        insertTask(
            $pdo, 
            $userId,
            $_POST['title'] ?? '', 
            $_POST['description'] ?? '', 
            $_POST['priority'] ?? 'Medium', 
            $_POST['due_date'] ?? null, 
            $_POST['category'] ?? 'General'
        );
        header('Location: ../../dashboard.php?msg=added');
        exit;
    }

    if ($action === 'update') {
        updateTask(
            $pdo, 
            $_POST['id'] ?? 0,
            $userId,
            $_POST['title'] ?? '', 
            $_POST['description'] ?? '', 
            $_POST['priority'] ?? 'Medium', 
            $_POST['due_date'] ?? null, 
            $_POST['category'] ?? 'General'
        );
        header('Location: ../../dashboard.php?msg=updated');
        exit;
    }

    if ($action === 'toggle') {
        toggleTaskStatus($pdo, $_POST['id'] ?? 0, $userId);
        header('Location: ../../dashboard.php');
        exit;
    }

    if ($action === 'delete') {
        deleteTask($pdo, $_POST['id'] ?? 0, $userId);
        header('Location: ../../dashboard.php?msg=deleted');
        exit;
    }

    if ($action === 'import_json') {
        if (isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK) {
            $jsonData = file_get_contents($_FILES['json_file']['tmp_name']);
            $tasksArray = json_decode($jsonData, true);
            if (is_array($tasksArray)) {
                foreach ($tasksArray as $t) {
                    insertTask(
                        $pdo, 
                        $userId,
                        $t['title'] ?? 'Untitled', 
                        $t['description'] ?? '', 
                        $t['priority'] ?? 'Medium', 
                        !empty($t['due_date']) ? $t['due_date'] : (!empty($t['dueDate']) ? $t['dueDate'] : null), 
                        $t['category'] ?? 'General'
                    );
                }
                header('Location: ../../dashboard.php?msg=imported');
                exit;
            }
        }
        header('Location: ../../dashboard.php?msg=import_error');
        exit;
    }
}
?>