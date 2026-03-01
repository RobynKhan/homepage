<?php

/**
 * ============================================================================
 * todo_widget.php — Todo/Quests Widget (PixelTune Styled)
 * ============================================================================
 *
 * Renders the todo list widget embedded in the main dashboard (index.php).
 * Two modes:
 *   - Admin (logged in):  Full CRUD interface with input, checkboxes, delete
 *   - Guest (not logged in): Locked state with login prompt
 *
 * The admin mode includes inline JavaScript that communicates with
 * todo_api.php for persistent storage via Supabase PostgreSQL.
 *
 * Included by: index.php (inside container-4)
 * ============================================================================
 */

// ─── Determine Admin Status ───────────────────────────────────────────────
$isAdmin = function_exists('is_admin_logged_in') && is_admin_logged_in();
?>
<div class="px-todo">
    <div class="px-todo-scanlines"></div>

    <!-- ── Todo Widget: Title Bar with Window Controls ── -->
    <div class="px-todo-titlebar">
        <span class="px-todo-titlebar-dots">
            <span class="px-dot px-dot--red"></span>
            <span class="px-dot px-dot--yellow"></span>
            <span class="px-dot px-dot--green"></span>
        </span>
        <h3 class="px-todo-title">QUESTS</h3>
    </div>

    <?php if ($isAdmin): ?>
        <!-- ── Todo Widget: Admin CRUD Interface ── -->
        <div class="px-todo-body">
            <div class="px-todo-input-row">
                <input
                    type="text"
                    id="todo-input"
                    class="px-todo-input"
                    placeholder="+ new quest..."
                    maxlength="120"
                    onkeydown="if(event.key==='Enter') todoAdd()" />
                <button class="px-todo-add-btn" onclick="todoAdd()" aria-label="Add task">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
            <ul class="px-todo-list" id="todo-list"></ul>
            <div class="px-todo-error" id="todo-error" style="display:none;">
                <i class="bi bi-exclamation-triangle"></i>
                <span id="todo-error-msg">SYSTEM ERROR</span>
            </div>
            <div class="px-todo-footer">
                <span id="todo-count" class="px-todo-count">0 / 0</span>
                <button class="px-todo-clear-btn" onclick="todoClearDone()" title="Clear completed">
                    <i class="bi bi-trash3"></i> CLR
                </button>
            </div>
        </div>
    <?php else: ?>
        <!-- ── Todo Widget: Guest Locked State ── -->
        <div class="px-todo-locked">
            <i class="bi bi-lock-fill"></i>
            <p>ADMIN ACCESS REQUIRED</p>
            <a href="login_admin.php" class="px-todo-login-btn">LOGIN</a>
        </div>
    <?php endif; ?>
</div>

<?php if ($isAdmin): ?>
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
                    if (msgEl) msgEl.textContent = msg || 'SYSTEM ERROR';
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
                    showError('CONNECTION LOST');
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
                    li.className = 'px-todo-item' + (todo.done ? ' px-todo-done' : '');

                    const checkbox = document.createElement('button');
                    checkbox.className = 'px-todo-check';
                    checkbox.setAttribute('aria-label', todo.done ? 'Mark incomplete' : 'Mark complete');
                    checkbox.innerHTML = todo.done ?
                        '<span class="px-todo-box">&#x2713;</span>' :
                        '<span class="px-todo-box"></span>';
                    checkbox.onclick = () => todoToggle(todo.id, !todo.done);

                    const text = document.createElement('span');
                    text.className = 'px-todo-text';
                    text.textContent = todo.text;

                    const del = document.createElement('button');
                    del.className = 'px-todo-del';
                    del.setAttribute('aria-label', 'Delete task');
                    del.innerHTML = '&#x2715;';
                    del.onclick = () => todoDelete(todo.id);

                    li.appendChild(checkbox);
                    li.appendChild(text);
                    li.appendChild(del);
                    list.appendChild(li);
                });

                const total = todos.length;
                const done = todos.filter(t => t.done).length;
                count.textContent = total === 0 ? 'NO QUESTS' : `${done} / ${total} DONE`;
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
<?php endif; ?>