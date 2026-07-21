(function(){
  const chatbox = document.getElementById('chatbox');
  const form = document.getElementById('msgForm');
  const userName = document.getElementById('user_name').value;
  
  let lastMessageCount = 0;

  function fetchMsgs(){
    fetch('/meras/chat/fetch_messages.php')
      .then(r => r.json())
      .then(data => {
        if (!data || !Array.isArray(data)) return;
        
        const html = data.map(m => {
          const isSelf = m.user_name === userName;
          const selfClass = isSelf ? 'self' : 'other';
          const initial = m.user_name ? m.user_name.substring(0, 1).toUpperCase() : '?';
          const avatarColor = getAvatarColor(m.user_name);
          const formattedTime = formatTime(m.created_at);
          
          return `
            <div class="chat-msg-row ${selfClass}">
              <div class="chat-bubble-container">
                <div class="user-avatar-circle" style="width: 30px; height: 30px; font-size: 0.75rem; flex-shrink: 0; background-color: ${avatarColor}; font-weight: 700; color: white; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                  ${initial}
                </div>
                <div class="chat-msg-meta">
                  <div class="chat-msg-sender">${escapeHtml(m.user_name)}</div>
                  <div class="chat-bubble">
                    ${escapeHtml(m.message)}
                  </div>
                  <div class="chat-msg-time">${formattedTime}</div>
                </div>
              </div>
            </div>
          `;
        }).join('');
        
        chatbox.innerHTML = html;
        
        // Auto scroll to bottom only if new messages have arrived
        if (data.length !== lastMessageCount) {
          chatbox.scrollTop = chatbox.scrollHeight;
          lastMessageCount = data.length;
        }
      })
      .catch(err => {
        console.error('Error fetching messages:', err);
      });
  }

  function getAvatarColor(name) {
    if (!name) return '#64748b';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
      hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }
    const colors = ['#4f46e5', '#0d9488', '#0891b2', '#0284c7', '#2563eb', '#7c3aed', '#db2777', '#ea580c', '#16a34a'];
    const idx = Math.abs(hash) % colors.length;
    return colors[idx];
  }

  function formatTime(dateTimeStr) {
    if (!dateTimeStr) return '';
    try {
      const parts = dateTimeStr.split(' ');
      if (parts.length >= 2) {
        const timeParts = parts[1].split(':');
        let hours = parseInt(timeParts[0]);
        const minutes = timeParts[1];
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        return `${hours}:${minutes} ${ampm}`;
      }
    } catch(e) {}
    return dateTimeStr;
  }

  function escapeHtml(s) { 
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;'); 
  }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    const input = document.getElementById('message');
    const msg = input.value.trim();
    if (!msg) return;
    
    const fd = new FormData(); 
    fd.append('user_name', userName); 
    fd.append('message', msg);
    
    // Clear input instantly for snappy feel
    input.value = '';
    
    fetch('/meras/chat/save_message.php', { method: 'POST', body: fd })
      .then(() => { 
        fetchMsgs(); 
      });
  });

  fetchMsgs();
  setInterval(fetchMsgs, 2500);
})();
