<?php
session_start();

// Security: If user is not logged in, redirect them to the login page
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'includes/mysql/task_handler.php';

// Capture Filter Query Parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$tag = $_GET['tag'] ?? 'all';
$msg = $_GET['msg'] ?? '';

// Pass the logged-in user's ID as the second argument matching task_handler definition
$userId = $_SESSION['user_id'];
$tasks = getTasks($pdo, $userId, $search, $status, $priority, $tag);
$categories = getCategories($pdo, $userId);

// Statistics
$totalTasks = count($tasks);
$completedTasks = count(array_filter($tasks, fn($t) => $t['completed'] == 1));
$pendingTasks = $totalTasks - $completedTasks;
$highPriorityTasks = count(array_filter($tasks, fn($t) => $t['priority'] === 'High' && $t['completed'] == 0));
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskMaster - MySQL Server-Side Rendered (SSR) Task Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Configure Tailwind to support class-based dark mode
        tailwind.config = {
            darkMode: 'class',
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
    <script>
        // Check local storage or system preference immediately to avoid flash of light/dark mode
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 h-full flex flex-col antialiased transition-colors duration-200">

    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="dashboard.php">
                <div class="flex items-center space-x-3">
                    <div class="bg-indigo-600 text-white p-2.5 rounded-xl shadow-sm flex items-center justify-center">
                        <i class="fa-solid fa-server text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">TaskMaster</h1>
                    </div>
                </div>
            </a>

            <div class="flex items-center space-x-2 sm:space-x-3">
                <!-- User Profile & Logout -->
                <div class="hidden sm:flex items-center space-x-2 mr-2 text-sm text-slate-600 dark:text-slate-300">
                    <i class="fa-solid fa-user-circle text-indigo-500"></i>
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>

                <!-- Dark/Light Mode Toggle Button -->
                <button onclick="toggleDarkMode()" class="p-2 border border-slate-300 dark:border-slate-700 rounded-lg text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-sm" title="Toggle Dark/Light Mode">
                    <i id="themeIcon" class="fa-solid fa-moon text-sm"></i>
                </button>

                <button onclick="openTaskModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition shadow-sm">
                    <i class="fa-solid fa-plus mr-2"></i>
                    <span>New Task</span>
                </button>

                <a href="includes/mysql/logout.php" class="inline-flex items-center px-3 py-2 border border-rose-300 dark:border-rose-900 text-sm font-medium rounded-lg text-rose-700 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 transition shadow-sm" title="Log Out">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col gap-6 overflow-hidden">

        <?php if ($msg): ?>
            <div id="toast" class="bg-slate-900 dark:bg-slate-800 text-white border border-slate-700 px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center justify-between transition-all duration-500 ease-in-out">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-indigo-400"></i>
                    <span>
                        <?php 
                            if ($msg === 'added') echo 'Task successfully added!';
                            elseif ($msg === 'updated') echo 'Task successfully updated!';
                            elseif ($msg === 'deleted') echo 'Task successfully deleted!';
                            elseif ($msg === 'imported') echo 'Tasks successfully imported!';
                            else echo 'Operation completed successfully.';
                        ?>
                    </span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Tasks</p>
                    <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $totalTasks ?></p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-list text-lg"></i>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Completed</p>
                    <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= $completedTasks ?></p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?= $pendingTasks ?></p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <i class="fa-solid fa-clock text-lg"></i>
                </div>
            </div>
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">High Priority</p>
                    <p class="text-2xl font-bold text-rose-600 dark:text-rose-400 mt-1"><?= $highPriorityTasks ?></p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
            </div>
        </div>

        <form method="GET" action="dashboard.php" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col lg:flex-row gap-4 items-center justify-between">
            <div class="relative w-full lg:w-96">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400 dark:text-slate-500">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search tasks by title or description..." 
                    class="w-full pl-10 pr-4 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end">
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Status:</span>
                    <select name="status" onchange="this.form.submit()" class="border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Status</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Priority:</span>
                    <select name="priority" onchange="this.form.submit()" class="border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all" <?= $priority === 'all' ? 'selected' : '' ?>>All Priorities</option>
                        <option value="High" <?= $priority === 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Medium" <?= $priority === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="Low" <?= $priority === 'Low' ? 'selected' : '' ?>>Low</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Category:</span>
                    <select name="tag" onchange="this.form.submit()" class="border border-slate-300 dark:border-slate-700 rounded-lg px-3 py-1.5 text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="all">All Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $tag === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>

        <div class="flex-1 overflow-y-auto pr-1">
            <?php if (empty($tasks)): ?>
                <div class="flex flex-col items-center justify-center h-64 text-center p-6 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-slate-300 dark:border-slate-800">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-400 dark:text-slate-500 mb-3 text-2xl">
                        <i class="fa-solid fa-clipboard-question"></i>
                    </div>
                    <h3 class="text-base font-semibold text-slate-700 dark:text-slate-200">No tasks found</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-sm">Get started by creating a new task or adjusting your filter criteria.</p>
                    <button onclick="openTaskModal()" class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">Create Task</button>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-4">
                    <?php foreach ($tasks as $task): ?>
                        <?php 
                            $priorityClass = 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
                            if ($task['priority'] === 'High') $priorityClass = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/50 dark:text-rose-400 dark:border-rose-900/50';
                            if ($task['priority'] === 'Medium') $priorityClass = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-900/50';
                            if ($task['priority'] === 'Low') $priorityClass = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900/50';

                            $dueDateFormatted = !empty($task['due_date']) ? '<i class="fa-regular fa-calendar mr-1"></i> Due: ' . $task['due_date'] : '<i class="fa-regular fa-calendar-xmark mr-1"></i> No due date';
                            
                            $isOverdue = false;
                            if (!empty($task['due_date']) && !$task['completed']) {
                                if ($task['due_date'] < date('Y-m-d')) $isOverdue = true;
                            }

                            $createdDateStr = date('M j, Y', strtotime($task['created_at']));
                            $cardOpacity = $task['completed'] ? 'opacity-75 bg-slate-50/80 dark:bg-slate-900/50' : 'bg-white dark:bg-slate-900';
                            $titleStyle = $task['completed'] ? 'line-through text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-white';
                        ?>
                        <div class="<?= $cardOpacity ?> border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm hover:shadow-md transition flex flex-col justify-between relative group">
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex items-start space-x-3">
                                        <form method="POST" action="includes/mysql/task_handler.php" class="mt-1 flex-shrink-0">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                            <button type="submit" class="text-lg text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                                <?= $task['completed'] ? '<i class="fa-solid fa-circle-check text-indigo-600 dark:text-indigo-400"></i>' : '<i class="fa-regular fa-circle"></i>' ?>
                                            </button>
                                        </form>
                                        <div>
                                            <h4 class="font-semibold text-sm <?= $titleStyle ?> leading-snug"><?= htmlspecialchars($task['title']) ?></h4>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-md border <?= $priorityClass ?> flex-shrink-0">
                                        <?= $task['priority'] ?>
                                    </span>
                                </div>

                                <?php if (!empty($task['description'])): ?>
                                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 mb-3 line-clamp-2 pl-7"><?= htmlspecialchars($task['description']) ?></p>
                                <?php else: ?>
                                    <div class="mb-2"></div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 pl-7 pt-3 border-t border-slate-100 dark:border-slate-800 mt-2 gap-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded font-medium">
                                            <i class="fa-solid fa-tag mr-1 text-[10px] text-slate-400 dark:text-slate-500"></i> <?= htmlspecialchars($task['category']) ?>
                                        </span>
                                        <span class="inline-flex items-center text-slate-400 dark:text-slate-500">
                                            <i class="fa-solid fa-clock-rotate-left mr-1"></i> Added: <?= $createdDateStr ?>
                                        </span>
                                        <span class="inline-flex items-center <?= $isOverdue ? 'text-rose-600 dark:text-rose-400 font-semibold' : '' ?>">
                                            <?= $dueDateFormatted ?> <?= $isOverdue ? '(Overdue)' : '' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-1 opacity-90 sm:opacity-0 group-hover:opacity-100 transition self-end sm:self-auto">
                                        <button onclick='openEditModal(<?= json_encode($task) ?>)' title="Edit Task" class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-indigo-600 dark:hover:text-indigo-400 rounded transition">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <form id="delete-form-<?= $task['id'] ?>" method="POST" action="includes/mysql/task_handler.php" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                            <button type="button" onclick="confirmDelete(<?= $task['id'] ?>)" title="Delete Task" class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded transition">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal Form for Add/Edit -->
    <div id="taskModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-900/50">
                <h3 id="modalTitle" class="text-lg font-bold text-slate-900 dark:text-white">Add New Task</h3>
                <button onclick="closeTaskModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form id="taskForm" method="POST" action="includes/mysql/task_handler.php" class="p-6 space-y-4">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="taskId">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Task Title *</label>
                    <input type="text" name="title" id="taskTitle" required placeholder="e.g. Complete quarterly financial review" 
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Description</label>
                    <textarea name="description" id="taskDesc" rows="3" placeholder="Add any details, notes or sub-steps..." 
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Priority</label>
                        <select name="priority" id="taskPriority" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Due Date <span class="text-slate-400 dark:text-slate-500 font-normal">(Optional)</span></label>
                        <input type="date" name="due_date" id="taskDueDate" 
                            class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">Category / Tag</label>
                    <input type="text" name="category" id="taskCategory" placeholder="e.g. Work, Personal, Urgent" 
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" onclick="closeTaskModal()" class="px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition shadow-sm">Save Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dark Mode Toggle Logic & Icon Switcher
        function updateThemeIcon() {
            const icon = document.getElementById('themeIcon');
            if (document.documentElement.classList.contains('dark')) {
                icon.className = "fa-solid fa-sun text-sm text-amber-400";
            } else {
                icon.className = "fa-solid fa-moon text-sm text-slate-700";
            }
        }

        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
            updateThemeIcon();
        }

        // Modal Controls
        function openTaskModal() {
            document.getElementById('taskModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Add New Task';
            document.getElementById('formAction').value = 'add';
            document.getElementById('taskForm').reset();
            document.getElementById('taskId').value = '';
        }

        function closeTaskModal() {
            document.getElementById('taskModal').classList.add('hidden');
        }

        function openEditModal(task) {
            document.getElementById('taskModal').classList.remove('hidden');
            document.getElementById('modalTitle').innerText = 'Edit Task';
            document.getElementById('formAction').value = 'update';
            document.getElementById('taskId').value = task.id;
            document.getElementById('taskTitle').value = task.title;
            document.getElementById('taskDesc').value = task.description || '';
            document.getElementById('taskPriority').value = task.priority;
            document.getElementById('taskDueDate').value = task.due_date || '';
            document.getElementById('taskCategory').value = task.category || '';
        }

        // Run on load to set correct icon state
        document.addEventListener('DOMContentLoaded', () => {
            updateThemeIcon();

            // Auto-hide toast message after 3 seconds
            const toast = document.getElementById('toast');
            if (toast) {
                setTimeout(() => {
                    toast.classList.add('opacity-0', '-translate-y-2');
                    setTimeout(() => toast.remove(), 500); 
                }, 3000);
            }
        });

        // SweetAlert2 Confirmation Dialog for Deleting Tasks
        function confirmDelete(taskId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Are you sure you want to delete this task?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + taskId).submit();
                }
            });
        }
    </script>
</body>
</html>