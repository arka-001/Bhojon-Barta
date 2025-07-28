<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BhojonSathi Chatbot</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    /* --- UI is unchanged from the previous version --- */
    :root {
      --primary-color: #ff8c00; 
      --primary-hover: #e67e22;
      --primary-gradient: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
      --bg-light: #fdfaf6;
      --bg-chat: #ffffff;
      --text-dark: #2c3e50;
      --text-light: #95a5a6;
      --user-msg-bg: #fff3e0;
      --bot-msg-bg: #f4f6f8;
      --border-color: #ecf0f1;
      --shadow-color: rgba(44, 62, 80, 0.15);
      --danger-color: #e74c3c;
    }
    body { font-family: 'Poppins', sans-serif; margin: 0; background: var(--bg-light); }
    #chat-toggle { position: fixed; bottom: 30px; right: 30px; width: 65px; height: 65px; background: var(--primary-gradient); color: white; border-radius: 50%; border: none; cursor: pointer; box-shadow: 0 8px 20px var(--shadow-color); display: flex; justify-content: center; align-items: center; z-index: 1000; transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s ease; }
    #chat-toggle:hover { transform: scale(1.1); box-shadow: 0 12px 25px var(--shadow-color); }
    #chat-toggle svg { width: 32px; height: 32px; }
    #chat-container { position: fixed; bottom: 110px; right: 30px; width: 100%; max-width: 420px; height: 75vh; max-height: 650px; background: var(--bg-chat); border-radius: 20px; box-shadow: 0 10px 40px -10px var(--shadow-color); display: flex; flex-direction: column; overflow: hidden; z-index: 999; transform-origin: bottom right; transform: scale(0); opacity: 0; transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.2s ease-out; }
    #chat-container.open { transform: scale(1); opacity: 1; }
    #chat-header { background: var(--primary-gradient); color: white; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; box-shadow: 0 4px 10px rgba(0,0,0,0.05); z-index: 10; }
    #chat-header h2 { margin: 0; font-size: 1.25em; font-weight: 600; }
    #chat-header-icons { display: flex; align-items: center; gap: 8px; }
    .header-btn { background: rgba(255,255,255,0.1); border: none; border-radius: 50%; color: white; cursor: pointer; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transition: background-color 0.2s; }
    .header-btn:hover { background: rgba(255,255,255,0.2); }
    .header-btn svg { width: 20px; height: 20px; }
    .header-btn.active { background: rgba(255, 255, 255, 0.3); }
    #messages { flex-grow: 1; padding: 1.5rem; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; }
    .message-wrapper { display: flex; flex-direction: column; max-width: 85%; }
    .message { padding: 12px 18px; border-radius: 18px; word-wrap: break-word; line-height: 1.5; font-size: 0.95em; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
    .user { align-self: flex-end; }
    .user .message { background: var(--user-msg-bg); color: var(--text-dark); border-bottom-right-radius: 4px; }
    .bot { align-self: flex-start; }
    .bot .message { background: var(--bot-msg-bg); color: var(--text-dark); border-bottom-left-radius: 4px; }
    .message-time { font-size: 0.75em; color: var(--text-light); margin-top: 6px; padding: 0 8px; }
    .user .message-time { text-align: right; }
    .bot .message-time { text-align: left; }
    .message-speaker-btn { background: none; border: none; cursor: pointer; padding: 4px 8px 0 8px; margin: 0; opacity: 0.5; transition: opacity 0.2s; }
    .message-speaker-btn:hover { opacity: 1; }
    .message-speaker-btn svg { width: 16px; height: 16px; fill: var(--text-light); }
    .bot .message-wrapper-inner { display: flex; align-items: flex-end; gap: 8px; }
    .typing-indicator { display: flex; align-items: center; padding: 18px !important; }
    .typing-indicator span { height: 9px; width: 9px; background-color: var(--text-light); border-radius: 50%; display: inline-block; margin: 0 2px; animation: bounce 1.4s infinite ease-in-out both; }
    .typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
    .typing-indicator span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1.0); } }
    .starter-prompts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
    .prompt-btn { background: var(--bg-chat); border: 1px solid var(--border-color); border-radius: 20px; padding: 8px 15px; text-align: left; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 0.85em; color: var(--primary-color); font-weight: 500; transition: all 0.2s ease; }
    .prompt-btn:hover { background-color: var(--primary-color); border-color: var(--primary-color); color: white; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
    #chat-input-area { display: flex; align-items: center; padding: 1rem 1.25rem; background: var(--bg-chat); border-top: 1px solid var(--border-color); flex-shrink: 0; gap: 10px; }
    #userInput { flex-grow: 1; border: 1px solid var(--border-color); background-color: #f9fafb; padding: 12px 20px; border-radius: 25px; font-size: 1em; outline: none; transition: border-color 0.2s, box-shadow 0.2s; font-family: 'Poppins', sans-serif; }
    #userInput:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px var(--user-msg-bg); }
    .input-btn { background: var(--primary-color); border: none; border-radius: 50%; min-width: 48px; height: 48px; cursor: pointer; display: flex; justify-content: center; align-items: center; transition: background-color 0.2s, transform 0.2s; }
    .input-btn:hover { background: var(--primary-hover); transform: scale(1.1); }
    .input-btn svg { width: 24px; height: 24px; fill: white; }
    #voice-btn.listening { background: var(--danger-color); animation: pulse 1.5s infinite; }
    @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(231, 76, 60, 0); } 100% { box-shadow: 0 0 0 0 rgba(231, 76, 60, 0); } }
  </style>
</head>
<body>
  
  <button id="chat-toggle">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18z"/></svg>
  </button>

  <div id="chat-container">
    <div id="chat-header">
      <h2>🍲 BhojonSathi</h2>
      <div id="chat-header-icons">
        <button id="tts-toggle-btn" class="header-btn" title="Toggle Voice">
          <svg id="tts-icon-on" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>
          <svg id="tts-icon-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="display:none;"><path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"></path></svg>
        </button>
        <button id="close-chat" class="header-btn" title="Close Chat">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
      </div>
    </div>
    <div id="messages"></div>
    <div id="chat-input-area">
      <input type="text" id="userInput" placeholder="Ask or press the mic...">
      <button id="voice-btn" class="input-btn" title="Speak">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 14c1.66 0 2.99-1.34 2.99-3L15 5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3zm5.3-3c0 3-2.54 5.1-5.3 5.1S6.7 14 6.7 11H5c0 3.41 2.72 6.23 6 6.72V21h2v-3.28c3.28-.49 6-3.31 6-6.72h-1.7z"/></svg>
      </button>
      <button id="send-btn" class="input-btn" title="Send">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M3.4 20.4l17.45-7.48c.81-.35.81-1.49 0-1.84L3.4 3.6c-.66-.29-1.39.2-1.39.91L2 9.12c0 .5.37.93.87.99L17 12 2.87 13.88c-.5.07-.87.5-.87 1l-.01 4.61c0 .71.73 1.2 1.39.91z"/></svg>
      </button>
    </div>
  </div>

  <script>
    const chatContainer = document.getElementById('chat-container');
    const chatToggle = document.getElementById('chat-toggle');
    const closeChatBtn = document.getElementById('close-chat');
    const userInput = document.getElementById('userInput');
    const messagesContainer = document.getElementById('messages');
    const sendBtn = document.getElementById('send-btn');
    const voiceBtn = document.getElementById('voice-btn');
    const ttsToggleBtn = document.getElementById('tts-toggle-btn');
    const ttsIconOn = document.getElementById('tts-icon-on');
    const ttsIconOff = document.getElementById('tts-icon-off');
    
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    const isSpeechSupported = !!SpeechRecognition;
    let recognition;
    let isListening = false;
    let isTtsEnabled = false;
    
    // NEW: Variable to store the selected female voice
    let selectedVoice = null;

    if (isSpeechSupported) {
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.lang = 'en-US';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
    } else {
        voiceBtn.style.display = 'none';
        console.warn("Speech Recognition not supported.");
    }

    if (!('speechSynthesis' in window)) {
        ttsToggleBtn.style.display = 'none';
        console.warn("Text-to-Speech not supported.");
    } else {
        // NEW: Add event listener to load voices when they are ready
        speechSynthesis.onvoiceschanged = () => loadVoices();
    }
    
    // --- Event Listeners ---
    chatToggle.addEventListener('click', () => {
      chatContainer.classList.toggle('open');
      if (chatContainer.classList.contains('open')) userInput.focus();
    });
    closeChatBtn.addEventListener('click', () => { chatContainer.classList.remove('open'); stopSpeaking(); });
    sendBtn.addEventListener('click', () => sendMessage());
    userInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });
    if (isSpeechSupported) {
        voiceBtn.addEventListener('click', toggleListen);
        recognition.onstart = () => { isListening = true; voiceBtn.classList.add('listening'); };
        recognition.onend = () => { isListening = false; voiceBtn.classList.remove('listening'); };
        recognition.onresult = (event) => {
            const transcript = event.results[event.results.length - 1][0].transcript.trim();
            if (transcript) sendMessage(transcript);
        };
        recognition.onerror = (event) => { console.error('Speech recognition error:', event.error); };
    }
    ttsToggleBtn.addEventListener('click', toggleTts);

    // --- Core Functions ---
    async function sendMessage(text = null) {
      const message = text || userInput.value.trim();
      if (!message) return;
      stopSpeaking();
      addMessage(message, 'user');
      if (!text) userInput.value = '';
      showTypingIndicator();

      try {
        const response = await fetch('chat_handler.php', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ question: message })
        });
        if (!response.ok) throw new Error(`Server error: ${response.status}`);
        const data = await response.json();
        removeTypingIndicator();
        addMessage(data.reply, 'bot');
      } catch (error) {
        removeTypingIndicator();
        addMessage('⚠️ Oops! I seem to be having trouble connecting. Please try again.', 'bot');
        console.error('Fetch Error:', error);
      }
    }

    function addMessage(text, type) {
        const wrapper = document.createElement('div');
        wrapper.className = `message-wrapper ${type}`;
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.innerHTML = text;
        const timeDiv = document.createElement('div');
        timeDiv.className = 'message-time';
        timeDiv.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        if (type === 'user') {
            wrapper.appendChild(messageDiv);
            wrapper.appendChild(timeDiv);
        } else { // Bot message
            const innerWrapper = document.createElement('div');
            innerWrapper.className = 'message-wrapper-inner';
            
            const speakerBtn = document.createElement('button');
            speakerBtn.className = 'message-speaker-btn';
            speakerBtn.title = 'Read aloud';
            speakerBtn.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"></path></svg>`;
            const rawText = messageDiv.textContent || messageDiv.innerText;
            speakerBtn.onclick = () => speak(rawText);
            
            innerWrapper.appendChild(messageDiv);
            innerWrapper.appendChild(speakerBtn);
            
            wrapper.appendChild(innerWrapper);
            wrapper.appendChild(timeDiv);
        }
        
        messagesContainer.appendChild(wrapper);
        scrollToBottom();

        if (type === 'bot' && isTtsEnabled) {
            const rawText = messageDiv.textContent || messageDiv.innerText;
            speak(rawText);
        }
    }
    
    function showTypingIndicator() {
      if (document.getElementById('typing-indicator')) return;
      const wrapper = document.createElement('div');
      wrapper.id = 'typing-indicator';
      wrapper.className = 'message-wrapper bot';
      wrapper.innerHTML = `<div class="message typing-indicator"><span></span><span></span><span></span></div>`;
      messagesContainer.appendChild(wrapper);
      scrollToBottom();
    }

    function removeTypingIndicator() {
      const indicator = document.getElementById('typing-indicator');
      if (indicator) indicator.remove();
    }

    function scrollToBottom() {
      messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    function showInitialMessage() {
        const initialText = "Hello! How can I help you today?";
        const prompts = [ "What's on special?", "Check my recent orders", "Are there any coupons?" ];
        let promptsHTML = '<div class="starter-prompts">';
        prompts.forEach(p => {
            const escapedPrompt = p.replace(/'/g, "\\'");
            promptsHTML += `<button class="prompt-btn" onclick="sendMessage('${escapedPrompt}')">${p}</button>`;
        });
        promptsHTML += '</div>';
        addMessage(initialText + promptsHTML, 'bot');
    }

    // --- Voice Functions ---
    function toggleListen() {
        if (isListening) {
            recognition.stop();
        } else {
            stopSpeaking();
            recognition.start();
        }
    }
    
    // NEW: Function to find and set the desired voice
    function loadVoices() {
        const voices = speechSynthesis.getVoices();
        if (voices.length === 0) return;

        // Priority list of female voice names
        const preferredVoices = [
            'Google US English', // High quality on Chrome
            'Samantha',          // Default on macOS/iOS
            'Microsoft Zira Desktop - English (United States)', // Default on Windows
            'Google UK English Female',
        ];

        // 1. Try to find a preferred voice
        selectedVoice = voices.find(voice => preferredVoices.includes(voice.name));
        
        // 2. If not found, try to find any English female voice
        if (!selectedVoice) {
            selectedVoice = voices.find(voice => voice.lang.startsWith('en-') && voice.name.toLowerCase().includes('female'));
        }

        // 3. If still not found, log it. The browser will use its default.
        if (selectedVoice) {
            console.log(`Female voice selected: ${selectedVoice.name}`);
        } else {
            console.warn("Could not find a preferred female voice. Using browser default.");
        }
    }

    function speak(text) {
        if (!('speechSynthesis' in window) || !text) return;
        stopSpeaking();
        const utterance = new SpeechSynthesisUtterance(text);
        
        // NEW: Set the selected voice on the utterance
        if (selectedVoice) {
            utterance.voice = selectedVoice;
        }
        
        utterance.lang = 'en-US'; // Fallback language
        speechSynthesis.speak(utterance);
    }
    
    function stopSpeaking() {
        if ('speechSynthesis' in window && speechSynthesis.speaking) {
            speechSynthesis.cancel();
        }
    }

    function toggleTts() {
        isTtsEnabled = !isTtsEnabled;
        ttsToggleBtn.classList.toggle('active', isTtsEnabled);
        ttsIconOn.style.display = isTtsEnabled ? 'block' : 'none';
        ttsIconOff.style.display = isTtsEnabled ? 'none' : 'block';
        if (!isTtsEnabled) stopSpeaking();
    }

    // Initialize
    loadVoices(); // Try to load voices immediately
    showInitialMessage();
    ttsIconOn.style.display = 'none';
    ttsIconOff.style.display = 'block';

  </script>
</body>
</html>