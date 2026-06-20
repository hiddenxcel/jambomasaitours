<?php
/**
 * AI Chatbot Widget — Jambo Masai Tours Assistant
 * Include once near the end of <body> on any public page (after functions.php is loaded).
 * Posts to ajax/chat.php (DeepSeek). Dark + emerald safari theme.
 */
$_jmtChatCsrf = generateCsrfToken();
?>
<!-- ════════ AI CHATBOT WIDGET ════════ -->
<div id="jmt-chat">

  <!-- Toggler button -->
  <button id="jmt-chat-toggler" aria-label="Open chat assistant">
    <i class="fas fa-robot open-ico"></i>
    <i class="fas fa-chevron-down close-ico"></i>
  </button>

  <!-- Popup -->
  <div class="jmt-chat-popup" role="dialog" aria-label="Jambo Masai Tours Assistant">

    <!-- Header -->
    <div class="jmt-chat-header">
      <div class="jmt-chat-hinfo">
        <div class="jmt-chat-logo"><i class="fas fa-robot"></i></div>
        <div>
          <h3>Safari Assistant</h3>
          <span class="jmt-chat-status"><span class="dot"></span> Online</span>
        </div>
      </div>
      <div class="jmt-chat-hactions">
        <button id="jmt-chat-clear" title="Clear chat" aria-label="Clear chat"><i class="fas fa-rotate-left"></i></button>
        <button id="jmt-chat-close" title="Close" aria-label="Close chat"><i class="fas fa-xmark"></i></button>
      </div>
    </div>

    <!-- Body -->
    <div class="jmt-chat-body" id="jmt-chat-body">
      <div class="jmt-msg bot">
        <div class="jmt-avatar"><i class="fas fa-robot"></i></div>
        <div class="jmt-bubble">
          <span class="jmt-bot-name">Safari Assistant</span>
          <div class="jmt-text">Jambo! 👋 Welcome to <strong>Jambo Masai Tours</strong>. I can help you plan a safari, suggest tours, or answer questions about Tanzania. How can I help?</div>
        </div>
      </div>
      <!-- Quick replies -->
      <div class="jmt-quick" id="jmt-quick">
        <button data-q="What safari tours do you offer?">🦁 Our safari tours</button>
        <button data-q="How much is a Serengeti safari?">💰 Pricing</button>
        <button data-q="When is the best time to visit Tanzania?">📅 Best time to visit</button>
        <button data-q="I want to book a safari">✈️ Book a trip</button>
      </div>
    </div>

    <!-- Footer / input -->
    <div class="jmt-chat-footer">
      <form id="jmt-chat-form" autocomplete="off">
        <input type="hidden" id="jmt-chat-csrf" value="<?= e($_jmtChatCsrf) ?>">
        <textarea id="jmt-chat-input" placeholder="Ask me anything…" rows="1" required></textarea>
        <button type="submit" id="jmt-chat-send" aria-label="Send"><i class="fas fa-paper-plane"></i></button>
      </form>
    </div>

  </div>
</div>

<style>
#jmt-chat *{box-sizing:border-box}
/* lift the back-to-top button so it doesn't overlap the chat toggler */
#back-top{bottom:5.5rem !important}

#jmt-chat-toggler{
  position:fixed;bottom:1.5rem;right:1.5rem;z-index:9997;width:56px;height:56px;border-radius:50%;
  background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;border:none;cursor:pointer;
  display:flex;align-items:center;justify-content:center;font-size:1.4rem;
  box-shadow:0 8px 30px rgba(160,94,34,.45);transition:transform .3s cubic-bezier(.34,1.56,.64,1)}
#jmt-chat-toggler:hover{transform:scale(1.08)}
#jmt-chat-toggler .close-ico{display:none}
#jmt-chat-toggler::before{content:'';position:absolute;inset:0;border-radius:50%;background:#a05e22;animation:jmtPulse 2.4s ease-in-out infinite;z-index:-1}
@keyframes jmtPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.4);opacity:0}}
body.jmt-chat-open #jmt-chat-toggler .open-ico{display:none}
body.jmt-chat-open #jmt-chat-toggler .close-ico{display:block}
body.jmt-chat-open #jmt-chat-toggler::before{display:none}

.jmt-chat-popup{
  position:fixed;bottom:1.5rem;right:1.5rem;z-index:9998;width:380px;max-width:calc(100vw - 2rem);
  height:580px;max-height:calc(100vh - 3rem);
  /* Glassmorphism — translucent forest-green tint + blur */
  background:linear-gradient(160deg,rgba(59,92,81,.55),rgba(35,54,47,.65));
  backdrop-filter:blur(22px) saturate(1.3);-webkit-backdrop-filter:blur(22px) saturate(1.3);
  border:1px solid rgba(255,255,255,.18);border-radius:22px;overflow:hidden;
  display:flex;flex-direction:column;
  box-shadow:0 30px 80px rgba(0,0,0,.45),inset 0 1px 0 rgba(255,255,255,.18);
  opacity:0;transform:translateY(24px) scale(.96);pointer-events:none;transform-origin:bottom right;
  transition:opacity .3s cubic-bezier(.16,1,.3,1),transform .3s cubic-bezier(.16,1,.3,1)}
body.jmt-chat-open .jmt-chat-popup{opacity:1;transform:translateY(0) scale(1);pointer-events:all}

.jmt-chat-header{display:flex;align-items:center;justify-content:space-between;gap:.5rem;
  padding:.9rem 1rem;background:rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.12)}
.jmt-chat-hinfo{display:flex;align-items:center;gap:.7rem}
.jmt-chat-logo{width:38px;height:38px;border-radius:50%;background:rgba(160,94,34,.18);
  display:flex;align-items:center;justify-content:center;color:#c17a3a;font-size:1rem;flex-shrink:0}
.jmt-chat-header h3{margin:0;font-family:'Montserrat',sans-serif;font-size:.92rem;font-weight:700;color:#fff;line-height:1.2}
.jmt-chat-status{display:flex;align-items:center;gap:.35rem;font-size:.66rem;color:rgba(255,255,255,.55);font-family:'Inter',sans-serif}
.jmt-chat-status .dot{width:7px;height:7px;border-radius:50%;background:#c17a3a;box-shadow:0 0 6px #c17a3a;animation:jmtBlink 2s infinite}
@keyframes jmtBlink{0%,100%{opacity:1}50%{opacity:.4}}
.jmt-chat-hactions{display:flex;gap:.25rem}
.jmt-chat-hactions button{width:32px;height:32px;border-radius:8px;background:transparent;border:none;color:rgba(255,255,255,.6);
  cursor:pointer;font-size:.95rem;display:flex;align-items:center;justify-content:center;transition:all .2s}
.jmt-chat-hactions button:hover{background:rgba(255,255,255,.1);color:#fff}

.jmt-chat-body{flex:1;overflow-y:auto;padding:1.1rem;display:flex;flex-direction:column;gap:1rem;
  scrollbar-width:thin;scrollbar-color:#a05e22 transparent}
.jmt-chat-body::-webkit-scrollbar{width:5px}
.jmt-chat-body::-webkit-scrollbar-thumb{background:rgba(160,94,34,.4);border-radius:3px}

.jmt-msg{display:flex;gap:.6rem;align-items:flex-end;max-width:90%}
.jmt-msg.user{align-self:flex-end;flex-direction:row-reverse}
.jmt-avatar{width:30px;height:30px;border-radius:50%;background:rgba(160,94,34,.16);color:#c17a3a;
  display:flex;align-items:center;justify-content:center;font-size:.72rem;flex-shrink:0;margin-bottom:2px}
.jmt-bubble{display:flex;flex-direction:column}
.jmt-bot-name{font-family:'Montserrat',sans-serif;font-size:.56rem;font-weight:700;letter-spacing:.12em;
  text-transform:uppercase;color:#c17a3a;margin-bottom:.3rem}
.jmt-text{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);color:#f3f6f3;
  padding:.7rem .9rem;border-radius:4px 14px 14px 14px;font-family:'Inter',sans-serif;font-size:.85rem;line-height:1.6;white-space:pre-wrap;word-break:break-word}
.jmt-msg.user .jmt-text{background:linear-gradient(135deg,#a05e22,#7d4817);color:#fff;border:none;border-radius:14px 4px 14px 14px}
.jmt-msg.user .jmt-avatar{background:rgba(255,255,255,.1);color:#9ca3af}

.jmt-msg.thinking .jmt-text{display:flex;gap:5px;align-items:center;padding:.85rem .9rem}
.jmt-dot{width:7px;height:7px;border-radius:50%;background:#c17a3a;animation:jmtTyping 1.2s infinite ease-in-out}
.jmt-dot:nth-child(2){animation-delay:.2s}
.jmt-dot:nth-child(3){animation-delay:.4s}
@keyframes jmtTyping{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-5px);opacity:1}}

.jmt-quick{display:flex;flex-wrap:wrap;gap:.45rem;margin-top:.2rem;padding-left:2.2rem}
.jmt-quick button{background:rgba(160,94,34,.08);border:1px solid rgba(160,94,34,.25);color:#e6c39b;
  font-family:'Inter',sans-serif;font-size:.74rem;padding:.45rem .75rem;border-radius:20px;cursor:pointer;transition:all .2s}
.jmt-quick button:hover{background:rgba(160,94,34,.18);color:#fff}

.jmt-chat-footer{padding:.7rem .8rem;border-top:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05)}
#jmt-chat-form{display:flex;align-items:flex-end;gap:.5rem;background:rgba(255,255,255,.1);
  border:1px solid rgba(255,255,255,.18);border-radius:24px;padding:.35rem .35rem .35rem 1rem;transition:border-color .2s}
#jmt-chat-form:focus-within{border-color:rgba(160,94,34,.5)}
#jmt-chat-input{flex:1;background:transparent;border:none;outline:none;resize:none;color:#e5e7eb;
  font-family:'Inter',sans-serif;font-size:.85rem;line-height:1.5;max-height:90px;padding:.45rem 0}
#jmt-chat-input::placeholder{color:rgba(255,255,255,.3)}
#jmt-chat-send{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#a05e22,#7d4817);
  color:#fff;border:none;cursor:pointer;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:transform .2s}
#jmt-chat-send:hover{transform:scale(1.08)}
#jmt-chat-send:disabled{opacity:.5;cursor:not-allowed;transform:none}

@media(max-width:480px){
  .jmt-chat-popup{width:100vw;height:100dvh;max-height:100dvh;max-width:100vw;bottom:0;right:0;border-radius:0}
  #jmt-chat-toggler{bottom:1rem;right:1rem}
}
</style>

<script>
(function(){
  const body    = document.body;
  const toggler = document.getElementById('jmt-chat-toggler');
  const closeB  = document.getElementById('jmt-chat-close');
  const clearB  = document.getElementById('jmt-chat-clear');
  const chatBody= document.getElementById('jmt-chat-body');
  const form    = document.getElementById('jmt-chat-form');
  const input   = document.getElementById('jmt-chat-input');
  const sendB   = document.getElementById('jmt-chat-send');
  const csrf    = document.getElementById('jmt-chat-csrf').value;
  const base    = (window.SITE_URL || '');
  const welcome = chatBody.innerHTML;
  let history   = [];   // [{role, content}]
  let busy      = false;

  const esc = s => s.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function addUser(text){
    const d = document.createElement('div');
    d.className = 'jmt-msg user';
    d.innerHTML = '<div class="jmt-avatar"><i class="fas fa-user"></i></div><div class="jmt-bubble"><div class="jmt-text">'+esc(text)+'</div></div>';
    chatBody.appendChild(d); scrollDown();
  }
  function addThinking(){
    const d = document.createElement('div');
    d.className = 'jmt-msg bot thinking';
    d.innerHTML = '<div class="jmt-avatar"><i class="fas fa-robot"></i></div><div class="jmt-bubble"><span class="jmt-bot-name">Safari Assistant</span><div class="jmt-text"><span class="jmt-dot"></span><span class="jmt-dot"></span><span class="jmt-dot"></span></div></div>';
    chatBody.appendChild(d); scrollDown();
    return d;
  }
  function scrollDown(){ chatBody.scrollTo({top:chatBody.scrollHeight,behavior:'smooth'}); }

  async function send(text){
    if (busy || !text.trim()) return;
    busy = true; sendB.disabled = true;
    // remove quick replies after first interaction
    const q = document.getElementById('jmt-quick'); if (q) q.remove();
    addUser(text);
    history.push({role:'user', content:text});
    const thinking = addThinking();

    try {
      const fd = new URLSearchParams();
      fd.append('<?= CSRF_TOKEN_NAME ?>', csrf);
      fd.append('message', text);
      fd.append('history', JSON.stringify(history.slice(0,-1)));
      const res = await fetch(base + '/ajax/chat.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: fd.toString()
      });
      const data = await res.json();
      const reply = (data.reply || 'Sorry, please try again.');
      thinking.classList.remove('thinking');
      thinking.querySelector('.jmt-text').innerHTML = esc(reply).replace(/\n/g,'<br>');
      history.push({role:'assistant', content:reply});
    } catch(err){
      thinking.classList.remove('thinking');
      thinking.querySelector('.jmt-text').innerHTML = 'Network error. Please check your connection and try again.';
    } finally {
      busy = false; sendB.disabled = false; scrollDown();
    }
  }

  // open/close
  toggler.addEventListener('click', () => {
    body.classList.toggle('jmt-chat-open');
    if (body.classList.contains('jmt-chat-open')) setTimeout(()=>input.focus(),300);
  });
  closeB.addEventListener('click', () => body.classList.remove('jmt-chat-open'));
  clearB.addEventListener('click', () => { chatBody.innerHTML = welcome; history = []; bindQuick(); });

  // quick replies
  function bindQuick(){
    chatBody.querySelectorAll('#jmt-quick button').forEach(b => {
      b.addEventListener('click', () => send(b.dataset.q));
    });
  }
  bindQuick();

  // input auto-grow + submit
  input.addEventListener('input', () => { input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,90)+'px'; });
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); }
  });
  form.addEventListener('submit', e => {
    e.preventDefault();
    const t = input.value.trim();
    if (!t) return;
    input.value=''; input.style.height='auto';
    send(t);
  });

  // allow other parts of the site to open the chat: window.openSafariChat('text')
  window.openSafariChat = function(text){
    body.classList.add('jmt-chat-open');
    if (text) { setTimeout(()=>send(text),350); } else { setTimeout(()=>input.focus(),300); }
  };
})();
</script>
<?php
/* Ensure SITE_URL is available to the widget JS even if the booking modal isn't on this page */
echo '<script>window.SITE_URL = window.SITE_URL || "' . SITE_URL . '";</script>';
?>
