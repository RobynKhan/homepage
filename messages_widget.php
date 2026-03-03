<?php

/**
 * ============================================================================
 * messages_widget.php — Inline Admin Messaging Widget (PixelTune Styled)
 * ============================================================================
 * Embeds admin messaging directly inside index.php, styled identically to the
 * QUESTS and PIXELTUNE widgets (retro pixel look, scanlines, Press Start 2P).
 * Calls messages_api.php via fetch for all CRUD operations.
 * Uses Supabase Realtime for live "new message" notifications.
 * ============================================================================
 */
if (!function_exists('is_admin_logged_in') || !is_admin_logged_in()) return;

$msg_admin = current_admin();
$msg_me    = $msg_admin['username'];
$msg_other_admins = array_filter(array_keys(ADMIN_ACCOUNTS), fn($u) => $u !== $msg_me);
?>

<!-- ── Pixel-styled Messages Widget ─────────────────────────────── -->
<div class="px-msg">
    <div class="px-msg-scanlines"></div>

    <!-- Title bar (matches QUESTS widget) -->
    <div class="px-msg-titlebar">
        <span class="px-msg-titlebar-dots">
            <span class="px-dot px-dot--red"></span>
            <span class="px-dot px-dot--yellow"></span>
            <span class="px-dot px-dot--green"></span>
        </span>
        <h3 class="px-msg-title">MESSAGES</h3>
        <span class="px-msg-unread-pip" id="px-msg-pip" style="display:none;"></span>
    </div>

    <!-- Tab row -->
    <div class="px-msg-tabs">
        <button class="px-msg-tab active" id="mtab-inbox" onclick="msgSwitchTab('inbox')" type="button">
            INBOX <span class="px-msg-badge" id="px-msg-badge"></span>
        </button>
        <button class="px-msg-tab" id="mtab-sent" onclick="msgSwitchTab('sent')" type="button">SENT</button>
        <button class="px-msg-tab" id="mtab-compose" onclick="msgSwitchTab('compose')" type="button">+ NEW</button>
    </div>

    <!-- Body -->
    <div class="px-msg-body">

        <!-- Inbox list -->
        <div id="mpanel-inbox" class="px-msg-panel">
            <div id="msg-list" class="px-msg-scroll"></div>
            <div id="msg-viewer" class="px-msg-viewer" style="display:none;">
                <button class="px-msg-back-btn" onclick="msgCloseView()" type="button">&#9664; BACK</button>
                <div class="px-msg-viewer-meta" id="v-meta"></div>
                <div class="px-msg-viewer-subject" id="v-subject"></div>
                <div class="px-msg-viewer-body" id="v-body"></div>
            </div>
        </div>

        <!-- Sent list -->
        <div id="mpanel-sent" class="px-msg-panel" style="display:none;">
            <div id="sent-list" class="px-msg-scroll"></div>
        </div>

        <!-- Compose -->
        <div id="mpanel-compose" class="px-msg-panel" style="display:none;">
            <div class="px-msg-compose">
                <label class="px-msg-label">TO</label>
                <select id="c-to" class="px-msg-select">
                    <?php foreach ($msg_other_admins as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="px-msg-label">SUBJECT</label>
                <input id="c-subject" class="px-msg-input" type="text" placeholder="subject..." maxlength="120">

                <label class="px-msg-label">MESSAGE</label>
                <textarea id="c-body" class="px-msg-textarea" placeholder="write your message..."></textarea>

                <div class="px-msg-send-row">
                    <button id="send-btn" class="px-msg-send-btn" onclick="msgSendMessage()" type="button">SEND &#9654;</button>
                    <span id="send-status" class="px-msg-status"></span>
                </div>
            </div>
        </div>

    </div><!-- /.px-msg-body -->
</div><!-- /.px-msg -->

<script>
    (function() {
            var ME = <?= json_encode($msg_me) ?>;

            // ── Tab switching ──────────────────────────────────────────
            window.msgSwitchTab = function(tab) {
                ['inbox', 'sent', 'compose'].forEach(function(t) {
                    var panel = document.getElementById('mpanel-' + t);
                    var btn = document.getElementById('mtab-' + t);
                    if (panel) panel.style.display = t === tab ? '' : 'none';
                    if (btn) btn.classList.toggle('active', t === tab);
                });
                if (tab === 'inbox') msgLoadInbox();
                if (tab === 'sent') msgLoadSent();
            };

            // Touch support for tab switching
            document.querySelectorAll('.px-msg-tab').forEach(function(btn) {
                btn.addEventListener('touchstart', function(e) {
                    e.preventDefault();
                    btn.click();
                }, {
                    passive: false
                });
            });

            // Touch support for message row selection
            function enableMsgRowTouch() {
                document.querySelectorAll('.px-msg-row').forEach(function(row) {
                    row.addEventListener('touchstart', function(e) {
                        e.preventDefault();
                        row.click();
                    }, {
                        passive: false
                    });
                });
            }
            // Call after inbox/sent loads
            var origMsgLoadInbox = window.msgLoadInbox;
            window.msgLoadInbox = function() {
                origMsgLoadInbox();
                setTimeout(enableMsgRowTouch, 50);
            };
            var origMsgLoadSent = window.msgLoadSent;
            window.msgLoadSent = function() {
                origMsgLoadSent();
                setTimeout(enableMsgRowTouch, 50);
            };
        };

        // ── Helpers ────────────────────────────────────────────────
        function fmtDate(str) {
            var d = new Date(str);
            return d.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric'
                }) +
                ' ' + d.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
        }

        function esc(s) {
            return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function emptyMsg(text) {
            var d = document.createElement('div');
            d.className = 'px-msg-empty';
            d.innerHTML = '<i class="bi bi-envelope-open"></i><span>' + esc(text) + '</span>';
            return d;
        }

        function buildRow(m, isSent) {
            var unread = !isSent && !m.is_read;
            var row = document.createElement('div');
            row.className = 'px-msg-row' + (unread ? ' unread' : '');
            row.innerHTML =
                '<div class="px-msg-row-left">' +
                '<div class="px-msg-row-from">' +
                (unread ? '<span class="px-msg-dot">&#9679;</span> ' : '') +
                esc(isSent ? 'TO: ' + m.to_username : m.from_username) +
                '</div>' +
                '<div class="px-msg-row-subject">' + esc(m.subject) + '</div>' +
                '</div>' +
                '<div class="px-msg-row-date">' + fmtDate(m.created_at) + '</div>';
            if (!isSent) row.onclick = function() {
                msgOpenMsg(m);
            };
            return row;
        }

        // ── Update ALL badge locations ─────────────────────────────
        function syncBadges(count) {
            // Widget badge
            var b = document.getElementById('px-msg-badge');
            if (b) {
                b.textContent = count;
                b.style.display = count > 0 ? '' : 'none';
            }
            // Title bar pip
            var pip = document.getElementById('px-msg-pip');
            if (pip) pip.style.display = count > 0 ? '' : 'none';
            // Header / drawer / dock badges
            ['msg-nav-badge', 'msg-drawer-badge', 'msg-dock-badge'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                if (count > 0) {
                    el.textContent = count;
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                }
            });
        }

        // ── Load inbox ─────────────────────────────────────────────
        window.msgLoadInbox = function() {
            var list = document.getElementById('msg-list');
            var viewer = document.getElementById('msg-viewer');
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
                        list.appendChild(emptyMsg('NO MESSAGES YET'));
                        return;
                    }
                    msgs.forEach(function(m) {
                        list.appendChild(buildRow(m, false));
                    });
                    var unread = msgs.filter(function(m) {
                        return !m.is_read;
                    }).length;
                    syncBadges(unread);
                })
                .catch(function() {
                    list.appendChild(emptyMsg('LOAD ERROR'));
                });
        };

        // ── Load sent ──────────────────────────────────────────────
        window.msgLoadSent = function() {
            var list = document.getElementById('sent-list');
            list.innerHTML = '';
            fetch('messages_api.php?action=sent')
                .then(function(r) {
                    return r.json();
                })
                .then(function(msgs) {
                    list.innerHTML = '';
                    if (!msgs.length) {
                        list.appendChild(emptyMsg('NO SENT MESSAGES'));
                        return;
                    }
                    msgs.forEach(function(m) {
                        list.appendChild(buildRow(m, true));
                    });
                })
                .catch(function() {
                    list.appendChild(emptyMsg('LOAD ERROR'));
                });
        };

        // ── Open message ───────────────────────────────────────────
        window.msgOpenMsg = function(m) {
            window.location.href = 'message_view.php?id=' + encodeURIComponent(m.id);
        };

        // ── Send ───────────────────────────────────────────────────
        window.msgSendMessage = function() {
            var to = document.getElementById('c-to').value;
            var subject = document.getElementById('c-subject').value.trim();
            var body = document.getElementById('c-body').value.trim();
            var status = document.getElementById('send-status');
            var btn = document.getElementById('send-btn');
            if (!subject || !body) {
                status.style.color = 'var(--px-accent)';
                status.textContent = '\u26a0 FILL ALL FIELDS';
                return;
            }
            btn.disabled = true;
            status.style.color = 'var(--px-text2)';
            status.textContent = 'SENDING...';
            var fd = new FormData();
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
                        status.style.color = 'var(--px-green)';
                        status.textContent = '\u2713 SENT!';
                        document.getElementById('c-subject').value = '';
                        document.getElementById('c-body').value = '';
                        setTimeout(function() {
                            status.textContent = '';
                        }, 3000);
                    } else {
                        status.style.color = 'var(--px-accent)';
                        status.textContent = '\u2717 ' + (res.error || 'FAILED');
                    }
                })
                .catch(function() {
                    btn.disabled = false;
                    status.style.color = 'var(--px-accent)';
                    status.textContent = '\u2717 NETWORK ERROR';
                });
        };

        // ── Unread count fetch (used by poller + realtime) ─────────
        function fetchUnreadCount() {
            fetch('messages_api.php?action=unread_count')
                .then(function(r) {
                    return r.json();
                })
                .then(function(d) {
                    if (typeof d.count === 'number') syncBadges(d.count);
                }).catch(function() {});
        }

        // ── Polling fallback (every 15s) ───────────────────────────
        setInterval(fetchUnreadCount, 15000);

        // ── Supabase Realtime: instant notification on INSERT ──────
        if (typeof supabaseClient !== 'undefined' && supabaseClient.channel) {
            supabaseClient
                .channel('admin_messages_realtime')
                .on('postgres_changes', {
                        event: 'INSERT',
                        schema: 'public',
                        table: 'admin_messages',
                        filter: 'to_username=eq.' + ME
                    },
                    function(payload) {
                        fetchUnreadCount();
                        var inboxPanel = document.getElementById('mpanel-inbox');
                        if (inboxPanel && inboxPanel.style.display !== 'none') {
                            msgLoadInbox();
                        }
                    }
                )
                .subscribe();
        }

        // ── Init ───────────────────────────────────────────────────
        msgLoadInbox(); fetchUnreadCount();
    }());
</script>