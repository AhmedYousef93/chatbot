<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شات وحدات سكنية</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Tahoma', sans-serif;
            background: #f0f2f5;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        h2 {
            color: #333;
        }

        #chat-box {
            background: white;
            padding: 15px;
            border-radius: 10px;
            height: 400px;
            width: 100%;
            max-width: 600px;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
        }

        .message {
            margin-bottom: 12px;
            padding: 10px 15px;
            border-radius: 12px;
            max-width: 80%;
            line-height: 1.5;
        }

        .user {
            background-color: #d0e6ff;
            align-self: flex-end;
            text-align: right;
            margin-left: auto;
        }

        .bot {
            background-color: #e3f7df;
            align-self: flex-start;
            text-align: left;
            margin-right: auto;
        }

        #chat-form {
            display: flex;
            justify-content: center;
            width: 100%;
            max-width: 600px;
        }

        #message {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin-left: 10px;
        }

        button {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Spinner */
        .spinner {
            border: 4px solid #eee;
            border-top: 4px solid #4CAF50;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

    </style>
</head>
<body>

<h2>شات الوحدات السكنية</h2>
<div id="chat-box"></div>

<form id="chat-form">
    <input type="text" id="message" placeholder="اكتب سؤالك هنا..." required>
    <button type="submit">إرسال</button>
</form>

<script>
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message');

    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userMsg = messageInput.value;

        appendMessage(userMsg, 'user');
        messageInput.value = '';

        // Spinner لودينج
        const loadingDiv = document.createElement('div');
        loadingDiv.classList.add('message', 'bot');
        loadingDiv.innerHTML = `<span class="spinner"></span>جاري البحث عن أفضل الوحدات...`;
        chatBox.appendChild(loadingDiv);
        chatBox.scrollTop = chatBox.scrollHeight;

        const res = await fetch("/api/chat", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({message: userMsg})
        });

        loadingDiv.remove();

        const data = await res.json();
        if (Array.isArray(data.reply)) {
            let formatted = data.reply.map(unit => {
                return `📌 ${unit.name}\n📍 الموقع: ${unit.location}\n💰 السعر: ${unit.price} ريال\n🛏 الغرف: ${unit.rooms}`;
            }).join('\n\n');
            appendMessage(formatted, 'bot');
        } else {
            appendMessage(data.reply, 'bot');
        }
    });

    function appendMessage(text, sender) {
        const div = document.createElement('div');
        div.classList.add('message', sender);
        div.innerText = text;
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
</script>

</body>
</html>
