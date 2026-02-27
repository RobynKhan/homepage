<?php // ─── To-do Widget ────────────────────────────────────────────────── 
?>
<div class="todo-widget">
    <div class="todo-header">
        <h3 class="todo-title"><i class="bi bi-journal-check"></i> To-do</h3>
    </div>
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
    <div class="todo-footer">
        <span id="todo-count" class="todo-count">0 tasks</span>
        <button class="todo-clear-btn" onclick="todoClearDone()" title="Clear completed">Clear done</button>
    </div>
</div>

<script>
    (function() {
        const STORAGE_KEY = 'hp_todos';

        function load() {
            try {
                return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
            } catch {
                return [];
            }
        }

        function save(todos) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(todos));
        }

        function render() {
            const todos = load();
            const list = document.getElementById('todo-list');
            const count = document.getElementById('todo-count');
            if (!list) return;

            list.innerHTML = '';
            todos.forEach((todo, i) => {
                const li = document.createElement('li');
                li.className = 'todo-item' + (todo.done ? ' todo-done' : '');

                const checkbox = document.createElement('button');
                checkbox.className = 'todo-check';
                checkbox.setAttribute('aria-label', todo.done ? 'Mark incomplete' : 'Mark complete');
                checkbox.innerHTML = todo.done ?
                    '<i class="bi bi-check-circle-fill"></i>' :
                    '<i class="bi bi-circle"></i>';
                checkbox.onclick = () => todoToggle(i);

                const text = document.createElement('span');
                text.className = 'todo-text';
                text.textContent = todo.text;

                const del = document.createElement('button');
                del.className = 'todo-del-btn';
                del.setAttribute('aria-label', 'Delete task');
                del.innerHTML = '<i class="bi bi-x"></i>';
                del.onclick = () => todoDelete(i);

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
            const todos = load();
            todos.push({
                text,
                done: false
            });
            save(todos);
            input.value = '';
            render();
        };

        window.todoToggle = function(i) {
            const todos = load();
            if (todos[i]) todos[i].done = !todos[i].done;
            save(todos);
            render();
        };

        window.todoDelete = function(i) {
            const todos = load();
            todos.splice(i, 1);
            save(todos);
            render();
        };

        window.todoClearDone = function() {
            save(load().filter(t => !t.done));
            render();
        };

        document.addEventListener('DOMContentLoaded', render);
    }());
</script>