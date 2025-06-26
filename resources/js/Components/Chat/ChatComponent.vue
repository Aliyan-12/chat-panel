<template>
  <div class="chat-container h-full bg-[#181F2A] flex">
    <!-- Sidebar -->
    <aside class="w-80 bg-[#232B3E] border-r border-[#232B3E] flex flex-col h-full shadow-lg">
      <div class="p-4 border-b border-[#232B3E] flex items-center justify-between">
        <span class="text-xl font-bold text-orange-400 tracking-tight">Workzen Chat</span>
        <button @click="openCreateGroupModal" class="bg-orange-500 hover:bg-orange-600 text-white rounded-full w-10 h-10 flex items-center justify-center shadow focus:outline-none transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
        </button>
      </div>
      <div class="p-3">
            <input 
              type="text" 
              v-model="searchQuery" 
          placeholder="Search chats..."
          class="w-full px-4 py-2 border border-[#232B3E] rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-400 bg-[#232B3E] text-sm text-white placeholder-gray-400"
            >
            </div>
      <!-- Filter Tabs -->
      <div class="flex border-b border-[#232B3E] mb-1">
        <button @click="activeTab = 'all'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'all' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            All
          </button>
        <button @click="activeTab = 'users'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'users' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            Users
          </button>
        <button @click="activeTab = 'groups'" class="flex-1 py-2 text-center font-medium transition"
          :class="activeTab === 'groups' ? 'text-orange-400 border-b-2 border-orange-400 bg-[#232B3E]' : 'text-gray-400'">
            Groups
          </button>
        </div>
      <nav class="flex-1 overflow-y-auto">
        <div v-for="chat in filteredChatList" :key="chat.id"
          @click="chat.type === 'group' ? selectGroup(chat.group) : selectUser(chat.user)"
          class="flex items-center p-3 rounded-lg hover:bg-[#222B45] transition group cursor-pointer relative mb-1"
          :class="{'bg-[#222B45] border-l-4 border-orange-400': selectedItem && ((chat.type === 'user' && selectedItem.id === chat.user?.id) || (chat.type === 'group' && selectedItem.id === chat.group?.id))}"
        >
          <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3 shadow"
            :class="chat.type === 'group' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : 'bg-gradient-to-br from-blue-400 to-blue-600'">
            {{ chat.type === 'group' ? chat.group.name.charAt(0).toUpperCase() : chat.user.name.charAt(0).toUpperCase() }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-center">
              <span class="font-semibold truncate text-white">{{ chat.type === 'group' ? chat.group.name : chat.user.name }}</span>
              <span class="text-xs text-gray-400 ml-2">{{ chat.last_message ? formatTime(chat.last_message.created_at) : '' }}</span>
            </div>
            <div class="text-sm text-gray-400 truncate">{{ chat.last_message ? chat.last_message.message : 'No messages yet' }}</div>
          </div>
          <span v-if="chat.unread_count > 0" class="ml-2 bg-orange-500 text-white text-xs rounded-full px-2 py-0.5 animate-pulse shadow">{{ chat.unread_count }}</span>
        </div>
        <div v-if="filteredChatList.length === 0" class="p-4 text-center text-gray-400">No chats found</div>
      </nav>
    </aside>

    <!-- Main Chat Area -->
    <section class="flex-1 flex flex-col h-full">
      <header v-if="currentConversation" class="p-4 border-b border-gray-200 bg-white flex items-center shadow-sm">
        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg mr-3 shadow"
          :class="selectedItem.type === 'user' ? 'bg-gradient-to-br from-blue-400 to-blue-600' : 'bg-gradient-to-br from-green-400 to-green-600'">
          {{ selectedItem.name.charAt(0).toUpperCase() }}
        </div>
        <div class="flex-1">
          <div class="text-black font-semibold text-lg">{{ selectedItem.name }}</div>
          <div class="text-xs text-gray-500">
            <span v-if="selectedItem.type === 'user'">{{ selectedItem.online ? 'Online' : 'Offline' }}</span>
            <span v-else>{{ selectedItem.users.length }} members</span>
          </div>
        </div>
        <button v-if="selectedItem.type === 'group'" @click="openAddParticipantsModal" class="ml-4 px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition text-sm font-medium flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
          Add Participants
          </button>
      </header>
      <div v-else class="flex-1 flex items-center justify-center bg-gray-50">
        <div class="text-center text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <p class="text-xl">Select a user or group to start chatting</p>
            </div>
          </div>
          
          <!-- Messages Area -->
      <main v-if="currentConversation" class="flex-1 p-6 overflow-y-auto bg-gradient-to-b from-gray-50 to-gray-100" ref="messagesContainer">
            <div v-if="conversations.length === 0" class="flex items-center justify-center h-full">
          <p class="text-gray-400">No messages yet. Start the conversation!</p>
        </div>
        <div v-else v-for="(message, index) in conversations" :key="index" :id="`message-${message.id}`" class="mb-6 flex flex-col"
          :class="{'items-end': message.user_id === currentUser.id, 'items-start': message.user_id !== currentUser.id}">
          <div class="flex items-end max-w-lg">
            <div v-if="message.user_id !== currentUser.id" class="w-8 h-8 rounded-full bg-blue-200 flex items-center justify-center text-blue-700 font-bold mr-2">
              {{ message.user ? message.user.name.charAt(0).toUpperCase() : '?' }}
            </div>
            <div :class="[
              'rounded-2xl px-4 py-2 shadow',
              message.user_id === currentUser.id ? 'bg-blue-500 text-white' : 'bg-white text-gray-800 border border-gray-200'
            ]">
                <template v-if="message.type === 'file'">
                  <a :href="fileUrl(message.file_path || message.message)" target="_blank" class="underline break-all">
                    <template v-if="isImageFile(message.file_path || message.message)">
                      <img :src="fileUrl(message.file_path || message.message)" :alt="message.message" class="max-h-40 max-w-xs rounded mb-1" />
                    </template>
                    <span>{{ getFileName(message.file_path || message.message) }}</span>
                  </a>
                </template>
                <template v-else>
                {{ message.message }}
                </template>
              </div>
            <div v-if="message.user_id === currentUser.id" class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold ml-2">
              {{ currentUser.name.charAt(0).toUpperCase() }}
            </div>
          </div>
          <span class="text-xs text-gray-400 mt-1" :class="{'text-right': message.user_id === currentUser.id, 'text-left': message.user_id !== currentUser.id}">
            {{ message.user ? message.user.name : 'Unknown' }} • {{ formatTime(message.created_at) }}
          </span>
        </div>
      </main>
          
          <!-- Message Input -->
      <footer v-if="currentConversation" class="p-4 border-t border-gray-200 bg-white flex items-center space-x-2 shadow-inner">
        <form enctype="multipart/form-data" @submit.prevent="sendMessage" class="flex items-center w-full space-x-2">
          <label class="inline-flex items-center cursor-pointer bg-gray-100 px-3 py-2 rounded-lg hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a4 4 0 10-5.656-5.656l-6.586 6.586" />
                </svg>
                <input type="file" class="hidden" @change="onFileChange" ref="fileInput" />
              </label>
          <input
            type="text"
            v-model="newMessage"
            placeholder="Type a message..."
            class="flex-1 px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 bg-gray-50 text-sm"
            :disabled="fileToSend"
          >
              <button 
                type="submit" 
            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                :disabled="(!newMessage.trim() && !fileToSend)"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
              </button>
            </form>
        <div v-if="fileToSend" class="ml-2 flex items-center space-x-2 bg-gray-100 p-2 rounded-lg">
              <span class="truncate max-w-xs">{{ fileToSend.name }}</span>
              <button @click="removeFile" class="text-red-500 hover:text-red-700" title="Remove file">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
      </footer>
    </section>

    <!-- Toaster Notification -->
    <transition name="fade">
      <div
        v-if="toaster.show"
        @click="onToasterClick"
        class="fixed z-50 right-6 bottom-6 bg-blue-600 text-white px-6 py-4 rounded-lg shadow-lg flex items-center space-x-3 animate-fade-in-up cursor-pointer"
        style="min-width: 250px; max-width: 350px;"
      >
        <div class="flex-1">
          <div class="font-bold">
            <span v-if="toaster.group">{{ toaster.group }} - </span>{{ toaster.sender }}
          </div>
          <div class="text-sm">
            {{ toaster.shortMessage }}
          </div>
          <div class="text-xs text-gray-200 mt-1">{{ toaster.time }}</div>
        </div>
        <button @click.stop="dismissToaster" class="ml-2 text-white hover:text-gray-200 focus:outline-none">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </transition>

    <!-- Add Participants Modal -->
    <div v-if="showAddParticipantsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Add Participants</h2>
          <button @click="showAddParticipantsModal = false" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mb-4">
          <input type="text" v-model="addParticipantsSearch" placeholder="Search users..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-2 mb-4">
          <div v-for="user in filteredAddParticipantsUsers" :key="user.id" class="flex items-center p-2 hover:bg-gray-100 rounded">
            <input type="checkbox" :id="'add-user-' + user.id" :value="user.id" v-model="selectedAddParticipants" class="mr-2" />
            <label :for="'add-user-' + user.id" class="flex items-center cursor-pointer">
              <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
                {{ user.name.charAt(0).toUpperCase() }}
              </div>
              <div>
                <div class="font-medium">{{ user.name }}</div>
                <div class="text-xs text-gray-500">{{ user.email }}</div>
              </div>
            </label>
          </div>
          <div v-if="filteredAddParticipantsUsers.length === 0" class="text-gray-400 text-center py-2">No users found</div>
        </div>
        <div class="flex justify-end">
          <button @click="showAddParticipantsModal = false" class="px-4 py-2 text-gray-600 mr-2 hover:text-gray-800">Cancel</button>
          <button @click="addParticipantsToGroup" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" :disabled="selectedAddParticipants.length === 0">Add</button>
        </div>
      </div>
    </div>
    
    <!-- Create Group Modal -->
    <div v-if="showCreateGroupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-96 max-w-full">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Create New Group</h2>
          <button @click="showCreateGroupModal = false" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="mb-4">
          <input type="text" id="group-name" v-model="groupName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter group name" />
        </div>
        <div class="mb-4">
          <input type="text" v-model="groupUserSearch" placeholder="Search users..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2" />
          <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-lg p-2">
            <div v-for="user in filteredGroupUsers" :key="user.id" class="flex items-center p-2 hover:bg-gray-100 rounded">
              <input type="checkbox" :id="'user-' + user.id" :value="user.id" v-model="selectedGroupUsers" class="mr-2" />
              <label :for="'user-' + user.id" class="flex items-center cursor-pointer">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold mr-2">
                  {{ user.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <div class="font-medium">{{ user.name }}</div>
                  <div class="text-xs text-gray-500">{{ user.email }}</div>
                </div>
              </label>
            </div>
            <div v-if="filteredGroupUsers.length === 0" class="text-gray-400 text-center py-2">No users found</div>
          </div>
        </div>
        <div class="flex justify-end">
          <button @click="showCreateGroupModal = false" class="px-4 py-2 text-gray-600 mr-2 hover:text-gray-800">Cancel</button>
          <button @click="createGroup" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500" :disabled="!groupName.trim() || selectedGroupUsers.length === 0">Create Group</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const currentUser = ref(null);
const searchResults = ref({ users: [], groups: [] });
const selectedItem = ref(null);
const currentConversation = ref(null);
const conversations = ref([]);
const newMessage = ref('');
const searchQuery = ref('');
const activeTab = ref('all');
const isLoading = ref(true);
const hasError = ref(false);
const errorMessage = ref('');
const messagesContainer = ref(null);

// Group creation
const showCreateGroupModal = ref(false);
const groupUsers = ref([]);
const selectedGroupUsers = ref([]);
const groupName = ref('');

const fileToSend = ref(null);
const fileInput = ref(null);

const notifications = ref([]);
const toaster = ref({
  show: false,
  message: '',
  shortMessage: '',
  sender: '',
  group: '',
  time: '',
  conversation_id: null,
  group_id: null,
  user_id: null,
  message_id: null,
  timeout: null,
});

const chatList = ref([]); // All conversations with unread counts

const showAddParticipantsModal = ref(false);
const addParticipantsSearch = ref('');
const addParticipantsUsers = ref([]); // Users not in group
const selectedAddParticipants = ref([]);

// For group creation modal search
const groupUserSearch = ref('');

const filteredItems = computed(() => {
  const result = { users: [], groups: [] };
  if (!searchResults.value.users || !searchResults.value.groups) {
    return result;
  }
  if (!searchQuery.value) {
    if (activeTab.value === 'users') {
      return { users: searchResults.value.users || [], groups: [] };
    } else if (activeTab.value === 'groups') {
      return { users: [], groups: searchResults.value.groups || [] };
    } else {
      return searchResults.value;
    }
  }
  const query = searchQuery.value.toLowerCase();
  const filteredUsers = activeTab.value === 'groups' ? [] :
    (searchResults.value.users || []).filter(user =>
      user.name.toLowerCase().includes(query) ||
      user.email.toLowerCase().includes(query)
    );
  const filteredGroups = activeTab.value === 'users' ? [] :
    (searchResults.value.groups || []).filter(group =>
      group.name.toLowerCase().includes(query)
    );
  return {
    users: filteredUsers,
    groups: filteredGroups
  };
});

const filteredChatList = computed(() => {
  let list = chatList.value;
  if (activeTab.value === 'users') {
    list = list.filter(c => c.type === 'individual');
  } else if (activeTab.value === 'groups') {
    list = list.filter(c => c.type === 'group');
  }
  if (!searchQuery.value) return list;
  const q = searchQuery.value.toLowerCase();
  return list.filter(c => {
    if (c.type === 'group') {
      return c.group.name.toLowerCase().includes(q);
    } else if (c.type === 'individual') {
      return c.user.name.toLowerCase().includes(q) || c.user.email.toLowerCase().includes(q);
    }
    return false;
  });
});

function getCurrentUser() {
  isLoading.value = true;
  return axios.get('/user')
    .then(response => {
      currentUser.value = response.data;
    })
    .catch(error => {
      throw error;
    });
}

function getSearchResults() {
  axios.get('/search')
    .then(response => {
      searchResults.value = response.data;
      isLoading.value = false;
    })
    .catch(error => {
      isLoading.value = false;
    });
}

async function getChatList() {
  isLoading.value = true;
  try {
    const res = await axios.get('/conversations');
    chatList.value = res.data.conversations || [];
    isLoading.value = false;
  } catch (e) {
    isLoading.value = false;
  }
}

async function getOrCreateIndividualConversation(userId) {
  isLoading.value = true;
  try {
    const res = await axios.get('/conversations', { params: { user_id: userId } });
    currentConversation.value = res.data.conversation;
    conversations.value = res.data.messages || [];
    isLoading.value = false;
    scrollToBottom();
    
    // Listen for new messages in this conversation
    listenForNewMessages(currentConversation.value.id);
  } catch (e) {
    isLoading.value = false;
  }
}

async function getGroupConversation(groupId) {
  isLoading.value = true;
  try {
    const res = await axios.get(`/conversations/${groupId}`);
    currentConversation.value = res.data.conversation;
    conversations.value = res.data.messages || [];
    isLoading.value = false;
    scrollToBottom();
    
    // Listen for new messages in this conversation
    listenForNewMessages(currentConversation.value.id);
  } catch (e) {
    isLoading.value = false;
  }
}

async function selectUser(user) {
  selectedItem.value = { ...user, type: 'user' };
  await getOrCreateIndividualConversation(user.id);
  await markChatAsRead(currentConversation.value.id);
  updateChatListOnOpen(currentConversation.value.id);
}

async function selectGroup(group) {
  selectedItem.value = { ...group, type: 'group' };
  await getGroupConversation(group.id);
  await markChatAsRead(currentConversation.value.id);
  updateChatListOnOpen(currentConversation.value.id);
}

function listenForNewMessages(conversationId) {
  // First, leave any existing channels
  if (window.Echo) {
    try {
      // Clean up any existing listeners to avoid duplicates
      window.Echo.leave(`conversation.${conversationId}`);
    } catch (error) {
      console.error('Error leaving channel:', error);
    }
  } else {
    console.error('Echo is not initialized');
    return;
  }
  
  // Then, listen to the new channel
  console.log(`Listening to private-conversation.${conversationId} channel`);
  
  try {
    window.Echo.private(`conversation.${conversationId}`)
      .listen('.NewMessage', (event) => {
        console.log('New message received (with dot):', event);
        handleNewMessage(event);
      })
      .listen('NewMessage', (event) => {
        console.log('New message received (without dot):', event);
        handleNewMessage(event);
      })
      .error((error) => {
        console.error('Echo connection error:', error);
      });
  } catch (error) {
    console.error('Error setting up Echo listener:', error);
  }
}

function handleNewMessage(event) {
  if (event.message && event.message.conversation_id) {
    // Find chat in chatList
    const idx = chatList.value.findIndex(c => c.id === event.message.conversation_id);
    if (idx !== -1) {
      // If not currently open, increment unread count
      if (!currentConversation.value || currentConversation.value.id !== event.message.conversation_id) {
        chatList.value[idx].unread_count = (chatList.value[idx].unread_count || 0) + 1;
      }
      // Move chat to top
      const chat = chatList.value.splice(idx, 1)[0];
      chatList.value.unshift(chat);
    } else {
      // If not found, refetch chatList
      getChatList();
    }
  }
  // Add the new message to the conversation if open
  if (event.message && currentConversation.value && 
      event.message.conversation_id === currentConversation.value.id) {
    const messageExists = conversations.value.some(m => m.id === event.message.id);
    if (!messageExists) {
      conversations.value.push(event.message);
      scrollToBottom();
    }
  }
}

// Clean up function to leave channels when component is unmounted
function leaveChannels() {
  if (currentConversation.value && window.Echo) {
    try {
      console.log(`Leaving channel: conversation.${currentConversation.value.id}`);
      window.Echo.leave(`conversation.${currentConversation.value.id}`);
    } catch (error) {
      console.error('Error leaving channel:', error);
    }
  }
}

function onFileChange(e) {
  const file = e.target.files[0];
  if (file) {
    fileToSend.value = file;
    newMessage.value = '';
  }
}

function removeFile() {
  fileToSend.value = null;
  if (fileInput.value) fileInput.value.value = '';
}

function fileUrl(path) {
  if (!path) return '';
  // Assuming files are stored in public disk
  return path.startsWith('/') ? path : `/storage/${path}`;
}

function isImageFile(path) {
  if (!path) return false;
  return /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(path);
}

function getFileName(path) {
  if (!path) return 'File';
  // Extract just the filename from the path (after the last slash)
  const filename = path.split('/').pop();
  // Remove the timestamp prefix if present (format: YYYYMMDDHHmmss_filename.ext)
  return filename.replace(/^\d{14}_/, '');
}

async function sendMessage() {
  if ((!newMessage.value.trim() && !fileToSend.value) || !currentConversation.value) return;
  try {
    const formData = new FormData();
    formData.append('conversation_id', currentConversation.value.id);
    if (fileToSend.value) {
      formData.append('file', fileToSend.value);
    }
    if (newMessage.value.trim()) {
      formData.append('message', newMessage.value);
    }
    
    console.log('Sending message:', fileToSend.value ? 'with file' : 'text only');
    
    const res = await axios.post('/conversations', formData, {
      headers: { 
        'Content-Type': 'multipart/form-data',
        'Accept': 'application/json'
      }
    });
    
    fileToSend.value = null;
    if (fileInput.value) fileInput.value.value = '';
    conversations.value.push(res.data);
    newMessage.value = '';
    scrollToBottom();
  } catch (e) {
    console.error('Error sending message:', e.response ? e.response.data : e);
  }
}

function openCreateGroupModal() {
  showCreateGroupModal.value = true;
  groupName.value = '';
  selectedGroupUsers.value = [];
  // Load all users for selection
  axios.get('/users')
    .then(res => {
      groupUsers.value = res.data.users || res.data;
    })
    .catch(error => {
      console.error('Error loading users for group creation:', error);
    });
}

async function createGroup() {
  if (!groupName.value.trim() || selectedGroupUsers.value.length === 0) return;
  
  isLoading.value = true;
  try {
    const res = await axios.post('/groups/create-or-get', {
      name: groupName.value,
      user_ids: selectedGroupUsers.value
    });
    
    // Close modal and reset values
    showCreateGroupModal.value = false;
    selectedGroupUsers.value = [];
    groupName.value = '';
    
    // Refresh the groups list
    getSearchResults();
    
    // Select the newly created group
    if (res.data && res.data.group) {
      selectGroup(res.data.group);
    }
    
    isLoading.value = false;
  } catch (e) {
    console.error('Error creating group:', e);
    isLoading.value = false;
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
}

function formatTime(timestamp) {
  if (!timestamp) return '';
  const date = new Date(timestamp);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Set up CSRF protection
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Listen for real-time notifications
function listenForNotifications(userId) {
  if (!window.Echo || !userId) return;
  window.Echo.private(`App.Models.User.${userId}`)
    .notification((notification) => {
      notifications.value.unshift(notification);
      showToaster(notification);
    });
}

function showToaster(notification) {
  toaster.value.show = true;
  toaster.value.message = notification.message;
  toaster.value.shortMessage = getShortMessage(notification.message);
  toaster.value.sender = notification.sender_name;
  toaster.value.group = notification.group_name || '';
  toaster.value.time = formatTime(notification.created_at);
  toaster.value.conversation_id = notification.conversation_id || null;
  toaster.value.group_id = notification.group_id || null;
  toaster.value.user_id = notification.sender_id || null;
  toaster.value.message_id = notification.message_id || null;
  if (toaster.value.timeout) clearTimeout(toaster.value.timeout);
  toaster.value.timeout = setTimeout(() => {
    toaster.value.show = false;
  }, 10000); // 10 seconds
}

function dismissToaster() {
  toaster.value.show = false;
  if (toaster.value.timeout) clearTimeout(toaster.value.timeout);
}

function getShortMessage(msg) {
  if (!msg) return '';
  return msg.split(' ').slice(0, 2).join(' ') + (msg.split(' ').length > 2 ? '...' : '');
}

async function onToasterClick() {
  toaster.value.show = false;
  if (toaster.value.group_id) {
    // It's a group message
    const group = (searchResults.value.groups || []).find(g => g.id === toaster.value.group_id);
    if (group) {
      await selectGroup(group);
    }
  } else if (toaster.value.user_id) {
    // It's a direct message
    const user = (searchResults.value.users || []).find(u => u.id === toaster.value.user_id);
    if (user) {
      await selectUser(user);
    }
  }
  // Wait for messages to load, then scroll to the message
  nextTick(() => {
    scrollToMessage(toaster.value.message_id);
  });
}

function scrollToMessage(messageId) {
  nextTick(() => {
    const messageEl = document.getElementById(`message-${messageId}`);
    if (messageEl) {
      messageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
      messageEl.classList.add('highlight');
      setTimeout(() => messageEl.classList.remove('highlight'), 2000);
    }
  });
}

async function markChatAsRead(conversationId) {
  try {
    await axios.post('/conversations/read', { conversation_id: conversationId });
  } catch (e) {}
}

function updateChatListOnOpen(conversationId) {
  // Reset unread count and move chat to top
  const idx = chatList.value.findIndex(c => c.id === conversationId);
  if (idx !== -1) {
    chatList.value[idx].unread_count = 0;
    const chat = chatList.value.splice(idx, 1)[0];
    chatList.value.unshift(chat);
  }
}

async function openAddParticipantsModal() {
  if (!currentConversation.value || !currentConversation.value.group_id) return;
  showAddParticipantsModal.value = true;
  addParticipantsSearch.value = '';
  selectedAddParticipants.value = [];
  // Fetch all users not in the group
  const groupId = currentConversation.value.group_id;
  try {
    const res = await axios.get('/users');
    const groupUsers = selectedItem.value.users.map(u => u.id);
    addParticipantsUsers.value = (res.data.users || res.data).filter(u => !groupUsers.includes(u.id));
  } catch (e) {
    addParticipantsUsers.value = [];
  }
}

async function addParticipantsToGroup() {
  if (!currentConversation.value || !currentConversation.value.group_id || selectedAddParticipants.value.length === 0) return;
  try {
    await axios.post(`/groups/${currentConversation.value.group_id}/add-users`, {
      user_ids: selectedAddParticipants.value
    });
    showAddParticipantsModal.value = false;
    // Refresh group info
    await selectGroup(selectedItem.value);
  } catch (e) {
    // handle error
  }
}

const filteredAddParticipantsUsers = computed(() => {
  if (!addParticipantsSearch.value) return addParticipantsUsers.value;
  const q = addParticipantsSearch.value.toLowerCase();
  return addParticipantsUsers.value.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
});

const filteredGroupUsers = computed(() => {
  if (!groupUserSearch.value) return groupUsers.value;
  const q = groupUserSearch.value.toLowerCase();
  return groupUsers.value.filter(u =>
    u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q)
  );
});

// Initialize component
onMounted(async () => {
  try {
    await axios.get('/sanctum/csrf-cookie');
    await getCurrentUser();
    await getChatList();
    getSearchResults();
    // Fetch existing notifications
    const notifRes = await axios.post('/notifications');
    notifications.value = notifRes.data.data || [];
    // Listen for real-time notifications
    if (currentUser.value && currentUser.value.id) {
      listenForNotifications(currentUser.value.id);
    }
  } catch (error) {
    console.error('Initialization error:', error);
    hasError.value = true;
    errorMessage.value = 'Failed to load user data. Please refresh the page.';
    isLoading.value = false;
  }
});

// Clean up when component is unmounted
onUnmounted(() => {
  leaveChannels();
});
</script>

<style scoped>
.chat-container {
  height: 80vh;
  min-height: 500px;
  border-radius: 1.5rem;
  overflow: hidden;
  box-shadow: 0 4px 32px 0 rgba(0,0,0,0.08);
  background: #181F2A;
}

::-webkit-scrollbar {
  width: 6px;
}
::-webkit-scrollbar-thumb {
  background: #232B3E;
  border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
  background: #FFA726;
}
.animate-fade-in-up {
  animation: fade-in-up 0.4s;
}
@keyframes fade-in-up {
  from { opacity: 0; transform: translateY(40px);}
  to { opacity: 1; transform: translateY(0);}
}
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style> 