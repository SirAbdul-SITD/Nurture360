<?php
require_once '../config/config.php';

// Guard
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

$user_id = getCurrentUserId();
$page_title = 'Messages';
$errors = [];
$success = '';
// CSRF token for forms
$csrf_token = generateCSRFToken();

// Query params for search and pagination
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$pageInbox = isset($_GET['pi']) ? max(1, (int)$_GET['pi']) : 1;
$pageSent  = isset($_GET['ps']) ? max(1, (int)$_GET['ps']) : 1;
$perPage   = 20;

// Prefill compose from query (e.g., reply)
$prefill_recipient_id = isset($_GET['recipient_id']) ? (int)$_GET['recipient_id'] : 0;
$prefill_subject = isset($_GET['subject']) ? (string)$_GET['subject'] : '';

try {
    $pdo = getDBConnection();

    // Handle POST actions (compose, mark read/unread, delete)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        // CSRF check
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid request token. Please refresh and try again.';
        }

        // Compose new message
        if (empty($errors) && $_POST['action'] === 'send') {
        $recipient_id = (int)($_POST['recipient_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $message_text = trim($_POST['message_text'] ?? '');

        if ($recipient_id <= 0) { $errors[] = 'Please choose a recipient.'; }
        if ($recipient_id === (int)$user_id) { $errors[] = 'You cannot send a message to yourself.'; }
        if ($message_text === '') { $errors[] = 'Message cannot be empty.'; }
        if (mb_strlen($subject) > 255) { $errors[] = 'Subject must be at most 255 characters.'; }

        // Verify recipient exists
        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$recipient_id]);
            if (!$stmt->fetch()) {
                $errors[] = 'Selected recipient does not exist.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO messages (sender_id, recipient_id, subject, message_text) VALUES (?,?,?,?)');
            $stmt->execute([$user_id, $recipient_id, $subject, $message_text]);
            $success = 'Message sent successfully.';
        }
        }

        // Mark as read
        if (empty($errors) && $_POST['action'] === 'mark_read') {
            $mid = (int)($_POST['message_id'] ?? 0);
            if ($mid > 0) {
                $stmt = $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?');
                $stmt->execute([$mid, $user_id]);
                $success = 'Message marked as read.';
            }
        }

        // Mark as unread
        if (empty($errors) && $_POST['action'] === 'mark_unread') {
            $mid = (int)($_POST['message_id'] ?? 0);
            if ($mid > 0) {
                $stmt = $pdo->prepare('UPDATE messages SET is_read = 0 WHERE id = ? AND recipient_id = ?');
                $stmt->execute([$mid, $user_id]);
                $success = 'Message marked as unread.';
            }
        }

        // Delete message (hard delete - allowed for sender or recipient)
        if (empty($errors) && $_POST['action'] === 'delete') {
            $mid = (int)($_POST['message_id'] ?? 0);
            if ($mid > 0) {
                $stmt = $pdo->prepare('DELETE FROM messages WHERE id = ? AND (sender_id = ? OR recipient_id = ?)');
                $stmt->execute([$mid, $user_id, $user_id]);
                $success = 'Message deleted.';
            }
        }
    }

    // View a message
    $view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
    $view_message = null;
    if ($view_id > 0) {
        $stmt = $pdo->prepare('SELECT m.*, s.first_name AS sender_first, s.last_name AS sender_last, r.first_name AS recipient_first, r.last_name AS recipient_last
                               FROM messages m
                               JOIN users s ON s.id = m.sender_id
                               JOIN users r ON r.id = m.recipient_id
                               WHERE m.id = ? AND (m.sender_id = ? OR m.recipient_id = ?)
                               LIMIT 1');
        $stmt->execute([$view_id, $user_id, $user_id]);
        $view_message = $stmt->fetch();
        if ($view_message && !$view_message['is_read'] && (int)$view_message['recipient_id'] === (int)$user_id) {
            $pdo->prepare('UPDATE messages SET is_read = 1 WHERE id = ?')->execute([$view_id]);
            $view_message['is_read'] = 1;
        }
    }

    // Build filters
    $like = '%' . $q . '%';

    // Count inbox for pagination
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM messages m WHERE m.recipient_id = ? ' . ($q !== '' ? 'AND (m.subject LIKE ? OR m.message_text LIKE ?)' : ''));
    $stmt->execute($q !== '' ? [$user_id, $like, $like] : [$user_id]);
    $inboxTotal = (int)($stmt->fetch()['c'] ?? 0);
    $inboxPages = max(1, (int)ceil($inboxTotal / $perPage));
    if ($pageInbox > $inboxPages) $pageInbox = $inboxPages;
    $offsetInbox = ($pageInbox - 1) * $perPage;

    // Load inbox page
    $inbox = [];
    $sqlInbox = 'SELECT m.id, m.subject, m.message_text, m.is_read, m.sent_at, u.first_name, u.last_name
                 FROM messages m
                 JOIN users u ON u.id = m.sender_id
                 WHERE m.recipient_id = ? ' . ($q !== '' ? 'AND (m.subject LIKE ? OR m.message_text LIKE ?)' : '') . '
                 ORDER BY m.sent_at DESC
                 LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offsetInbox;
    $stmt = $pdo->prepare($sqlInbox);
    $stmt->execute($q !== '' ? [$user_id, $like, $like] : [$user_id]);
    $inbox = $stmt->fetchAll();

    // Count sent for pagination
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM messages m WHERE m.sender_id = ? ' . ($q !== '' ? 'AND (m.subject LIKE ? OR m.message_text LIKE ?)' : ''));
    $stmt->execute($q !== '' ? [$user_id, $like, $like] : [$user_id]);
    $sentTotal = (int)($stmt->fetch()['c'] ?? 0);
    $sentPages = max(1, (int)ceil($sentTotal / $perPage));
    if ($pageSent > $sentPages) $pageSent = $sentPages;
    $offsetSent = ($pageSent - 1) * $perPage;

    // Load sent page
    $sent = [];
    $sqlSent = 'SELECT m.id, m.subject, m.message_text, m.is_read, m.sent_at, u.first_name, u.last_name
                FROM messages m
                JOIN users u ON u.id = m.recipient_id
                WHERE m.sender_id = ? ' . ($q !== '' ? 'AND (m.subject LIKE ? OR m.message_text LIKE ?)' : '') . '
                ORDER BY m.sent_at DESC
                LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offsetSent;
    $stmt = $pdo->prepare($sqlSent);
    $stmt->execute($q !== '' ? [$user_id, $like, $like] : [$user_id]);
    $sent = $stmt->fetchAll();

    // Recipient options (all users except self). You may filter to roles as needed.
    $recipients = [];
    $stmt = $pdo->prepare('SELECT id, first_name, last_name, role FROM users WHERE id <> ? ORDER BY role, first_name, last_name');
    $stmt->execute([$user_id]);
    $recipients = $stmt->fetchAll();

} catch (PDOException $e) {
    $errors[] = 'Error loading messages: ' . $e->getMessage();
}

include '../components/header.php';
?>

<div class="dashboard-container">
    <?php include '../components/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h1>Messages</h1>
            <p>Send and receive messages with other users.</p>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
            <button type="button" class="btn btn-primary" onclick="if(window.openModalComposeMessage){window.openModalComposeMessage();}else{var m=document.getElementById('composeMessage'); if(m){m.classList.add('show'); document.body.classList.add('modal-open'); m.focus();}}"><i class="fas fa-edit"></i> Compose</button>
        </div>

        <form method="get" action="./messages.php" class="form" style="margin-bottom:16px;">
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label for="q">Search</label>
                    <input type="text" id="q" name="q" placeholder="Search subject or message" value="<?php echo htmlspecialchars($q); ?>">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-secondary"><i class="fas fa-search"></i> Filter</button>
                </div>
            </div>
        </form>

        <?php if (!empty($errors)): ?>
            <div class="notification alert alert-error show" style="margin-bottom:16px;">
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php elseif (!empty($success)): ?>
            <div class="notification alert alert-success show" style="margin-bottom:16px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($view_message): ?>
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-envelope-open"></i> View Message</h3>
                    <div style="display:flex; gap:8px;">
                        <a href="./messages.php" class="btn btn-sm btn-outline">Back to Messages</a>
                        <?php $reply_subject = $view_message['subject'] ? ('Re: ' . $view_message['subject']) : 'Re:'; ?>
                        <a class="btn btn-sm" href="./messages.php?<?php echo http_build_query(['recipient_id'=>$view_message['sender_id'],'subject'=>$reply_subject,'open_compose'=>1,'q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent]); ?>">
                            <i class="fas fa-reply"></i> Reply
                        </a>
                    </div>
                </div>
                <div class="card-content">
                    <div style="margin-bottom:12px;">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($view_message['subject'] ?: '(No subject)'); ?>
                    </div>
                    <div style="margin-bottom:12px; color:#6b7280;">
                        <strong>From:</strong> <?php echo htmlspecialchars(($view_message['sender_first'] . ' ' . $view_message['sender_last']) . ' • ' . date('M j, Y g:i A', strtotime($view_message['sent_at']))); ?>
                    </div>
                    <div style="white-space:pre-wrap; line-height:1.6;">
                        <?php echo nl2br(htmlspecialchars($view_message['message_text'])); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="charts-section" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px; align-items:start;">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-inbox"></i> Inbox</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($inbox)): ?>
                        <p class="no-data">No messages in your inbox.</p>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($inbox as $row): ?>
                                <div class="list-item" style="align-items:center; gap:12px;">
                                    <div style="flex:1 1 auto; min-width:0;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <?php if (!$row['is_read']): ?><span class="status-badge status-active">New</span><?php endif; ?>
                                            <strong style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block; max-width:70%;">
                                                <?php echo htmlspecialchars($row['subject'] ?: '(No subject)'); ?>
                                            </strong>
                                        </div>
                                        <div style="color:#6b7280; font-size:12px;">
                                            From: <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?> • <?php echo date('M j, Y g:i A', strtotime($row['sent_at'])); ?>
                                        </div>
                                        <div style="color:#6b7280; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?php echo htmlspecialchars($row['message_text']); ?>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:6px;">
                                        <a class="btn btn-sm btn-outline" href="./messages.php?view=<?php echo (int)$row['id']; ?>">View</a>
                                        <?php if (!$row['is_read']): ?>
                                        <form method="post" action="./messages.php<?php echo $q!==''?('?'.http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent])):''; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="message_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" class="btn btn-sm"><i class="fas fa-envelope-open"></i> Read</button>
                                        </form>
                                        <?php else: ?>
                                        <form method="post" action="./messages.php<?php echo $q!==''?('?'.http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent])):''; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="mark_unread">
                                            <input type="hidden" name="message_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" class="btn btn-sm"><i class="fas fa-envelope"></i> Unread</button>
                                        </form>
                                        <?php endif; ?>
                                        <form method="post" action="./messages.php<?php echo $q!==''?('?'.http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent])):''; ?>" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination" style="display:flex; justify-content:space-between; margin-top:8px;">
                            <div>Page <?php echo (int)$pageInbox; ?> of <?php echo (int)$inboxPages; ?> (<?php echo (int)$inboxTotal; ?> total)</div>
                            <div style="display:flex; gap:8px;">
                                <?php if ($pageInbox > 1): ?>
                                    <a class="btn btn-sm" href="./messages.php?<?php echo http_build_query(['q'=>$q,'pi'=>$pageInbox-1,'ps'=>$pageSent]); ?>">Prev</a>
                                <?php endif; ?>
                                <?php if ($pageInbox < $inboxPages): ?>
                                    <a class="btn btn-sm" href="./messages.php?<?php echo http_build_query(['q'=>$q,'pi'=>$pageInbox+1,'ps'=>$pageSent]); ?>">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-paper-plane"></i> Sent</h3>
                </div>
                <div class="card-content">
                    <?php if (empty($sent)): ?>
                        <p class="no-data">No sent messages.</p>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach ($sent as $row): ?>
                                <div class="list-item" style="align-items:center; gap:12px;">
                                    <div style="flex:1 1 auto; min-width:0;">
                                        <strong style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block; max-width:70%;">
                                            <?php echo htmlspecialchars($row['subject'] ?: '(No subject)'); ?>
                                        </strong>
                                        <div style="color:#6b7280; font-size:12px;">
                                            To: <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?> • <?php echo date('M j, Y g:i A', strtotime($row['sent_at'])); ?>
                                        </div>
                                        <div style="color:#6b7280; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                            <?php echo htmlspecialchars($row['message_text']); ?>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:6px;">
                                        <a class="btn btn-sm btn-outline" href="./messages.php?view=<?php echo (int)$row['id']; ?>">View</a>
                                        <form method="post" action="./messages.php<?php echo $q!==''?('?'.http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent])):''; ?>" onsubmit="return confirm('Delete this message?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="message_id" value="<?php echo (int)$row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="pagination" style="display:flex; justify-content:space-between; margin-top:8px;">
                            <div>Page <?php echo (int)$pageSent; ?> of <?php echo (int)$sentPages; ?> (<?php echo (int)$sentTotal; ?> total)</div>
                            <div style="display:flex; gap:8px;">
                                <?php if ($pageSent > 1): ?>
                                    <a class="btn btn-sm" href="./messages.php?<?php echo http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent-1]); ?>">Prev</a>
                                <?php endif; ?>
                                <?php if ($pageSent < $sentPages): ?>
                                    <a class="btn btn-sm" href="./messages.php?<?php echo http_build_query(['q'=>$q,'pi'=>$pageInbox,'ps'=>$pageSent+1]); ?>">Next</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        

    </main>
</div>

<?php
    // Compose Modal rendering
    require_once __DIR__ . '/../components/modal.php';

    // Build the compose form content using output buffering to include dynamic PHP
    ob_start();
?>
    <form method="post" action="./messages.php" class="form" style="max-width:720px;" id="composeMessageForm">
        <input type="hidden" name="action" value="send">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
        <div class="form-row">
            <div class="form-group">
                <label for="recipient_id">Recipient</label>
                <select id="recipient_id" name="recipient_id" required>
                    <option value="">-- Select recipient --</option>
                    <?php foreach ($recipients as $u): ?>
                        <option value="<?php echo (int)$u['id']; ?>" <?php echo (isset($recipient_id) && (int)$recipient_id === (int)$u['id']) ? 'selected' : (($prefill_recipient_id && (int)$prefill_recipient_id === (int)$u['id']) ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars(ucfirst($u['role']) . ' • ' . $u['first_name'] . ' ' . $u['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" placeholder="Subject (optional)" value="<?php echo isset($subject) ? htmlspecialchars($subject) : htmlspecialchars($prefill_subject); ?>" maxlength="255">
            </div>
        </div>
        <div class="form-group">
            <label for="message_text">Message</label>
            <textarea id="message_text" name="message_text" required placeholder="Write your message..." rows="4"><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
        </div>
    </form>
<?php
    $composeFormHtml = ob_get_clean();
    renderFormModal('composeMessage', 'Compose Message', $composeFormHtml, 'Send', 'Cancel', [
        'size' => 'large',
        'formId' => 'composeMessageForm',
        'submitClass' => 'btn-primary',
        'cancelClass' => 'btn-outline'
    ]);
?>

<script>
  (function(){
    function tryOpenCompose(){
      try {
        const params = new URLSearchParams(window.location.search);
        if (params.get('open_compose') === '1') {
          if (typeof window.openModalComposeMessage === 'function') {
            window.openModalComposeMessage();
            return true;
          }
        }
      } catch(e) {}
      return false;
    }
    function autoOpenCompose(){
      if (tryOpenCompose()) return;
      // Retry a few times in case modal init script hasn't executed yet
      let attempts = 0;
      const maxAttempts = 30;
      const interval = setInterval(function(){
        attempts++;
        if (tryOpenCompose() || attempts >= maxAttempts) {
          clearInterval(interval);
        }
      }, 150);
    }
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', autoOpenCompose);
    } else {
      autoOpenCompose();
    }
  })();
</script>

<?php include '../components/footer.php'; ?>
