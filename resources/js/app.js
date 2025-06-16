import './bootstrap';
import { createApp } from 'vue';
import ChatComponent from './Components/Chat/ChatComponent.vue';

if (document.getElementById('chat-app')) {
    createApp(ChatComponent).mount('#chat-app');
}
