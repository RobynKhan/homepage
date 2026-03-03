<?php

/**
 * ============================================================================
 * messages.php — Admin Inbox
 * ============================================================================
 * Protected admin-only messaging page.
 * Uses the same header/footer and CSS variables as the rest of the app.
 * ============================================================================
 */
session_start();
require_once __DIR__ . '/auth_config.php';
require_once __DIR__ . '/config.php';
require_admin_login();

$admin = current_admin();
$me    = $admin['username'];

// Build the recipient list (all admins except self)
$other_admins = array_filter(array_keys(ADMIN_ACCOUNTS), fn($u) => $u !== $me);
?>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<style>
    /* ── Messages Page ─────────────────────────────────────── */
    .msg-wrap {
        width: min(680px, 92vw);
        margin: clamp(20px, 4vh, 48px) auto clamp(16px, 3vh, 32px);
        animation: fadeInUp 0.5s ease both;
    }

    /* Tab bar */
    .msg-tabs {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1.25rem;
    }

    .msg-tabs__label {
        font-family: "Press Start 2P", monospace;
        font-size: 0.6rem;
        color: var(--sand);
        letter-spacing: 2px;
    }

    .msg-tabs__btns {
        display: flex;
        gap: 0.4rem;
    }

    /* Reuse existing .timers button style — just override what we need */
    .msg-tab-btn {
        font-family: "Press Start 2P", monospace;
        font-size: 0.55rem;
        letter-spacing: 1px;
        padding: 8px 14px;
        background: var(--px-bg, var(--glass-bg));
        border: 2px solid var(--glass-border);
        color: var(--text-muted);
        cursor: pointer;
        transition: all var(--transition);
        position: relative;
    }

    .msg-tab-btn:hover {
        background: var(--glass-hover);
        color: var(--cream);
        border-color: var(--sand);
    }

    .msg-tab-btn.active {
        background: var(--glass-hover);
        color: var(--cream);
        border-color: var(--sand);
        box-shadow: 0 0 10px rgba(156, 95, 192, 0.3);
    }

    /* Message list rows */
    .msg-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.4rem;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        cursor: pointer;
        transition: background var(--transition), border-color var(--transition);
        border-radius: 10px;
    }

    .msg-row:hover {
        background: var(--glass-hover);
        border-color: var(--sand);
    }

    .msg-row.unread {
        border-color: var(--sand);
        box-shadow: 0 0 10px rgba(156, 95, 192, 0.2);
    }

    .msg-row__left {
        overflow: hidden;
        flex: 1;
    }

    .msg-row__from {
        font-family: "Press Start 2P", monospace;
        font-size: 0.5rem;
        letter-spacing: 1px;
        margin-bottom: 0.35rem;
        color: var(--text-muted);
    }

    .msg-row.unread .msg-row__from {
        color: var(--sand);
    }

    .msg-row__subject {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--text-primary);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .msg-row__date {
        font-family: "Press Start 2P", monospace;
        font-size: 0.42rem;
        color: var(--text-muted);
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Message viewer */
    .msg-viewer {
        display: none;
    }

    .msg-viewer__back {
        font-family: "Press Start 2P", monospace;
        font-size: 0.55rem;
        letter-spacing: 1px;
        padding: 8px 14px;
        background: transparent;
        border: 2px solid var(--glass-border);
        color: var(--text-muted);
        cursor: pointer;
        margin-bottom: 1.25rem;
        transition: all var(--transition);
    }

    .msg-viewer__back:hover {
        background: var(--glass-hover);
        color: var(--cream);
        border-color: var(--sand);
    }

    .msg-viewer__meta {
        font-family: "Press Start 2P", monospace;
        font-size: 0.48rem;
        color: var(--text-muted);
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }

    .msg-viewer__subject {
        font-size: 1rem;
        font-weight: 600;
        color: var(--sand);
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--glass-border);
    }

    .msg-viewer__body {
        font-size: 0.88rem;
        line-height: 1.8;
        color: var(--text-primary);
        white-space: pre-wrap;
    }

    /* Compose form */
    .msg-compose {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .msg-field label {
        display: block;
        font-family: "Press Start 2P", monospace;
        font-size: 0.48rem;
        letter-spacing: 1px;
        color: var(--text-muted);
        margin-bottom: 0.4rem;
    }

    .msg-field select,
    .msg-field input,
    .msg-field textarea {
        width: 100%;
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: 10px;
        color: var(--text-primary);
        padding: 0.6rem 0.9rem;
        font-family: "Inter", sans-serif;
        font-size: 0.88rem;
        box-sizing: border-box;
        transition: border-color var(--transition), box-shadow var(--transition);
        outline: none;
    }

    .msg-field select:focus,
    .msg-field input:focus,
    .msg-field textarea:focus {
        border-color: var(--sand);
        box-shadow: 0 0 0 3px rgba(156, 95, 192, 0.12);
    }

    .msg-field textarea {
        resize: vertical;
        min-height: 160px;
    }

    /* Option text inside select */
    .msg-field select option {
        background: #1a0d2e;
        color: var(--text-primary);
    }

    .msg-send-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .msg-send-btn {
        font-family: "Press Start 2P", monospace;
        font-size: 0.55rem;
        letter-spacing: 1px;
        padding: 10px 20px;
        background: var(--sand);
        color: #0f0e17;
        border: none;
        cursor: pointer;
        transition: opacity var(--transition), transform 0.15s;
        border-radius: 999px;
    }

    .msg-send-btn:hover {
        opacity: 0.85;
        transform: scale(1.02);
    }

    .msg-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .msg-send-status {
        font-family: "Press Start 2P", monospace;
        font-size: 0.48rem;
        letter-spacing: 1px;
        color: var(--text-muted);
    }

    /* Empty state */
    .msg-empty {
        font-family: "Press Start 2P", monospace;
        font-size: 0.55rem;
        color: var(--text-muted);
        letter-spacing: 1px;
        padding: 2rem 0;
        text-align: center;
    }

    /* Unread badge (inline) */
    .msg-badge {
        display: none;
        background: #f87171;
        color: #000;
        border-radius: 4px;
        padding: 1px 5px;
        font-size: 0.5rem;
        margin-left: 5px;
        vertical-align: middle;
        font-family: "Press Start 2P", monospace;
    }

    /* Panel container — matches timer-container glass style */
    .msg-panel {
        background: var(--glass-bg);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: clamp(18px, 3vw, 28px);
        min-height: 400px;
    }

    @media (max-width: 600px) {
        .msg-tabs__label {
            display: none;
        }

        .msg-tab-btn {
            font-size: 0.45rem;
            padding: 7px 10px;
        }
    }
</style>

<section>
    <div class="msg-wrap">
        <div class="msg-panel">

            <!-- ── Tab Bar ── -->
            <div class="msg-tabs">
                <span class="msg-tabs__label">✉ MESSAGES</span>
                <div class="msg-tabs__btns">
                    <button id="tab-inbox" class="msg-tab-btn active" onclick="switchTab('inbox')" type="button">
                        INBOX <span id="unread-badge" class="msg-badge"></span>
                    </button>
                    <button id="tab-sent" class="msg-tab-btn" onclick="switchTab('sent')" type="button">SENT</button>
                    <button id="tab-compose" class="msg-tab-btn" onclick="switchTab('compose')" type="button">+ NEW</button>
                </div>
            </div>

            <!-- ── INBOX panel ── -->
            <div id="panel-inbox">
                <div id="msg-list"></div>
                <div id="msg-viewer" class="msg-viewer">
                    <button class="msg-viewer__back" onclick="closeView()" type="button">← BACK</button>
                    <div id="v-meta" class="msg-viewer__meta"></div>
                    <div id="v-subject" class="msg-viewer__subject"></div>
                    <div id="v-body" class="msg-viewer__body"></div>
                </div>
            </div>

            <!-- ── SENT panel ── -->
            <div id="panel-sent" style="display:none;">
                <div id="sent-list"></div>
            </div>

            <!-- ── COMPOSE panel ── -->
            <div id="panel-compose" style="display:none;">
                <div class="msg-compose">

                    <div class="msg-field">
                        <label for="c-to">TO</label>
                        <select id="c-to">
                            <?php foreach ($other_admins as $u): ?>
                                <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="msg-field">
                        <label for="c-subject">SUBJECT</label>
                        <input id="c-subject" type="text" placeholder="subject...">
                    </div>

                    <div class="msg-field">
                        <label for="c-body">MESSAGE</label>
                        <textarea id="c-body" placeholder="write your message..."></textarea>
                    </div>

                    <div class="msg-send-row">
                        <button id="send-btn" class="msg-send-btn" onclick="sendMessage()" type="button">SEND ▶</button>
                        <span id="send-status" class="msg-send-status"></span>
                    </div>

                </div>
            </div>

        </div><!-- /.msg-panel -->
    </div><!-- /.msg-wrap -->
</section>

<script>
    const ME = <?= json_encode($me) ?>;

    // ── Tab switching ──────────────────────────────────────────────────────────
    function switchTab(tab) {
        ['inbox', 'sent', 'compose'].forEach(function(t) {
            document.getElementById('panel-' + t).style.display = t === tab ? '' : 'none';
            document.getElementById('tab-' + t).classList.toggle('active', t === tab);
        });
        if (tab === 'inbox') loadInbox();
        if (tab === 'sent') loadSent();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────
    function fmtDate(str) {
        const d = new Date(str);
        return d.toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric'
            }) +
            ' · ' + d.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
    }

    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function emptyState(text) {
        const d = document.createElement('div');
        d.className = 'msg-empty';
        d.textContent = text;
        return d;
    }

    function buildRow(m, isSent) {
        const unread = !isSent && !m.is_read;
        const row = document.createElement('div');
        row.className = 'msg-row' + (unread ? ' unread' : '');
        row.innerHTML =
            '<div class="msg-row__left">' +
            '<div class="msg-row__from">' +
            (unread ? '● ' : '') +
            escHtml(isSent ? 'TO: ' + m.to_username : 'FROM: ' + m.from_username) +
            '</div>' +
            '<div class="msg-row__subject">' + escHtml(m.subject) + '</div>' +
            '</div>' +
            '<div class="msg-row__date">' + fmtDate(m.created_at) + '</div>';
        if (!isSent) row.onclick = function() {
            openMsg(m);
        };
        return row;
    }

    // ── Load inbox ─────────────────────────────────────────────────────────────
    function loadInbox() {
        const list = document.getElementById('msg-list');
        const viewer = document.getElementById('msg-viewer');
        list.innerHTML = '';
        list.style.display = '';
        viewer.style.display = 'none';

        fetch('messages_api.php?action=inbox')
            .then(function(r) {
                return r.json();
            })
            .then(function(msgs) {
                list.innerHTML = '';
                if (!msgs.length) {
                    list.appendChild(emptyState('NO MESSAGES YET.'));
                    return;
                }
                msgs.forEach(function(m) {
                    list.appendChild(buildRow(m, false));
                });
                // Update badge
                const unread = msgs.filter(function(m) {
                    return !m.is_read;
                }).length;
                const badge = document.getElementById('unread-badge');
                badge.textContent = unread;
                badge.style.display = unread ? '' : 'none';
            })
            .catch(function() {
                list.appendChild(emptyState('COULD NOT LOAD MESSAGES.'));
            });
    }

    // ── Load sent ──────────────────────────────────────────────────────────────
    function loadSent() {
        const list = document.getElementById('sent-list');
        list.innerHTML = '';
        fetch('messages_api.php?action=sent')
            .then(function(r) {
                return r.json();
            })
            .then(function(msgs) {
                list.innerHTML = '';
                if (!msgs.length) {
                    list.appendChild(emptyState('NO SENT MESSAGES.'));
                    return;
                }
                msgs.forEach(function(m) {
                    list.appendChild(buildRow(m, true));
                });
            })
            .catch(function() {
                list.appendChild(emptyState('COULD NOT LOAD MESSAGES.'));
            });
    }

    // ── Open message viewer ────────────────────────────────────────────────────
    function openMsg(m) {
        window.location.href = 'message_view.php?id=' + encodeURIComponent(m.id);
    }

    function closeView() {
        document.getElementById('msg-list').style.display = '';
        document.getElementById('msg-viewer').style.display = 'none';
        loadInbox();
    }

    // ── Send message ───────────────────────────────────────────────────────────
    function sendMessage() {
        const to = document.getElementById('c-to').value;
        const subject = document.getElementById('c-subject').value.trim();
        const body = document.getElementById('c-body').value.trim();
        const status = document.getElementById('send-status');
        const btn = document.getElementById('send-btn');

        if (!subject || !body) {
            status.style.color = '#f87171';
            status.textContent = '⚠ FILL IN ALL FIELDS.';
            return;
        }

        btn.disabled = true;
        status.style.color = 'var(--text-muted)';
        status.textContent = 'SENDING...';

        const fd = new FormData();
        fd.append('action', 'send');
        fd.append('to', to);
        fd.append('subject', subject);
        fd.append('body', body);

        fetch('messages_api.php', {
                method: 'POST',
                body: fd
            })
            .then(function(r) {
                return r.json();
            })
            .then(function(res) {
                btn.disabled = false;
                if (res.ok) {
                    status.style.color = 'var(--sand)';
                    status.textContent = '✓ SENT!';
                    document.getElementById('c-subject').value = '';
                    document.getElementById('c-body').value = '';
                    setTimeout(function() {
                        status.textContent = '';
                    }, 3000);
                } else {
                    status.style.color = '#f87171';
                    status.textContent = '✗ ' + (res.error || 'FAILED.');
                }
            })
            .catch(function() {
                btn.disabled = false;
                status.style.color = '#f87171';
                status.textContent = '✗ NETWORK ERROR.';
            });
    }

    // ── Init ───────────────────────────────────────────────────────────────────
    loadInbox();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>