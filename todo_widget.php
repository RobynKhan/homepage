<?php
// ─── To-do Widget ──────────────────────────────────────────────────
$isAdmin = function_exists('is_admin_logged_in') && is_admin_logged_in();
?>
<div class="todo-widget">
    <div class="todo-header">
        <h3 class="todo-title"><i class="bi bi-journal-check"></i> To-do</h3>
    </div>

    <!-- Todo input & list (available for everyone) -->
    <div class="todo-input-row">
        <input
            type="text"
            id="todo-input"
            class="todo-input"
            placeholder="Add a task…"
            maxlength="120"
            onkeydown="if(event.key==='Enter') todoAdd()" />
        <button class="todo-add-btn" onclick="todoAdd()" aria-label="Add task">
            <i class="bi bi-plus-lg"></i>
        </button>
    </div>
    <ul class="todo-list" id="todo-list"></ul>
    <?php if ($isAdmin): ?>
        <div class="todo-error" id="todo-error" style="display:none;">
            <i class="bi bi-exclamation-triangle"></i>
            <span id="todo-error-msg">Could not load tasks</span>
        </div>
    <?php else: ?>
        <p class="todo-guest-notice" style="font-size:.75rem;opacity:.6;margin:.25rem 0 0;text-align:center;">
            Tasks will clear on refresh. <a href="login_admin.php">Login</a> to save.
        </p>
    <?php endif; ?>
    <div class="todo-footer">
        <span id="todo-count" class="todo-count">0 tasks</span>
        <button class="todo-clear-btn" onclick="todoClearDone()" title="Clear completed">Clear done</button>
    </div>
</div>

<?php if ($isAdmin): ?>
    <!-- Admin: API-backed persistent todos -->
    <script>
        (function() {
            const API = 'todo_api.php';
            let todos = [];

            async function api(action, body) {
                const opts = {
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                };
                if (body) {
                    opts.method = 'POST';
                    opts.body = JSON.stringify(body);
                }
                const res = await fetch(`${API}?action=${action}`, opts);
                if (!res.ok) {
                    const errText = await res.text();
                    console.error(`todo_api ${action} failed (${res.status}):`, errText);
                    throw new Error(errText);
                }
                return res.json();
            }

            function showError(msg) {
                const el = document.getElementById('todo-error');
                const msgEl = document.getElementById('todo-error-msg');
                if (el) {
                    el.style.display = 'flex';
                    if (msgEl) msgEl.textContent = msg || 'Could not load tasks';
                }
            }

            function hideError() {
                const el = document.getElementById('todo-error');
                if (el) el.style.display = 'none';
            }

            async function loadAndRender() {
                try {
                    todos = await api('list');
                    hideError();
                } catch (err) {
                    todos = [];
                    showError('Could not load tasks — check connection');
                    console.error('Todo list load error:', err);
                }
                render();
            }

            function render() {
                const list = document.getElementById('todo-list');
                const count = document.getElementById('todo-count');
                if (!list) return;

                list.innerHTML = '';
                todos.forEach((todo) => {
                    const li = document.createElement('li');
                    li.className = 'todo-item' + (todo.done ? ' todo-done' : '');

                    const checkbox = document.createElement('button');
                    checkbox.className = 'todo-check';
                    checkbox.setAttribute('aria-label', todo.done ? 'Mark incomplete' : 'Mark complete');
                    checkbox.innerHTML = todo.done ?
                        '<i class="bi bi-check-circle-fill"></i>' :
                        '<i class="bi bi-circle"></i>';
                    checkbox.onclick = () => todoToggle(todo.id, !todo.done);

                    const text = document.createElement('span');
                    text.className = 'todo-text';
                    text.textContent = todo.text;

                    const del = document.createElement('button');
                    del.className = 'todo-del-btn';
                    del.setAttribute('aria-label', 'Delete task');
                    del.innerHTML = '<i class="bi bi-x"></i>';
                    del.onclick = () => todoDelete(todo.id);

                    li.appendChild(checkbox);
                    li.appendChild(text);
                    li.appendChild(del);
                    list.appendChild(li);
                });

                const total = todos.length;
                const done = todos.filter(t => t.done).length;
                count.textContent = total === 0 ? 'No tasks' : `${done}/${total} done`;
            }

            window.todoAdd = async function() {
                const input = document.getElementById('todo-input');
                const text = input.value.trim();
                if (!text) return;
                try {
                    await api('add', {
                        text
                    });
                    input.value = '';
                    await loadAndRender();
                } catch (err) {
                    console.error('Failed to add todo:', err);
                }
            };

            window.todoToggle = async function(id, done) {
                try {
                    await api('update', {
                        id,
                        done
                    });
                    await loadAndRender();
                } catch (err) {
                    console.error('Failed to toggle todo:', err);
                }
            };

            window.todoDelete = async function(id) {
                try {
                    await api('delete', {
                        id
                    });
                    await loadAndRender();
                } catch (err) {
                    console.error('Failed to delete todo:', err);
                }
            };

            window.todoClearDone = async function() {
                const doneTodos = todos.filter(t => t.done);
                try {
                    await Promise.all(doneTodos.map(t => api('delete', {
                        id: t.id
                    })));
                    await loadAndRender();
                } catch (err) {
                    console.error('Failed to clear done:', err);
                }
            };

            document.addEventListener('DOMContentLoaded', loadAndRender);
        }());
    </script>
<?php else: ?>
    <!-- Guest: in-memory only todos (no persistence) -->
    <script>
        (function() {
            let todos = [];
            let nextId = 1;

            function render() {
                const list = document.getElementById('todo-list');
                const count = document.getElementById('todo-count');
                if (!list) return;

                list.innerHTML = '';
                todos.forEach((todo) => {
                    const li = document.createElement('li');
                    li.className = 'todo-item' + (todo.done ? ' todo-done' : '');

                    const checkbox = document.createElement('button');
                    checkbox.className = 'todo-check';
                    checkbox.setAttribute('aria-label', todo.done ? 'Mark incomplete' : 'Mark complete');
                    checkbox.innerHTML = todo.done ?
                        '<i class="bi bi-check-circle-fill"></i>' :
                        '<i class="bi bi-circle"></i>';
                    checkbox.onclick = () => {
                        todo.done = !todo.done;
                        render();
                    };

                    const text = document.createElement('span');
                    text.className = 'todo-text';
                    text.textContent = todo.text;

                    const del = document.createElement('button');
                    del.className = 'todo-del-btn';
                    del.setAttribute('aria-label', 'Delete task');
                    del.innerHTML = '<i class="bi bi-x"></i>';
                    del.onclick = () => {
                        todos = todos.filter(t => t.id !== todo.id);
                        render();
                    };

                    li.appendChild(checkbox);
                    li.appendChild(text);
                    li.appendChild(del);
                    list.appendChild(li);
                });

                const total = todos.length;
                const done = todos.filter(t => t.done).length;
                count.textContent = total === 0 ? 'No tasks' : `${done}/${total} done`;
            }

            window.todoAdd = function() {
                const input = document.getElementById('todo-input');
                const text = input.value.trim();
                if (!text) return;
                todos.push({
                    id: nextId++,
                    text,
                    done: false
                });
                input.value = '';
                render();
            };

            window.todoToggle = function(id, done) {
                const todo = todos.find(t => t.id === id);
                if (todo) todo.done = done;
                render();
            };

            window.todoDelete = function(id) {
                todos = todos.filter(t => t.id !== id);
                render();
            };

            window.todoClearDone = function() {
                todos = todos.filter(t => !t.done);
                render();
            };

            document.addEventListener('DOMContentLoaded', render);
        }());
    </script>
<?php endif; ?>