<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-6">Chat</h2>
                <div id="chat-app"></div>
            </div>
        </div>
    </div>
</x-app-layout>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const { createApp } = Vue;
        
        const ChatComponent = {
            template: '#chat-component-template',
            data() {
                return {
                    currentUser: null,
                    users: [],
                    selectedUser: null,
                    group: null,
                    conversations: [],
                    newMessage: '',
                    searchQuery: '',
                }
            },
            
            computed: {
                filteredUsers() {
                    if (!this.searchQuery) return this.users;
                    
                    const query = this.searchQuery.toLowerCase();
                    return this.users.filter(user => 
                        user.name.toLowerCase().includes(query) || 
                        user.email.toLowerCase().includes(query)
                    );
                }
            },
            
            mounted() {
                this.getCurrentUser();
                this.getUsers();
            },
            
            methods: {
                getCurrentUser() {
                    axios.get('/api/user')
                        .then(response => {
                            this.currentUser = response.data;
                        })
                        .catch(error => {
                            console.error('Error fetching current user:', error);
                        });
                },
                
                getUsers() {
                    axios.get('/api/users')
                        .then(response => {
                            this.users = response.data.filter(user => user.id !== this.currentUser?.id);
                        })
                        .catch(error => {
                            console.error('Error fetching users:', error);
                        });
                },
                
                selectUser(user) {
                    this.selectedUser = user;
                    this.createOrGetGroup(user.id);
                },
                
                createOrGetGroup(userId) {
                    axios.post('/api/groups/create-or-get', {
                        user_id: userId
                    })
                        .then(response => {
                            this.group = response.data;
                            this.getConversations();
                            this.listenForNewMessage();
                        })
                        .catch(error => {
                            console.error('Error creating/getting group:', error);
                        });
                },
                
                getConversations() {
                    if (!this.group) return;
                    
                    axios.get(`/api/conversations/${this.group.id}`)
                        .then(response => {
                            this.conversations = response.data;
                            this.scrollToBottom();
                        })
                        .catch(error => {
                            console.error('Error fetching conversations:', error);
                        });
                },
                
                sendMessage() {
                    if (!this.newMessage.trim() || !this.group) return;
                    
                    axios.post('/api/conversations', {
                        message: this.newMessage,
                        group_id: this.group.id
                    })
                        .then(response => {
                            // Add the new message to conversations
                            this.conversations.push(response.data);
                            this.newMessage = '';
                            this.scrollToBottom();
                        })
                        .catch(error => {
                            console.error('Error sending message:', error);
                        });
                },
                
                listenForNewMessage() {
                    if (!this.group) return;
                    
                    Echo.private('groups.' + this.group.id)
                        .listen('NewMessage', (e) => {
                            this.conversations.push(e.conversation);
                            this.scrollToBottom();
                        });
                },
                
                scrollToBottom() {
                    this.$nextTick(() => {
                        const messagesContainer = this.$refs.messagesContainer;
                        if (messagesContainer) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    });
                },
                
                formatTime(timestamp) {
                    if (!timestamp) return '';
                    
                    const date = new Date(timestamp);
                    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            },
            
            watch: {
                group(newGroup, oldGroup) {
                    // Unsubscribe from old group channel
                    if (oldGroup) {
                        Echo.leave('private-groups.' + oldGroup.id);
                    }
                    
                    // Subscribe to new group channel
                    if (newGroup) {
                        this.listenForNewMessage();
                    }
                }
            }
        };
        
        createApp(ChatComponent).mount('#chat-app');
    });
</script>

<script type="text/x-template" id="chat-component-template">
    <div class="chat-container">
        <div class="flex h-full">
            <!-- User List Sidebar -->
            <div class="w-1/4 bg-gray-100 border-r border-gray-200 overflow-y-auto">
                <div class="p-4 border-b border-gray-200">
                    <div class="relative">
                        <input 
                            type="text" 
                            v-model="searchQuery" 
                            placeholder="Search users..." 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                        <div v-if="searchQuery" class="absolute right-3 top-2.5 cursor-pointer text-gray-500" @click="searchQuery = ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="user-list">
                    <div 
                        v-for="user in filteredUsers" 
                        :key="user.id" 
                        @click="selectUser(user)"
                        class="p-4 border-b border-gray-200 hover:bg-gray-200 cursor-pointer flex items-center"
                        :class="{'bg-blue-50': selectedUser && selectedUser.id === user.id}"
                    >
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="font-medium">{{ user.name }}</div>
                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="w-3/4 flex flex-col h-full">
                <div v-if="!group" class="flex-1 flex items-center justify-center bg-gray-50">
                    <div class="text-center text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-xl">Select a user to start chatting</p>
                    </div>
                </div>
                
                <div v-else class="flex flex-col h-full">
                    <!-- Chat Header -->
                    <div class="p-4 border-b border-gray-200 bg-white flex items-center">
                        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-3">
                            {{ selectedUser.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <div class="font-medium">{{ selectedUser.name }}</div>
                            <div class="text-sm text-gray-500">{{ selectedUser.online ? 'Online' : 'Offline' }}</div>
                        </div>
                    </div>
                    
                    <!-- Messages Area -->
                    <div class="flex-1 p-4 overflow-y-auto bg-gray-50" ref="messagesContainer" style="height: 400px;">
                        <div v-for="(message, index) in conversations" :key="index" class="mb-4">
                            <div 
                                :class="[
                                    'max-w-xs rounded-lg p-3 mb-2', 
                                    message.user.id === currentUser.id 
                                        ? 'bg-blue-500 text-white ml-auto' 
                                        : 'bg-gray-200 text-gray-800'
                                ]"
                            >
                                {{ message.message }}
                            </div>
                            <div 
                                :class="[
                                    'text-xs text-gray-500', 
                                    message.user.id === currentUser.id ? 'text-right' : 'text-left'
                                ]"
                            >
                                {{ message.user.name }} • {{ formatTime(message.created_at) }}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Message Input -->
                    <div class="p-4 border-t border-gray-200 bg-white">
                        <form @submit.prevent="sendMessage" class="flex">
                            <input 
                                type="text" 
                                v-model="newMessage" 
                                placeholder="Type a message..." 
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                            <button 
                                type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-r-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :disabled="!newMessage.trim()"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<style>
.chat-container {
    height: 70vh;
}

.user-list {
    max-height: calc(70vh - 80px);
    overflow-y: auto;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
@endpush
